<?php
namespace Bravo3\Orm\Drivers\Common;

class UnitOfWork
{
    /**
     * @var Command[]
     */
    protected $commands;

    public function __construct()
    {
        $this->commands = [];
    }

    /**
     * Add a command to the queue
     *
     * @param Command $command
     * @return $this
     */
    public function queueCommand(Command $command)
    {
        $this->commands[] = $command;
        return $this;
    }

    /**
     * Create a new command and add it to the queue
     *
     * @param string $name
     * @param array  $arguments
     * @return $this
     */
    public function addCommand($name, array $arguments = [])
    {
        $this->commands[] = new Command($name, $arguments);
        return $this;
    }

    /**
     * Get the next command and remove it from the queue
     *
     * @return Command|null
     */
    public function getNextCommand()
    {
        return array_shift($this->commands);
    }

    /**
     * Purge the command queue
     *
     * @return $this
     */
    public function purge()
    {
        $this->commands = [];
        return $this;
    }

    /**
     * Get all commands on the queue
     *
     * @return Command[]
     */
    public function getCommands()
    {
        return $this->commands;
    }
}
