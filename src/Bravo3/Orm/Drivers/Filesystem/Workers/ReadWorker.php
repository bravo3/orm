<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Read the raw contents of an object, if the object does not exist, null will be returned
 */
class ReadWorker extends AbstractWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return mixed
     */
    public function execute(array $parameters)
    {
        return $this->readData($parameters['filename']);
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
