<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Performs a key scan
 */
class ScanWorker extends AbstractFilesystemWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return string[]
     */
    public function execute(array $parameters)
    {
        $query = $parameters['query'];

        return $this->io_driver->scan(dirname($query), basename($query));
    }


    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['query'];
    }
}
