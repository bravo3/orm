<?php
namespace Bravo3\Orm\Drivers\Filesystem\Io;

interface IoDriverInterface
{
    /**
     * Write raw data to the interface
     *
     * @param string $key
     * @param string $data
     */
    public function write($key, $data);

    /**
     * Read raw data from the interface, returning null if the file is not readable
     *
     * @param string $key
     * @return string|null
     */
    public function read($key);

    /**
     * Delete a key
     *
     * @param string $key
     */
    public function delete($key);

    /**
     * Check if a key exists on the interface
     *
     * @param string $key
     * @return bool
     */
    public function exists($key);

    /**
     * Get a list of keys on the interface
     *
     * @param string $base   Base path to list all keys from
     * @param string $filter Key filter, @see docs/Queries.md - Wildcards
     * @return string[]
     */
    public function scan($base, $filter);
}
