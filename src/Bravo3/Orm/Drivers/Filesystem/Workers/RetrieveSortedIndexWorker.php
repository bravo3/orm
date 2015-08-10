<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

/**
 * Retrieve the value of a multi-value index
 */
class RetrieveSortedIndexWorker extends AbstractIndexWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return array
     */
    public function execute(array $parameters)
    {
        $current = $this->getCurrentValue($parameters['filename']);

        if ($parameters['reverse']) {
            $current = array_reverse($current);
        }

        $start = $parameters['start'];
        $stop  = $parameters['stop'];
        $count = count($current);

        // Negative start/stop index
        if ($start < 0) {
            $start = $count + $start - 1;
        }

        if ($stop < 0) {
            $stop = $count + $stop;
        }

        // Slice the result if required
        if ($start || $stop) {
            // If we have a start and stop index, convert $stop to a length (it is a length if there is no start)
            if ($start && $stop) {
                $stop -= $start;
            }

            if ($start === null) {
                $start = 0;
            }

            $current = array_slice($current, $start, $stop + 1);
        }

        // We need an array of values only, ditch the score data
        $out = [];
        foreach ($current as $item) {
            $out[] = $item[1];
        }

        return $out;
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['filename', 'reverse', 'start', 'stop'];
    }
}
