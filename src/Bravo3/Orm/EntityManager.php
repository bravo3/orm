<?php
namespace Bravo3\Orm;

use Bravo3\Orm\Drivers\DriverInterface;

class EntityManager
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Persist an entity
     *
     * @param object $entity
     * @return void
     */
    public function persist($entity)
    {

    }

    /**
     * Delete an entity
     *
     * @param string $object
     * @return void
     */
    public function delete($object)
    {

    }

    /**
     * Retrieve an entity
     *
     * @param string $class_name
     * @param mixed $query
     * @return object
     */
    public function retrieve($class_name, $query)
    {

    }

    /**
     * Execute the current unit of work
     *
     * @return void
     */
    public function flush()
    {
        $this->driver->flush();
    }

    /**
     * Purge the current unit of work, clearing any unexecuted commands
     *
     * @return void
     */
    public function purge()
    {
        $this->driver->purge();
    }
}
