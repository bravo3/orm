<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Write raw content to the filesystem
 */
class WriteWorker extends AbstractWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return void
     */
    public function execute(array $parameters)
    {
        $this->writeData($parameters['filename'], $parameters['payload'], $parameters['umask']);
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['filename', 'payload', 'umask'];
    }
}
