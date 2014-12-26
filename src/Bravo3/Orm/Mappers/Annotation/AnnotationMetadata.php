<?php
namespace Bravo3\Orm\Mappers\Annotation;

use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Mappers\MetadataInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Inflector\Inflector;

class AnnotationMetadata implements MetadataInterface
{
    const ENTITY_ANNOTATION  = 'Bravo3\Orm\Annotations\Entity';
    const ERR_NOT_AN_OBJECT  = "Entity must be an object";
    const ERR_INVALID_ENTITY = "Object is not a valid entity";

    /**
     * @var AnnotationReader
     */
    protected $annotion_reader;

    /**
     * @var \ReflectionClass
     */
    protected $reflection_obj;

    public function __construct($entity)
    {
        if (!is_object($entity)) {
            throw new InvalidArgumentException(self::ERR_NOT_AN_OBJECT);
        }

        $this->annotion_reader = new AnnotationReader();
        $this->reflection_obj  = new \ReflectionClass($entity);

        if (!$this->isValidEntity()) {
            throw new InvalidArgumentException(self::ERR_INVALID_ENTITY);
        }
    }

    /**
     * Check if we meet the entity prerequisits:
     * - must have 'Entity' annotation
     * - must have an ID field
     *
     * @return bool
     */
    private function isValidEntity()
    {
        return (bool)$this->annotion_reader->getClassAnnotation($this->reflection_obj, self::ENTITY_ANNOTATION) &&
               $this->hasPrimaryKey();
    }

    /**
     * Check we have a valid primary key field
     *
     * @return bool
     */
    private function hasPrimaryKey()
    {
        // TODO: implement me
        return true;
    }

    /**
     * Get the entities ID
     *
     * @return string
     */
    public function getEntityId()
    {
        // TODO: Implement getEntityId() method.
    }

    /**
     * Gets the entity table name
     *
     * @return string
     */
    public function getTableName()
    {
        $entity = $this->annotion_reader->getClassAnnotation($this->reflection_obj, self::ENTITY_ANNOTATION);
        return $entity->table ?: $this->getOrganicTableName();
    }

    /**
     * Get the table name by inflecting the class name
     *
     * @return string
     */
    private function getOrganicTableName()
    {
        return Inflector::tableize(basename(str_replace('\\', '/', $this->reflection_obj->getName())));
    }
}
