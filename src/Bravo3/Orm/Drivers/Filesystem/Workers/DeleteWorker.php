<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

use Bravo3\Orm\Drivers\Common\WorkerInterface;

/**
 * Delete an object from the filesystem
 */
class DeleteWorker implements WorkerInterface
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return void
     */
    public function execute(array $parameters)
    {
        $filename = $parameters['filename'];

        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['filename'];
    }
}
