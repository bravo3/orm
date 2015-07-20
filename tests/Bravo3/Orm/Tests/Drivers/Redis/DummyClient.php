<?php
namespace Bravo3\Orm\Tests\Drivers\Redis;

use Predis\ClientInterface;
use Predis\Command\CommandInterface;

class DummyClient implements ClientInterface
{
    /**
     * Returns the server profile used by the client.
     *
     * @return ProfileInterface
     */
    public function getProfile()
    {

    }

    /**
     * Returns the client options specified upon initialization.
     *
     * @return OptionsInterface
     */
    public function getOptions()
    {

    }

    /**
     * Opens the underlying connection to the server.
     */
    public function connect()
    {

    }

    /**
     * Closes the underlying connection from the server.
     */
    public function disconnect()
    {

    }

    /**
     * Returns the underlying connection instance.
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {

    }

    /**
     * Creates a new instance of the specified Redis command.
     *
     * @param string $method    Command ID.
     * @param array  $arguments Arguments for the command.
     *
     * @return CommandInterface
     */
    public function createCommand($method, $arguments = array())
    {

    }

    /**
     * Executes the specified Redis command.
     *
     * @param CommandInterface $command Command instance.
     *
     * @return mixed
     */
    public function executeCommand(CommandInterface $command)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $arguments)
    {

    }

    /**
     * Empty function to mock calls to Predis/Client
     *
     * @param  string $key
     * @return boolean
     */
    public function exists($key)
    {

    }

    /**
     * Empty function to mock calls to Predis/Client
     *
     * @param  string $key
     * @return string
     */
    public function get($key)
    {
        print_r($key);
    }
}
