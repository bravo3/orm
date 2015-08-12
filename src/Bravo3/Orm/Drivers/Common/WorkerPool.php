<?php
namespace Bravo3\Orm\Drivers\Common;

use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Exceptions\UnexpectedValueException;

class WorkerPool
{
    /**
     * @var WorkerInterface[]
     */
    protected $workers;

    /**
     * @var mixed
     */
    protected $worker_data;

    /**
     * Creates a lazy-loading worker pool
     *
     * If any of the workers passed in $workers are a string, it is considered the class name for a WorkerInterface
     * that is lazy-loaded on demand.
     *
     * @param WorkerInterface[]|string[] $workers     Preload the pool with some workers
     * @param mixed                      $worker_data Data to pass to worker constructors
     */
    public function __construct(array $workers = [], $worker_data = null)
    {
        $this->workers     = $workers;
        $this->worker_data = $worker_data;
    }

    /**
     * Adds a worker to the pool
     *
     * @param string                 $name   Command name the worker fulfills
     * @param WorkerInterface|string $worker Worker object or class name
     * @return $this
     */
    public function addWorker($name, $worker)
    {
        $this->workers[$name] = $worker;
        return $this;
    }

    /**
     * Check if we have a worker for a given command
     *
     * @param string $name
     * @return bool
     */
    public function hasWorker($name)
    {
        return array_key_exists($name, $this->workers);
    }

    /**
     * Have the appropriate worker execute a command
     *
     * @param Command $command
     * @return mixed
     */
    public function execute(Command $command)
    {
        $worker = $this->getWorker($command->getName());
        $args   = $command->getArguments();

        foreach ($worker->getRequiredParameters() as $param) {
            if (!array_key_exists($param, $args)) {
                throw new InvalidArgumentException(
                    "Command '".$command->getName()."' requires parameter '".$param."''"
                );
            }
        }

        return $worker->execute($args);
    }

    /**
     * Get a worker, lazy-loading as required
     *
     * @param $name
     * @return WorkerInterface
     */
    protected function getWorker($name)
    {
        if (!$this->hasWorker($name)) {
            throw new UnexpectedValueException("Command '".$name."' does not have a worker");
        }

        $worker = $this->workers[$name];

        if (is_string($worker)) {
            $worker               = new $worker($this->worker_data);
            $this->workers[$name] = $worker;
        }

        if (!($worker instanceof WorkerInterface)) {
            throw new UnexpectedValueException("Worker '".$name."' is not a WorkerInterface");
        }

        return $worker;
    }
}
