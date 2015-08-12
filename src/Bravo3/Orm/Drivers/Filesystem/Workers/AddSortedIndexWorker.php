<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Add one or more values to a sorted index
 */
class AddSortedIndexWorker extends AbstractFilesystemWorker
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
        $score = $parameters['score'];

        $content = $this->getJsonValue($key);

        // Search existing content
        foreach ($content as $index => $item_data) {
            list(, $item) = $item_data;

            if ($item == $value) {
                // Overwrite existing value
                $content[$index] = [$score, $item];
                $this->sortAndWrite($content, $key);
                return;
            }
        }

        // Append new value
        $content[] = [$score, $value];
        $this->sortAndWrite($content, $key);
    }

    /**
     * Sort the data and write it to the filesystem
     *
     * @param array  $content
     * @param string $key
     */
    protected function sortAndWrite(array $content, $key)
    {
        usort(
            $content,
            function (array $a, array $b) {
                list($a_score,) = $a;
                list($b_score,) = $b;

                if ($a_score == $b_score) {
                    return 0;
                } else {
                    return $a_score < $b_score ? -1 : 1;
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
        return ['key', 'value', 'score'];
    }
}
