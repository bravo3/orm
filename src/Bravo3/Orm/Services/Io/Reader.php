<?php
namespace Bravo3\Orm\Services\Io;

use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Exceptions\InvalidEntityException;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\UniqueIndex;
use Bravo3\Orm\Proxy\OrmProxyInterface;

/**
 * Responsible for reading values from an entity, using the metadata provided
 */
class Reader
{
    /**
     * Use this string to join all ID columns in a table to return a single string key.
     * This string is not configurable and must never change!
     */
    const ID_DELIMITER = '.';

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
        if (!$entity || !is_object($entity)) {
            throw new InvalidArgumentException("Entity is not an object");
        }

        $this->metadata = $metadata;
        $this->entity   = $entity;
    }

    /**
     * Get the value of a property on the entity
     *
     * @param string $name
     * @return mixed
     */
    public function getPropertyValue($name)
    {
        if ($column = $this->metadata->getColumnByProperty($name)) {
            $getter = $column->getGetter();

            if (!method_exists($this->entity, $getter)) {
                throw new InvalidArgumentException("Getter '".$getter."' does not exist for property '".$name."'");
            }

            return $this->entity->$getter();
        } elseif ($relationship = $this->metadata->getRelationshipByName($name)) {
            $getter = $relationship->getGetter();

            if (!method_exists($this->entity, $getter)) {
                throw new InvalidArgumentException("Getter '".$getter."' does not exist for relationship '".$name."'");
            }

            return $this->entity->$getter();
        } else {
            throw new InvalidArgumentException("No column/relationship found for property '".$name."'");
        }
    }

    /**
     * Get the value of a method on the entity
     *
     * @param $name
     * @return mixed
     */
    public function getMethodValue($name)
    {
        if (method_exists(get_class($this->entity), $name)) {
            return $this->entity->$name();
        }

        throw new InvalidArgumentException("The method '".$name."' does not exist on ".get_class($this->entity));
    }

    /**
     * Get the value of an index
     *
     * @param UniqueIndex $index
     * @return string
     */
    public function getIndexValue(UniqueIndex $index)
    {
        $values = [];

        foreach ($index->getColumns() as $column) {
            $values[] = (string)$this->getPropertyValue($column);
        }

        foreach ($index->getMethods() as $method) {
            $values[] = (string)$this->getMethodValue($method);
        }

        return implode(self::ID_DELIMITER, $values);
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

        return implode(self::ID_DELIMITER, $values);
    }

    /**
     * Get the true class name of the entity, resolving any proxy wrappers
     *
     * @param object|string $entity
     * @return string
     */
    public static function getEntityClassName($entity)
    {
        if ($entity instanceof OrmProxyInterface) {
            return get_parent_class($entity);
        } elseif (is_object($entity)) {
            return get_class($entity);
        } elseif (is_string($entity)) {
            return $entity;
        } else {
            return null;
        }
    }
}
