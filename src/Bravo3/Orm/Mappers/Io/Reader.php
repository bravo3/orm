<?php
namespace Bravo3\Orm\Mappers\Io;

use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Exceptions\InvalidEntityException;
use Bravo3\Orm\Mappers\Metadata\Entity;

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
        $column = $this->metadata->getColumnByProperty($name);
        if (!$column) {
            throw new InvalidArgumentException("No column found for property '".$name."'");
        }

        $getter = $column->getGetter();
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

}
