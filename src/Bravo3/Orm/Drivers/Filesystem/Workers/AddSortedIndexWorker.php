<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Add one or more values to a sorted index
 */
class AddSortedIndexWorker extends AbstractIndexWorker
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
        $score    = $parameters['score'];

        $content = $this->getCurrentValue($filename);

        // Search existing content
        foreach ($content as $index => $item_data) {
            list(, $item) = $item_data;

            if ($item == $value) {
                // Overwrite existing value
                $content[$index] = [$score, $item];
                $this->sortAndWrite($content, $filename, $parameters['umask']);
                return;
            }
        }

        // Append new value
        $content[] = [$score, $value];
        $this->sortAndWrite($content, $filename, $parameters['umask']);
    }

    /**
     * Sort the data and write it to the filesystem
     *
     * @param array  $content
     * @param string $filename
     * @param int    $umask
     */
    protected function sortAndWrite(array $content, $filename, $umask)
    {
        usort(
            $content,
            function (array $a, array $b) {
                list($a_score,) = $a;
                list($b_score,) = $b;

                if ($a_score == $b_score) {
                    return 0;
                }

                return $a_score < $b_score ? -1 : 1;
            }
        );

        $this->writeData($filename, json_encode($content), $umask);
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['filename', 'value', 'score', 'umask'];
    }
}
