<?php
namespace Bravo3\Orm;

use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Enum\RelationshipType;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\Mappers\Io\Reader;
use Bravo3\Orm\Mappers\Io\Writer;
use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Proxy\OrmProxyInterface;
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
        $metadata   = $this->mapper->getEntityMetadata(get_class($entity));
        $serialiser = $this->getSerialiserMap()->getDefaultSerialiser();
        $reader     = new Reader($metadata, $entity);
        $id         = $reader->getId();

        $this->driver->persist(
            $this->key_scheme->getEntityKey($metadata->getTableName(), $id),
            $serialiser->serialise($metadata, $entity)
        );

        $relationships = $metadata->getRelationships();
        $is_proxy      = $entity instanceof OrmProxyInterface;


        foreach ($relationships as $relationship) {
            // If the entity is not a proxy (i.e. a new entity) we still must allow for the scenario in which a new
            // entity is created over the top of an existing, as such, we still need to check every relationship
            if ($is_proxy) {
                /** @var OrmProxyInterface $entity */
                if (!$entity->isRelativeModified($relationship->getName())) {
                    // Only if we have a proxy object and the relationship has not been modified, can we skip the
                    // relationship update
                    continue;
                }
            }

            $key = $this->key_scheme->getRelationshipKey($relationship, $id);

            switch ($relationship->getRelationshipType()) {
                default:
                case RelationshipType::ONETOONE():
                case RelationshipType::MANYTOONE():
                    // Index is a single-value key
                    $this->setSingleValueRelationship($key, $reader->getPropertyValue($relationship->getName()));
                    break;
                case RelationshipType::ONETOMANY():
                case RelationshipType::MANYTOMANY():
                    // Index is a multi-value key (list)

                    break;
            }

            if ($relationship->getInversedBy()) {
                // TODO: update inverse relationship - need to know the former value
                // Remove local entity from previous foreign entity
                // ..

                // Add local entity to new foreign entity
                // ..
            }
        }
    }

    private function setSingleValueRelationship($key, $foreign_entity)
    {
        if ($foreign_entity) {
            $rel_metadata = $this->mapper->getEntityMetadata(get_class($foreign_entity));
            $rel_reader   = new Reader($rel_metadata, $foreign_entity);
            $value        = $rel_reader->getId();
        } else {
            $value = null;
        }

        $this->driver->setSingleValueIndex($key, $value);
    }

    /**
     * Delete an entity
     *
     * @param string $entity
     * @return void
     */
    public function delete($entity)
    {
        $metadata = $this->mapper->getEntityMetadata(get_class($entity));
        $reader   = new Reader($metadata, $entity);

        $this->driver->delete(
            $this->key_scheme->getEntityKey($metadata->getTableName(), $reader->getId())
        );
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
        $metadata = $this->mapper->getEntityMetadata($class_name);

        $serialised_data = $this->driver->retrieve(
            $this->key_scheme->getEntityKey($metadata->getTableName(), $id)
        );

        $writer = new Writer($metadata, $serialised_data, $this);
        return $writer->getProxy();
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
