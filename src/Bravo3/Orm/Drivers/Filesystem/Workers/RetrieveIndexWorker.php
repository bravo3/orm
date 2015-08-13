<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Retrieve the value of a multi-value index
 */
class RetrieveIndexWorker extends AbstractFilesystemWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return array
     */
    public function execute(array $parameters)
    {
        return $this->getJsonValue($parameters['key']);
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
