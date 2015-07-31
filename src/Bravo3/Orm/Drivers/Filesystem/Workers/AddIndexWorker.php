<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Add one or more values to a multi-value index
 */
class AddIndexWorker extends AbstractIndexWorker
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

        $content = $this->getCurrentValue($filename);
        foreach ($value as $item) {
            if (!in_array($item, $content)) {
                $content[] = $item;
            }
        }

        $this->writeData($filename, json_encode($content), $parameters['umask']);
    }
}
