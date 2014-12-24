<?php
namespace Bravo3\Orm\Mappers\Annotation;

use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Mappers\MetadataInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Inflector\Inflector;

class AnnotationMetadata implements MetadataInterface
{
    const ANNOTAION_ENTITY = 'Bravo3\Orm\Annotations\Entity';

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
            throw new InvalidArgumentException("Entity must be an object");
        }

        $this->reflection_obj = new \ReflectionClass($entity);
    }

    /**
     * Gets the entity table name
     *
     * @return string
     */
    public function getTableName()
    {
        $entity = $this->annotion_reader->getClassAnnotation($this->reflection_obj, self::ANNOTAION_ENTITY);
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
