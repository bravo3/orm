<?php
namespace Bravo3\Orm\Mappers\Annotation;

use Bravo3\Orm\Annotations\AbstractRelationshipAnnotation;
use Bravo3\Orm\Annotations\AbstractSortableRelationshipAnnotation;
use Bravo3\Orm\Annotations\Column as ColumnAnnotation;
use Bravo3\Orm\Annotations\Entity as EntityAnnotation;
use Bravo3\Orm\Annotations\Index as IndexAnnotation;
use Bravo3\Orm\Annotations\ManyToMany;
use Bravo3\Orm\Annotations\ManyToOne;
use Bravo3\Orm\Annotations\OneToMany;
use Bravo3\Orm\Annotations\OneToOne;
use Bravo3\Orm\Annotations\Sortable as SortableAnnotation;
use Bravo3\Orm\Annotations\Condition as ConditionAnnotation;
use Bravo3\Orm\Enum\FieldType;
use Bravo3\Orm\Enum\RelationshipType;
use Bravo3\Orm\Exceptions\InvalidEntityException;
use Bravo3\Orm\Exceptions\UnexpectedValueException;
use Bravo3\Orm\Mappers\Metadata\Column;
use Bravo3\Orm\Mappers\Metadata\Condition;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\Index;
use Bravo3\Orm\Mappers\Metadata\Relationship;
use Bravo3\Orm\Mappers\Metadata\Sortable;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Inflector\Inflector;

class AnnotationMetadataParser
{
    const ENTITY_ANNOTATION = 'Bravo3\Orm\Annotations\Entity';
    const ID_ANNOTATION     = 'Bravo3\Orm\Annotations\Id';
    const COLUMN_ANNOTATION = 'Bravo3\Orm\Annotations\Column';
    const OTO_ANNOTATION    = 'Bravo3\Orm\Annotations\OneToOne';
    const OTM_ANNOTATION    = 'Bravo3\Orm\Annotations\OneToMany';
    const MTO_ANNOTATION    = 'Bravo3\Orm\Annotations\ManyToOne';
    const MTM_ANNOTATION    = 'Bravo3\Orm\Annotations\ManyToMany';

    /**
     * @var AnnotationReader
     */
    protected $annotation_reader;

    /**
     * @var \ReflectionClass
     */
    protected $reflection_obj;

    /**
     * @var EntityAnnotation
     */
    protected $entity_annotation = null;

    /**
     * @var string
     */
    protected $class_name;

    public function __construct($class_name)
    {
        $this->class_name        = $class_name;
        $this->annotation_reader = new AnnotationReader();
        $this->reflection_obj    = new \ReflectionClass($this->class_name);
    }

    /**
     * Gets the entity table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->getEntityAnnotation()->table ?: $this->getOrganicTableName();
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
            /** @var ColumnAnnotation $column_annotation */
            $column_annotation = $this->annotation_reader->getPropertyAnnotation($property, self::COLUMN_ANNOTATION);
            if ($column_annotation) {
                $column = $this->parseColumnAnnotation($column_annotation, $property->getName());

                if ($this->annotation_reader->getPropertyAnnotation($property, self::ID_ANNOTATION)) {
                    $column->setId(true);
                }

                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * Get all relationships in the entity
     *
     * @return Relationship[]
     */
    public function getRelationships()
    {
        $r = [];

        $properties = $this->reflection_obj->getProperties();
        foreach ($properties as $property) {
            /** @var OneToOne $oto */
            $oto = $this->annotation_reader->getPropertyAnnotation($property, self::OTO_ANNOTATION);
            if ($oto) {
                $r[] = $this->createRelationship($property->getName(), RelationshipType::ONETOONE(), $oto);
            }

            /** @var OneToMany $otm */
            $otm = $this->annotation_reader->getPropertyAnnotation($property, self::OTM_ANNOTATION);
            if ($otm) {
                $r[] = $this->createRelationship($property->getName(), RelationshipType::ONETOMANY(), $otm);
            }

            /** @var ManyToOne $mto */
            $mto = $this->annotation_reader->getPropertyAnnotation($property, self::MTO_ANNOTATION);
            if ($mto) {
                $r[] = $this->createRelationship($property->getName(), RelationshipType::MANYTOONE(), $mto);
            }

            /** @var ManyToMany $mtm */
            $mtm = $this->annotation_reader->getPropertyAnnotation($property, self::MTM_ANNOTATION);
            if ($mtm) {
                $r[] = $this->createRelationship($property->getName(), RelationshipType::MANYTOMANY(), $mtm);
            }
        }

        return $r;
    }

    /**
     * Create a relationship from an annotation
     *
     * @param string                         $name
     * @param RelationshipType               $type
     * @param AbstractRelationshipAnnotation $annotation
     * @return Relationship
     */
    private function createRelationship($name, RelationshipType $type, AbstractRelationshipAnnotation $annotation)
    {
        $target       = new AnnotationMetadataParser($annotation->target);
        $relationship = new Relationship($name, $type);

        $relationship->setSource($this->reflection_obj->getName())
                     ->setTarget($annotation->target)
                     ->setSourceTable($this->getTableName())
                     ->setTargetTable($target->getTableName())
                     ->setGetter($annotation->getter)
                     ->setSetter($annotation->setter)
                     ->setInversedBy($annotation->inversed_by);

        if (($annotation instanceof AbstractSortableRelationshipAnnotation) && $annotation->sortable_by) {
            $sortables = [];
            foreach ($annotation->sortable_by as $sortable) {
                if ($sortable instanceof SortableAnnotation) {
                    $conditions = [];
                    foreach ($sortable->conditions as $condition) {
                        if ($condition instanceof ConditionAnnotation) {
                            if ($condition->column && $condition->method) {
                                throw new UnexpectedValueException("A condition cannot be tested against both a 'column' and a 'method'");
                            }

                            if (!$condition->column && !$condition->method) {
                                throw new UnexpectedValueException("A condition must define either a 'column' or a 'method' to test against");
                            }
                            $conditions[] = new Condition(
                                $condition->column,
                                $condition->method,
                                $condition->value,
                                $condition->comparison
                            );
                        } else {
                            throw new UnexpectedValueException("Unknown condition type, must be a Condition object");
                        }
                    }
                    $sortables[] = new Sortable($sortable->column, $conditions);
                } elseif (is_string($sortable)) {
                    $sortables[] = new Sortable($sortable);
                } else {
                    throw new UnexpectedValueException("Unknown sortable type, must be a string or Sortable object");
                }
            }
            $relationship->setSortableBy($sortables);
        }

        return $relationship;
    }

    /**
     * Parse a ColumnAnnotation and return a Column object
     *
     * @param ColumnAnnotation $column_annotation
     * @param string           $name
     * @return Column
     */
    private function parseColumnAnnotation(ColumnAnnotation $column_annotation, $name)
    {
        $column = new Column($name);
        $column->setType(FieldType::memberByValue($column_annotation->type));
        $column->setName($column_annotation->name);
        $column->setGetter($column_annotation->getter);
        $column->setSetter($column_annotation->setter);
        $column->setClassName($column_annotation->class_name);
        return $column;
    }

    /**
     * Get all indices on the entity
     *
     * @return Index[]
     */
    public function getIndices()
    {
        $indices            = [];
        $annotation_indices = $this->getEntityAnnotation()->indices;
        $table_name         = $this->getTableName();

        /** @var IndexAnnotation $annotation_index */
        foreach ($annotation_indices as $annotation_index) {
            $index = new Index($table_name, $annotation_index->name);
            $index->setColumns($annotation_index->columns);
            $indices[] = $index;
        }

        return $indices;
    }

    /**
     * Get the Entity metadata object
     *
     * @return Entity
     */
    public function getEntityMetadata()
    {
        $entity = new Entity($this->reflection_obj->getName(), $this->getTableName());
        $entity->setColumns($this->getColumns());
        $entity->setRelationships($this->getRelationships());
        $entity->setIndices($this->getIndices());
        return $entity;
    }

    /**
     * Lazy-load @Entity annotation
     *
     * @return EntityAnnotation
     */
    public function getEntityAnnotation()
    {
        if ($this->entity_annotation === null) {
            /** @var EntityAnnotation $entity */
            $this->entity_annotation =
                $this->annotation_reader->getClassAnnotation($this->reflection_obj, self::ENTITY_ANNOTATION);

            if (!$this->entity_annotation) {
                throw new InvalidEntityException(
                    'Entity "'.$this->class_name.'" does not contain an @Entity annotation'
                );
            }
        }

        return $this->entity_annotation;
    }
}
