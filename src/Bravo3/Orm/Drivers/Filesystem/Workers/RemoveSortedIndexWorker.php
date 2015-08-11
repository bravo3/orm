<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Remove one or more values from a sorted index
 */
class RemoveSortedIndexWorker extends AbstractIndexWorker
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
        $content  = $this->getCurrentValue($filename);

        if (!is_array($value)) {
            $value = [$value];
        }

        $payload = [];
        foreach ($value as $item) {
            $payload[] = [null, $item];
        }

        $content = array_udiff(
            $content,
            $payload,
            function (array $a, array $b) {
                $val_a = $a[1];
                $val_b = $b[1];

                if ($val_a == $val_b) {
                    return 0;
                } else {
                    return $val_a < $val_b ? -1 : 1;
                }
            }
        );

        $this->writeData($filename, json_encode($content), $parameters['umask']);
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['filename', 'value', 'umask'];
    }
}
