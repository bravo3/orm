<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Remove one or more values from a multi-value index
 */
class RemoveIndexWorker extends AbstractFilesystemWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return void
     */
    public function execute(array $parameters)
    {
        $key   = $parameters['key'];
        $value = $parameters['value'];

        if (!is_array($value)) {
            $value = [$value];
        }

        $content = array_diff($this->getJsonValue($key), $value);
        $this->io_driver->write($key, json_encode($content));
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['key', 'value'];
    }
}
