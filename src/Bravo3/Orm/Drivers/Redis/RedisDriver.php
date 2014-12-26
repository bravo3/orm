<?php
namespace Bravo3\Orm\Drivers\Redis;

use Bravo3\Orm\Drivers\Common\Command;
use Bravo3\Orm\Drivers\Common\UnitOfWork;
use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\KeySchemes\StandardKeyScheme;
use Predis\Client;
use Predis\Command\CommandInterface;
use Predis\Transaction\MultiExec;

class RedisDriver implements DriverInterface
{
    /**
     * @var UnitOfWork
     */
    protected $unit_of_work;

    /**
     * @var Client
     */
    protected $client;

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
     * @param string $key
     * @param string $data
     * @return void
     */
    public function persist($key, $data)
    {
        $this->unit_of_work->addCommand('StringSet', [$key, $data]);
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
     * @return string
     */
    public function retrieve($key)
    {
        if (!$this->client->exists($key)) {
            throw new NotFoundException('Key "'.$key.'" does not exist');
        }

        return $this->client->get($key);
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
        $this->client->executeCommand($this->getPredisCommand($command));
    }

    /**
     * Execute all items in the work queue in a single transaction
     */
    private function flushMulti()
    {
        $multi = new MultiExec($this->client);
        while ($command = $this->unit_of_work->getWork()) {
            $multi->executeCommand($this->getPredisCommand($command));
        }
        $multi->execute();
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
}
