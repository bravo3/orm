<?php
namespace Bravo3\Orm\Mappers\Annotation;

use Bravo3\Orm\Annotations\Column as ColumnAnnotion;
use Bravo3\Orm\Annotations\Entity as EntityAnnotion;
use Bravo3\Orm\Enum\FieldType;
use Bravo3\Orm\Exceptions\InvalidEntityException;
use Bravo3\Orm\Mappers\Metadata\Column;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Inflector\Inflector;

class AnnotationMetadataParser
{
    const ENTITY_ANNOTATION = 'Bravo3\Orm\Annotations\Entity';
    const ID_ANNOTATION     = 'Bravo3\Orm\Annotations\Id';
    const COLUMN_ANNOTATION = 'Bravo3\Orm\Annotations\Column';

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
        $this->annotion_reader = new AnnotationReader();
        $this->reflection_obj  = new \ReflectionClass($entity);
    }

    /**
     * Gets the entity table name
     *
     * @return string
     */
    public function getTableName()
    {
        /** @var EntityAnnotion $entity */
        $entity = $this->annotion_reader->getClassAnnotation($this->reflection_obj, self::ENTITY_ANNOTATION);

        if (!$entity) {
            throw new InvalidEntityException(
                'Entity "'.$this->reflection_obj->getName().'" does not contain an @Entity annotation'
            );
        }

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

    /**
     * Get all columns in the entity
     *
     * @return Column[]
     */
    public function getColumns()
    {
        $columns = [];

        $properties = $this->reflection_obj->getProperties();
        foreach ($properties as $property) {
            /** @var ColumnAnnotion $column_annotation */
            $column_annotation = $this->annotion_reader->getPropertyAnnotation($property, self::COLUMN_ANNOTATION);
            if ($column_annotation) {
                $column = $this->parseColumnAnnotation($column_annotation, $property->getName());

                if ($this->annotion_reader->getPropertyAnnotation($property, self::ID_ANNOTATION)) {
                    $column->setId(true);
                }

                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * Parse a ColumnAnnotation and return a Column object
     *
     * @param ColumnAnnotion $column_annotation
     * @param string         $name
     * @return Column
     */
    private function parseColumnAnnotation(ColumnAnnotion $column_annotation, $name)
    {
        $column = new Column($name);
        $column->setType(FieldType::memberByValue($column_annotation->type));
        $column->setName($column_annotation->name);
        $column->setGetter($column_annotation->getter);
        $column->setSetter($column_annotation->setter);
        return $column;
    }

    /**
     * Get the Entity metadata object
     *
     * @return Entity
     */
    public function getEntityMetdata()
    {
        $entity = new Entity($this->reflection_obj->getName(), $this->getTableName());
        $entity->setColumns($this->getColumns());
        return $entity;
    }
}
