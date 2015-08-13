<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

use Bravo3\Orm\Drivers\Common\WorkerInterface;

/**
 * Delete an object from the filesystem
 */
class DeleteWorker extends AbstractFilesystemWorker implements WorkerInterface
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return void
     */
    public function execute(array $parameters)
    {
        $this->io_driver->delete($parameters['key']);
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['key'];
    }
}
