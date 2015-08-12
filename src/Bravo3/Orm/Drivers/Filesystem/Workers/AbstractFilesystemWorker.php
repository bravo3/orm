<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

use Bravo3\Orm\Drivers\Common\WorkerInterface;
use Bravo3\Orm\Drivers\Filesystem\Io\IoDriverInterface;
use Bravo3\Orm\Exceptions\InvalidArgumentException;

/**
 * Generic I/O worker, all workers extending this abstraction require an IoDriverInterface to read and write raw data
 */
abstract class AbstractFilesystemWorker implements WorkerInterface
{
    /**
     * @var IoDriverInterface
     */
    protected $io_driver;

    /**
     * Construct the worker, must contain an IoDriverInterface in the $data array with key 'io_driver'
     *
     * @param array $data
     */
    public function __construct($data)
    {
        if (!is_array($data) || !array_key_exists('io_driver', $data)) {
            throw new InvalidArgumentException("Filesystem workers must include an IO driver");
        }

        $this->io_driver = $data['io_driver'];

        if (!($this->io_driver instanceof IoDriverInterface)) {
            throw new InvalidArgumentException("io_driver must be an instance of IoDriverInterface");
        }
    }

    /**
     * Get the current value of an index
     *
     * @param string $key
     * @return array
     */
    protected function getJsonValue($key)
    {
        if ($this->io_driver->exists($key)) {
            return json_decode($this->io_driver->read($key), true);
        } else {
            return [];
        }
    }
}
