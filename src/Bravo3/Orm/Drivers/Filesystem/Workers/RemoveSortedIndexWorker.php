<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Remove one or more values from a sorted index
 */
class RemoveSortedIndexWorker extends AbstractFilesystemWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return void
     */
    public function execute(array $parameters)
    {
        $key     = $parameters['key'];
        $value   = $parameters['value'];
        $content = $this->getJsonValue($key);

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
