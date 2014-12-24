<?php
namespace Bravo3\Orm\Drivers;

interface DriverInterface
{
    /**
     * Persist some primitive data
     *
     * @param string $key
     * @param string $data
     * @return void
     */
    public function persist($key, $data);

    /**
     * Delete a primitive document
     *
     * @param string $key
     * @return void
     */
    public function delete($key);

    /**
     * Retrieve an object
     *
     * @param string $key
     * @return string
     */
    public function retrieve($key);

    /**
     * Execute the current unit of work
     *
     * @return void
     */
    public function flush();

    /**
     * Purge the current unit of work, clearing any unexecuted commands
     *
     * @return void
     */
    public function purge();
}
