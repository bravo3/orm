<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

abstract class AbstractIndexWorker extends AbstractWorker
{
    /**
     * Default requirements for index workers
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['filename', 'value', 'umask'];
    }

    /**
     * Get the current value of an index
     *
     * @param string $filename
     * @return array
     */
    protected function getCurrentValue($filename)
    {
        if (file_exists($filename)) {
            return json_decode(file_get_contents($filename), true);
        } else {
            return [];
        }
    }
}
