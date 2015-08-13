<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Add one or more values to a multi-value index
 */
class AddIndexWorker extends AbstractFilesystemWorker
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

        $content = array_unique(array_merge($this->getJsonValue($key), $value));

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
