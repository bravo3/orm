<?php
namespace Bravo3\Orm\Services\Io;

use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Exceptions\InvalidEntityException;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Proxy\OrmProxyInterface;

/**
 * Responsible for reading values from an entity, using the metadata provided
 */
class Reader
{
    /**
     * @var Entity
     */
    protected $metadata;

    /**
     * @var object
     */
    protected $entity;

    /**
     * @param Entity $metadata
     * @param object $entity
     */
    public function __construct(Entity $metadata, $entity)
    {
        $this->metadata = $metadata;
        $this->entity   = $entity;
    }

    /**
     * Get a property value
     *
     * @param string $name
     * @return mixed
     */
    public function getPropertyValue($name)
    {
        if ($column = $this->metadata->getColumnByProperty($name)) {
            $getter = $column->getGetter();
        } elseif ($relationship = $this->metadata->getRelationshipByName($name)) {
            $getter = $relationship->getGetter();
        } else {
            throw new InvalidArgumentException("No column/relationship found for property '".$name."'");
        }

        return $this->entity->$getter();
    }

    /**
     * Get an ID for the given data
     *
     * @return string
     */
    public function getId()
    {
        $values = [];

        foreach ($this->metadata->getIdColumns() as $column) {
            $values[] = $this->getPropertyValue($column->getProperty());
        }

        if (!count($values)) {
            throw new InvalidEntityException('Entity "'.$this->metadata->getClassName().'" has no ID column');
        }

        return implode(Entity::ID_DELIMITER, $values);
    }

    /**
     * Get the true class name of the entity, resolving any proxy wrappers
     *
     * @param object $entity
     * @return string
     */
    public static function getEntityClassName($entity)
    {
        if ($entity instanceof OrmProxyInterface) {
            return get_parent_class($entity);
        } else {
            return get_class($entity);
        }
    }
}
