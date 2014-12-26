<?php
namespace Bravo3\Orm;

use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Serialisers\JsonSerialiser;
use Bravo3\Orm\Serialisers\SerialiserMap;

class EntityManager
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var MapperInterface
     */
    protected $mapper;

    /**
     * @var SerialiserMap
     */
    protected $serialiser_map;

    /**
     * @var KeySchemeInterface
     */
    protected $key_scheme;

    public function __construct(
        DriverInterface $driver,
        MapperInterface $mapper,
        SerialiserMap $serialiser_map = null,
        KeySchemeInterface $key_scheme = null
    ) {
        $this->driver     = $driver;
        $this->mapper     = $mapper;
        $this->key_scheme = $key_scheme ?: $driver->getPreferredKeyScheme();

        if ($serialiser_map) {
            $this->serialiser_map = $serialiser_map;
        } else {
            $this->serialiser_map = new SerialiserMap();
            $this->serialiser_map->addSerialiser(new JsonSerialiser());
        }
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
        $metadata = $this->mapper->getEntityMetadata($entity);
        $this->driver->persist(
            $this->key_scheme->getEntityKey($metadata->getTableName(), $metadata->getEntityId()),
            $this->getSerialiserMap()->getDefaultSerialiser()->serialise($entity)
        );
    }

    /**
     * Delete an entity
     *
     * @param string $entity
     * @return void
     */
    public function delete($entity)
    {
        $metadata = $this->mapper->getEntityMetadata($entity);
        $this->driver->delete($this->key_scheme->getEntityKey($metadata->getTableName(), $metadata->getEntityId()));
    }

    /**
     * Retrieve an entity
     *
     * @param string $class_name
     * @param string $id
     * @return object
     */
    public function retrieve($class_name, $id)
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
