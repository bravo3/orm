<?php
namespace Bravo3\Orm;

use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Serialisers\JsonSerialiser;
use Bravo3\Orm\Serialisers\SerialiserMap;

class EntityManager
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var SerialiserMap
     */
    protected $serialiser_map;

    public function __construct(DriverInterface $driver)
    {
        $this->driver         = $driver;
        $this->serialiser_map = new SerialiserMap();
        $this->serialiser_map->addSerialiser(new JsonSerialiser());
    }

    /**
     * Get the serialiser mappings
     *
     * @return SerialiserMap
     */
    public function getSerialiserMap()
    {
        return $this->serialiser_map;
    }

    /**
     * Set the serialiser map
     *
     * @param SerialiserMap $serialiser_map
     * @return $this
     */
    public function setSerialiserMap($serialiser_map)
    {
        $this->serialiser_map = $serialiser_map;
        return $this;
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
     * @param mixed  $query
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
