<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Retrieve the value of a multi-value index
 */
class RetrieveIndexWorker extends AbstractIndexWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return array
     */
    public function execute(array $parameters)
    {
        return $this->getCurrentValue($parameters['filename']);
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
