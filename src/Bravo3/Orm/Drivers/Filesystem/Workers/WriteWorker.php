<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Write raw content to the filesystem
 */
class WriteWorker extends AbstractFilesystemWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return void
     */
    public function execute(array $parameters)
    {
        $this->io_driver->write($parameters['key'], $parameters['payload']);
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['key', 'payload'];
    }
}
