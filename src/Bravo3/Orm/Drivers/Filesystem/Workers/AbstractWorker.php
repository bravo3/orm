<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

use Bravo3\Orm\Drivers\Common\WorkerInterface;

abstract class AbstractWorker implements WorkerInterface
{
    /**
     * Write data to the filesystem
     *
     * @param $filename
     * @param $data
     * @param $umask
     */
    protected function writeData($filename, $data, $umask)
    {
        if (file_exists($filename)) {
            // Write file, maintaining permissions
            file_put_contents($filename, $data);
        } else {
            // Write file and set the requested umask
            file_put_contents($filename, $data);
            chmod($filename, $umask);
        }
    }

    /**
     * Read data from the filesystem, returning null if the file is not readable
     *
     * @param string $filename
     * @return string|null
     */
    protected function readData($filename)
    {
        if (is_readable($filename) && !is_dir($filename)) {
            return file_get_contents($filename);
        } else {
            return null;
        }
    }
}
