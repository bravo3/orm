<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Get the size of a multi-value/sorted index
 */
class GetIndexSizeWorker extends AbstractIndexWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return int
     */
    public function execute(array $parameters)
    {
        return count($this->getCurrentValue($parameters['filename']));
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
