<?php
namespace Bravo3\Orm\Drivers\Redis;

use Bravo3\Orm\Drivers\Common\UnitOfWork;
use Bravo3\Orm\Drivers\DriverInterface;

class RedisDriver implements DriverInterface
{
    /**
     * @var UnitOfWork
     */
    protected $unit_of_work;

    public function __construct()
    {
        $this->unit_of_work = new UnitOfWork();
    }

    /**
     * Persist some primitive data
     *
     * @param string $key
     * @param string $data
     * @return void
     */
    public function persist($key, $data)
    {
        // TODO: Implement persist() method.
    }

    /**
     * Delete a primitive document
     *
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        // TODO: Implement delete() method.
    }

    /**
     * Retrieve an object
     *
     * @param string $key
     * @return string
     */
    public function retrieve($key)
    {
        // TODO: Implement retrieve() method.
    }

    /**
     * Execute the current unit of work
     *
     * @return void
     */
    public function flush()
    {
        // TODO: Implement flush() method.
    }

    /**
     * Purge the current unit of work, clearing any unexecuted commands
     *
     * @return void
     */
    public function purge()
    {
        $this->unit_of_work->purge();
    }
}
