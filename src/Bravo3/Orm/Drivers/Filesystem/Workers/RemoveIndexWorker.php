<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Remove one or more values from a multi-value index
 */
class RemoveIndexWorker extends AbstractIndexWorker
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
        $value    = $parameters['value'];

        if (!is_array($value)) {
            $value = [$value];
        }

        $content = array_diff($this->getCurrentValue($filename), $value);
        $this->writeData($filename, json_encode($content), $parameters['umask']);
    }
}
