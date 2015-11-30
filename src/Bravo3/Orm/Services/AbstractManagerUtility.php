<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\Relationship;
use Bravo3\Orm\Services\Io\Reader;

abstract class AbstractManagerUtility
{
    /**
     * @var EntityManager
     */
    protected $entity_manager;

    /**
     * @var string
     */
    private $table_data_cache = [];

    public function __construct(EntityManager $entity_manager)
    {
        $this->entity_manager = $entity_manager;
    }

    /**
     * Get the driver belonging to the entity manager
     *
     * @return DriverInterface
     */
    protected function getDriver()
    {
        return $this->entity_manager->getDriver();
    }

    /**
     * Get the key scheme belonging to the entity manager
     *
     * @return KeySchemeInterface
     */
    protected function getKeyScheme()
    {
        return $this->entity_manager->getKeyScheme();
    }

    /**
     * Get the mapper belonging to the entity manager
     *
     * @return MapperInterface
     */
    protected function getMapper()
    {
        return $this->entity_manager->getMapper();
    }

    /**
     * Build requisite services & data if they were not provided
     *
     * @param object $entity
     * @param Entity $metadata
     * @param Reader $reader
     * @param string $local_id
     * @return array
     */
    protected function buildPrerequisites($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        if (!$metadata) {
            $metadata = $this->getMapper()->getEntityMetadata($entity);
        }

        if (!$reader) {
            $reader = new Reader($metadata, $entity);
        }

        if (!$local_id) {
            $local_id = $reader->getId();
        }

        return [$metadata, $reader, $local_id];
    }

    /**
     * Get the full ID of an entity
     *
     * @param object $entity
     * @return string
     */
    protected function getEntityId($entity)
    {
        $metadata = $this->getMapper()->getEntityMetadata(Reader::getEntityClassName($entity));
        $reader   = new Reader($metadata, $entity);
        return $reader->getId();
    }

    /**
     * Get the table name for a given entity class
     *
     * @param string $class
     * @return string
     */
    protected function getTableForClass($class)
    {
        if (!array_key_exists($class, $this->table_data_cache)) {
            $this->table_data_cache[$class] = $this->getMapper()->getEntityMetadata($class)->getTableName();
        }

        return $this->table_data_cache[$class];
    }

    /**
     * Get the relationship target table name
     *
     * @param Relationship $relationship
     * @return string
     */
    protected function getTargetTable(Relationship $relationship)
    {
        return $this->getTableForClass($relationship->getTarget());
    }

    /**
     * Get the relationship source table name
     *
     * @param Relationship $relationship
     * @return string
     */
    protected function getSourceTable(Relationship $relationship)
    {
        return $this->getTableForClass($relationship->getSource());
    }

    /**
     * Get a relationship key, resolving table names accordingly
     *
     * @param Relationship $relationship
     * @param string       $source_id
     * @return string
     */
    protected function getRelationshipKey(Relationship $relationship, $source_id)
    {
        return $this->getKeyScheme()->getRelationshipKey($relationship,
                                                         $this->getSourceTable($relationship),
                                                         $this->getTargetTable($relationship),
                                                         $source_id);
    }

    /**
     * Get a sort index key, resolving table names accordingly
     *
     * @param Relationship $relationship
     * @param              $sort_name
     * @param              $source_id
     * @return string
     */
    protected function getSortIndexKey(Relationship $relationship, $sort_name, $source_id)
    {
        return $this->getKeyScheme()->getSortIndexKey(
            $relationship,
            $this->getSourceTable($relationship),
            $this->getTargetTable($relationship),
            $sort_name,
            $source_id
        );
    }
}
