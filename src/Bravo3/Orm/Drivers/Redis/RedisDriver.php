<?php
namespace Bravo3\Orm\Drivers\Redis;

use Bravo3\Orm\Drivers\Common\Command;
use Bravo3\Orm\Drivers\Common\Ref;
use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Drivers\Common\UnitOfWork;
use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\KeySchemes\StandardKeyScheme;
use Bravo3\Orm\Services\ScoreNormaliser;
use Bravo3\Orm\Traits\DebugTrait;
use Predis\Client;
use Predis\Command\CommandInterface;
use Predis\Command\KeyScan;
use Predis\Connection\Aggregate\PredisCluster;
use Predis\Connection\Aggregate\ReplicationInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Pipeline\Pipeline;

class RedisDriver implements DriverInterface
{
    use DebugTrait;

    /**
     * @var UnitOfWork
     */
    protected $unit_of_work;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ScoreNormaliser
     */
    protected $score_normaliser = null;

    /**
     * Create a new Redis driver
     *
     * @param mixed $params
     * @param mixed $options
     */
    public function __construct($params = null, $options = null)
    {
        $this->client       = new Client($params, $options);
        $this->unit_of_work = new UnitOfWork();
    }

    /**
     * Persist some primitive data
     *
     * @param string         $key
     * @param SerialisedData $data
     * @param int            $ttl
     * @return void
     */
    public function persist($key, SerialisedData $data, $ttl = null)
    {
        $params = [$key, $data->getSerialisationCode().$data->getData()];
        if ($ttl) {
            $params[] = 'EX';
            $params[] = $ttl;
        }
        $this->unit_of_work->addCommand('StringSet', $params);
    }

    /**
     * Delete a primitive document
     *
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        $this->unit_of_work->addCommand('KeyDelete', [$key]);
    }

    /**
     * Retrieve an object, throwing an exception if not found
     *
     * @param string $key
     * @return SerialisedData
     */
    public function retrieve($key)
    {
        if (!$this->client->exists($key)) {
            throw new NotFoundException('Key "'.$key.'" does not exist');
        }

        $data = $this->client->get($key);
        return new SerialisedData(substr($data, 0, 4), substr($data, 4));
    }

    /**
     * Execute the current unit of work
     *
     * @return void
     */
    public function flush()
    {
        switch ($this->unit_of_work->getQueueSize()) {
            case 0:
                return;
            case 1:
                $this->flushSingle();
                break;
            default:
                $this->flushMulti();
                break;
        }
    }

    /**
     * Execute the next item in the work queue
     */
    private function flushSingle()
    {
        $command = $this->unit_of_work->getWork();
        if ($command) {
            $this->client->executeCommand($this->getPredisCommand($command));
        }
    }

    /**
     * Execute all items in the work queue in a single transaction
     */
    private function flushMulti()
    {
        $this->client->pipeline(
            function ($pipe) {
                /** @var Pipeline $pipe */
                while ($command = $this->unit_of_work->getWork()) {
                    $pipe->executeCommand($this->getPredisCommand($command));
                }
            }
        );
    }

    /**
     * Build a Predis command from a Command object
     *
     * @param Command $command
     * @return CommandInterface
     */
    private function getPredisCommand($command)
    {
        $class = 'Predis\Command\\'.$command->getName();

        /** @var CommandInterface $predis_command */
        $predis_command = new $class();
        $predis_command->setArguments($command->getArguments());
        return $predis_command;
    }

    /**
     * Purge the current unit of work, clearing any unexecuted commands
     *
     * @return void
     */
    public function purge()
    {
        $this->unit_of_work->purge();
    }

    /**
     * Get the drivers preferred key scheme
     *
     * @return KeySchemeInterface
     */
    public function getPreferredKeyScheme()
    {
        return new StandardKeyScheme();
    }

    /**
     * Clear the value of a key-value index
     *
     * @param string $key
     * @return string
     */
    public function clearSingleValueIndex($key)
    {
        $this->unit_of_work->addCommand('KeyDelete', [$key]);
    }

    /**
     * Set a key-value index
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setSingleValueIndex($key, $value)
    {
        $this->unit_of_work->addCommand('StringSet', [$key, $value]);
    }

    /**
     * Get the value of a key-value index
     *
     * If the key does not exist, null should be returned.
     *
     * @param string $key
     * @return string
     */
    public function getSingleValueIndex($key)
    {
        return $this->client->get($key) ?: null;
    }

    /**
     * Clear all values from a set index
     *
     * @param string $key
     * @return void
     */
    public function clearMultiValueIndex($key)
    {
        $this->unit_of_work->addCommand('KeyDelete', [$key]);
    }

    /**
     * Add one or many values to a set index
     *
     * @param string       $key
     * @param string|array $value
     * @return void
     */
    public function addMultiValueIndex($key, $value)
    {
        $this->unit_of_work->addCommand('SetAdd', [$key, $value]);
    }

    /**
     * Remove one or more values from a set index
     *
     * @param string       $key
     * @param string|array $value
     * @return void
     */
    public function removeMultiValueIndex($key, $value)
    {
        $this->unit_of_work->addCommand('SetRemove', [$key, $value]);
    }

    /**
     * Get a list of all values on a set index
     *
     * If the key does not exist, an empty array should be returned.
     *
     * @param string $key
     * @return string[]
     */
    public function getMultiValueIndex($key)
    {
        return $this->client->smembers($key) ?: [];
    }

    /**
     * Scan key-value indices and return the value of all matching keys
     *
     * @param string $key
     * @return string[]
     */
    public function scan($key)
    {
        $cursor     = 0;
        $results    = [];
        $connection = $this->client->getConnection();

        do {
            if ($connection instanceof ReplicationInterface) {
                $slaves = $connection->getSlaves();
                /** @var NodeConnectionInterface $slave */
                $slave = $slaves[rand(0, count($slaves) - 1)];
                $cmd   = new KeyScan();
                $cmd->setArguments([$cursor, 'MATCH', $key]);
                $set = $slave->executeCommand($cmd);
            } elseif ($connection instanceof PredisCluster) {
                $iterator = $connection->getIterator();
                /** @var NodeConnectionInterface $node */
                $node = $iterator->current();
                $cmd  = new KeyScan();
                $cmd->setArguments([$cursor, 'MATCH', $key]);
                $set = $node->executeCommand($cmd);
            } else {
                $set = $this->client->scan($cursor, ['MATCH' => $key]);
            }

            $cursor  = $set[0];
            $results = array_merge($results, $set[1]);
        } while ($cursor != 0);

        return $results;
    }

    /**
     * Clear an entire sorted index
     *
     * @param string $key
     * @return void
     */
    public function clearSortedIndex($key)
    {
        $this->unit_of_work->addCommand('KeyDelete', [$key]);
    }

    /**
     * Add or update an item in a sorted index
     *
     * @param string $key
     * @param mixed  $score
     * @param string $value
     * @return void
     */
    public function addSortedIndex($key, $score, $value)
    {
        $this->unit_of_work->addCommand('ZSetAdd', [$key, $this->getScoreNormaliser()->score($score), $value]);
    }

    /**
     * Remove an item from a sorted index
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function removeSortedIndex($key, $value)
    {
        $this->unit_of_work->addCommand('ZSetRemove', [$key, $value]);
    }

    /**
     * Get a range values in a sorted index
     *
     * If $start/$stop are null then they are assumed to be the start/end of the entire set
     *
     * @param string $key
     * @param bool   $reverse
     * @param int    $start
     * @param int    $stop
     * @return string[]
     */
    public function getSortedIndex($key, $reverse = false, $start = null, $stop = null)
    {
        if ($reverse) {
            return $this->client->zrevrange($key, $start === null ? 0 : $start, $stop === null ? -1 : $stop);
        } else {
            return $this->client->zrange($key, $start === null ? 0 : $start, $stop === null ? -1 : $stop);
        }
    }

    /**
     * Create a debug log
     *
     * @param string $msg
     * @return void
     */
    public function debugLog($msg)
    {
        if (!$this->getDebugMode() || !$msg) {
            return;
        }

        if ($msg{0} == '@') {
            $this->unit_of_work->addCommand('ConnectionEcho', [substr($msg, 1)]);
        } else {
            $this->client->echo($msg);
        }
    }

    /**
     * Lazy-loading score normaliser
     *
     * @return ScoreNormaliser
     */
    protected function getScoreNormaliser()
    {
        if ($this->score_normaliser === null) {
            $this->score_normaliser = new ScoreNormaliser();
        }

        return $this->score_normaliser;
    }

    /**
     * Get the size of a sorted index, without any filters applied
     *
     * @param string $key
     * @return int
     */
    public function getSortedIndexSize($key)
    {
        return $this->client->zcard($key);
    }

    /**
     * Get all refs to an entity
     *
     * @param string $key Entity ref key
     * @return Ref[]
     */
    public function getRefs($key)
    {
        $members = $this->client->smembers($key);
        if (!$members) {
            return [];
        }

        $out = [];
        foreach ($members as $member) {
            $out[] = Ref::fromString($member);
        }

        return $out;
    }

    /**
     * Add a ref to an entity
     *
     * @param string $key Entity ref key
     * @param Ref    $ref Reference to add
     * @return void
     */
    public function addRef($key, Ref $ref)
    {
        $this->unit_of_work->addCommand('SetAdd', [$key, (string)$ref]);
    }

    /**
     * Remove a ref from an entity
     *
     * If the reference does not exist, no action is taken.
     *
     * @param string $key Entity ref key
     * @param Ref    $ref Reference to remove
     * @return void
     */
    public function removeRef($key, Ref $ref)
    {
        $this->unit_of_work->addCommand('SetRemove', [$key, (string)$ref]);
    }

    /**
     * Clear all refs from an entity (delete a ref list)
     *
     * @param string $key
     * @return void
     */
    public function clearRefs($key)
    {
        $this->unit_of_work->addCommand('KeyDelete', [$key]);
    }
}
