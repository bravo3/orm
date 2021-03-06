<?php

namespace Bravo3\Orm\Drivers\Redis;

use Bravo3\Orm\Drivers\Common\Command;
use Bravo3\Orm\Drivers\Common\Ref;
use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Drivers\Common\StandardIdValidatorTrait;
use Bravo3\Orm\Drivers\Common\UnitOfWork;
use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Drivers\PubSubDriverInterface;
use Bravo3\Orm\Drivers\PubSubInterface;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\KeySchemes\StandardKeyScheme;
use Bravo3\Orm\Services\ScoreNormaliser;
use Bravo3\Orm\Traits\DebugTrait;
use Bravo3\Orm\Traits\PubSubTrait;
use Predis\Client;
use Predis\Command\CommandInterface;
use Predis\ClientInterface;
use Predis\Command\KeyScan;
use Predis\Command\RawCommand;
use Predis\Connection\Aggregate\MasterSlaveReplication;
use Predis\Connection\Aggregate\PredisCluster;
use Predis\Connection\Aggregate\ReplicationInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Pipeline\Pipeline;

class RedisDriver implements DriverInterface, PubSubDriverInterface
{
    use DebugTrait;
    use PubSubTrait;
    use StandardIdValidatorTrait;

    /**
     * Get/Write commands that fails will be retried again
     * upto $max_connection_retries times with delays between
     * each retry.
     *
     * @var int
     */
    protected $retry_delay_coefficient = 1.5;

    /**
     * Initial retry delay between failing $client commands.
     *
     * @var int
     */
    protected $initial_retry_delay = 200;

    /**
     * Maximum number of Predis connection retries to occur
     * if redis server doesn't respond.
     *
     * @var int
     */
    protected $max_connection_retries = 2;

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
     * @param mixed                $params
     * @param mixed                $options
     * @param mixed                $sentinel_params
     * @param ClientInterface|null $client
     */
    public function __construct(
        $params = null,
        $options = null,
        $sentinel_params = null,
        ClientInterface $client = null
    ) {
        if (null === $client) {
            $this->client = PredisClientFactory::create($params, $options, $sentinel_params);
        } else {
            $this->client = $client;
        }

        $this->unit_of_work = new UnitOfWork();
    }

    /**
     * Persist some primitive data
     *
     * @param string         $key
     * @param SerialisedData $data
     * @param int            $ttl
     *
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
     *
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
     *
     * @return SerialisedData
     */
    public function retrieve($key)
    {
        if (!$this->clientCmd('exists', [$key])) {
            throw new NotFoundException('Key "'.$key.'" does not exist');
        }

        $data = $this->clientCmd('get', [$key]);

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
            $this->clientCmd(
                'executeCommand',
                [
                    $this->getPredisCommand($command),
                ]
            );
        }
    }

    /**
     * Execute all items in the work queue in a single transaction
     */
    private function flushMulti()
    {
        $this->clientCmd(
            'pipeline',
            function ($pipe) {
                /* @var Pipeline $pipe */
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
     *
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
     *
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
     *
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
     *
     * @return string
     */
    public function getSingleValueIndex($key)
    {
        return $this->clientCmd('get', [$key]) ?: null;
    }

    /**
     * Clear all values from a set index
     *
     * @param string $key
     *
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
     *
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
     *
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
     *
     * @return string[]
     */
    public function getMultiValueIndex($key)
    {
        return $this->clientCmd('smembers', [$key]);
    }

    /**
     * Scan key-value indices and return the value of all matching keys
     *
     * @param string $key
     *
     * @return string[]
     */
    public function scan($key)
    {
        $cursor     = 0;
        $results    = [];
        $connection = $this->client->getConnection();

        do {
            if ($connection instanceof ReplicationInterface) {
                /** @var NodeConnectionInterface[] $slaves */
                $slaves = $connection->getSlaves();

                if (count($slaves) == 1) {
                    $slave = $slaves[0];
                } elseif ($slaves) {
                    $slave = $slaves[rand(0, count($slaves) - 1)];
                } else {
                    $slave = $connection->getMaster();
                }

                $cmd = new KeyScan();
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
                $set = $this->clientCmd(
                    'scan',
                    [
                        $cursor,
                        ['MATCH' => $key],
                    ]
                );
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
     *
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
     *
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
     *
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
     *
     * @return string[]
     */
    public function getSortedIndex($key, $reverse = false, $start = null, $stop = null)
    {
        if ($reverse) {
            return $this->clientCmd(
                'zrevrange',
                [
                    $key,
                    $start === null ? 0 : $start,
                    $stop === null ? -1 : $stop,
                ]
            );
        } else {
            return $this->clientCmd(
                'zrange',
                [
                    $key,
                    $start === null ? 0 : $start,
                    $stop === null ? -1 : $stop,
                ]
            );
        }
    }

    /**
     * Create a debug log
     *
     * @param string $msg
     *
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
     *
     * @return int
     */
    public function getSortedIndexSize($key)
    {
        return $this->clientCmd('zcard', [$key]);
    }

    /**
     * Get all refs to an entity
     *
     * @param string $key Entity ref key
     *
     * @return Ref[]
     */
    public function getRefs($key)
    {
        $members = $this->clientCmd('smembers', [$key]);
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
     *
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
     *
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
     *
     * @return void
     */
    public function clearRefs($key)
    {
        $this->unit_of_work->addCommand('KeyDelete', [$key]);
    }

    /**
     * Set retry delay between $client command retries.
     *
     * @param int $delay_coefficient
     *
     * @return RedisDriver
     */
    public function setRetryDelayCoefficient($delay_coefficient)
    {
        $this->retry_delay_coefficient = $delay_coefficient;

        return $this;
    }

    /**
     * Returns the current retry delay coefficient set, which
     * is the delay between each retry may happen if the requests
     * to $client fails.
     *
     * @return int
     */
    public function getRetryDelayCoefficient()
    {
        return $this->retry_delay_coefficient;
    }

    /**
     * Gets the Initial retry delay between failing $client commands.
     *
     * @return int
     */
    public function getInitialRetryDelay()
    {
        return $this->initial_retry_delay;
    }

    /**
     * Sets the Initial retry delay between failing $client commands.
     *
     * @param int $initial_retry_delay the initial retry delay
     *
     * @return self
     */
    public function setInitialRetryDelay($initial_retry_delay)
    {
        $this->initial_retry_delay = $initial_retry_delay;

        return $this;
    }

    /**
     * Gets the Maximum number of Predis connection retries to occur
     * if redis server doesn't respond.
     *
     * @return int
     */
    public function getMaxConnectionRetries()
    {
        return $this->max_connection_retries;
    }

    /**
     * Sets the Maximum number of Predis connection retries to occur
     * if redis server doesn't respond.
     *
     * @param int $max_connection_retries the max connection retries
     *
     * @return self
     */
    public function setMaxConnectionRetries($max_connection_retries)
    {
        $this->max_connection_retries = $max_connection_retries;

        return $this;
    }

    /**
     * A wrapper function to wrap Redis commands which go to Predis Client
     * in order to replay them if server connection issues occur.
     *
     * @param string         $cmd function to call against the Predis client
     * @param array|callable $params
     * @param int            $retry_iteration
     *
     * @return mixed
     */
    private function clientCmd($cmd, $params, $retry_iteration = 0)
    {
        try {
            $delay = $this->calculateRetryDelay($retry_iteration);
            if ($delay > 0) {
                usleep($delay);
            }

            if (is_callable($params)) {
                return call_user_func([$this->client, $cmd], $params);
            } else {
                return call_user_func_array([$this->client, $cmd], $params);
            }
        } catch (\Exception $e) {
            if (++$retry_iteration <= $this->max_connection_retries) {
                return $this->clientCmd($cmd, $params, $retry_iteration);
            }

            throw $e;
        }
    }

    /**
     * Calculate a retry delay to be used in the clientCmd() function
     * to delay failing operations to $client.
     *
     * Note: Calculations are done in microseconds.
     *
     * @param  int $retry_iteration
     * @return int
     */
    public function calculateRetryDelay($retry_iteration)
    {
        if ($retry_iteration > 0) {
            $retry_delay = $retry_iteration * $this->initial_retry_delay * $this->retry_delay_coefficient;

            // Since $retry_delay is in milliseconds multiply it by 1000 to return microseconds
            return $retry_delay * 1000;
        } else {
            return 0;
        }
    }

    /**
     * Checks if the driver supports publish/subscribe pattern.
     *
     * @return bool
     */
    public function isPubSubSupported()
    {
        if ((float) $this->client->getProfile()->getVersion() >= 2.8) {
            return true;
        }

        return false;
    }


    /**
     * Start listening to subscribed channels of the Redis PubSub mechanism.
     * Add a callback to a particular subscription channel.
     *
     * @param callable $callback
     * @return void
     */
    public function listenToPubSub(callable $callback)
    {
        while (1) {

            $command = RawCommand::create(
                'PSUBSCRIBE',
                sprintf('%s-%s', $this->pubsub_channel_prefix, '*')
            );

            $this->client->executeCommand($command);

            if ($this->client->getConnection() instanceof MasterSlaveReplication) {
                $payload = $this->client->getConnection()->getConnection($command)->read();
            } else {
                $payload = $this->client->getConnection()->read();
            }

            $channel = ltrim($payload[2], sprintf('%s%s', $this->pubsub_channel_prefix, '-'));
            $message = base64_decode($payload[3]);

            call_user_func(
                $callback,
                [
                    'channel' => $channel,
                    'message' => $message,
                ]
            );
        }
    }

    /**
     * Uses Redis PubSub implementation to push messages to the channel specified.
     *
     * @param string $channel
     * @param string $message
     * @return int
     */
    public function publishMessage($channel, $message)
    {
        return $this->client->publish(
            sprintf('%s-%s', $this->pubsub_channel_prefix, $channel),
            base64_encode($message)
        );
    }
}
