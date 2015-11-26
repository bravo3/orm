<?php
namespace Bravo3\Orm\Mappers\Annotation;

use Bravo3\Orm\Annotations\AbstractRelationshipAnnotation;
use Bravo3\Orm\Annotations\AbstractSortableRelationshipAnnotation;
use Bravo3\Orm\Annotations\Column as ColumnAnnotation;
use Bravo3\Orm\Annotations\Condition as ConditionAnnotation;
use Bravo3\Orm\Annotations\Entity as EntityAnnotation;
use Bravo3\Orm\Annotations\Index as IndexAnnotation;
use Bravo3\Orm\Annotations\ManyToMany;
use Bravo3\Orm\Annotations\ManyToOne;
use Bravo3\Orm\Annotations\OneToMany;
use Bravo3\Orm\Annotations\OneToOne;
use Bravo3\Orm\Annotations\Sortable as SortableAnnotation;
use Bravo3\Orm\Enum\FieldType;
use Bravo3\Orm\Enum\RelationshipType;
use Bravo3\Orm\Exceptions\InvalidEntityException;
use Bravo3\Orm\Exceptions\NoMetadataException;
use Bravo3\Orm\Exceptions\UnexpectedValueException;
use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Mappers\Metadata\Column;
use Bravo3\Orm\Mappers\Metadata\Condition;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\Index;
use Bravo3\Orm\Mappers\Metadata\Relationship;
use Bravo3\Orm\Mappers\Metadata\Sortable;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Inflector\Inflector;

/**
 * Parses the annotations on a class to return valid metadata
 */
class AnnotationMetadataParser
{
    const ENTITY_ANNOTATION           = 'Bravo3\Orm\Annotations\Entity';
    const ID_ANNOTATION               = 'Bravo3\Orm\Annotations\Id';
    const COLUMN_ANNOTATION           = 'Bravo3\Orm\Annotations\Column';
    const OTO_ANNOTATION              = 'Bravo3\Orm\Annotations\OneToOne';
    const OTM_ANNOTATION              = 'Bravo3\Orm\Annotations\OneToMany';
    const MTO_ANNOTATION              = 'Bravo3\Orm\Annotations\ManyToOne';
    const MTM_ANNOTATION              = 'Bravo3\Orm\Annotations\ManyToMany';
    const ERR_UNKNOWN_CONDITION       = "Unknown condition type, must be a Condition object";
    const ERR_UNKNOWN_SORTABLE        = "Unknown sortable type, must be a string or Sortable object";
    const ERR_CONDITION_CONFLICT      = "A condition cannot be tested against both a 'column' and a 'method'";
    const ERR_CONDITION_PREREQUISITES = "A condition must define either a 'column' or a 'method' to test against";

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

    /**
     * @var MapperInterface
     */
    protected $external_mapper;

    public function __construct($class_name, MapperInterface $external_mapper)
    {
        $this->class_name        = $class_name;
        $this->external_mapper   = $external_mapper;
        $this->annotation_reader = new AnnotationReader();
        $this->reflection_obj    = new \ReflectionClass($this->class_name);
        $this->validateTableName();
    }

    /**
     * Throw an exception if the table name contains illegal chars
     */
    private function validateTableName()
    {
        if (!ctype_alnum(str_replace(['_'], '', $this->getTableName()))) {
            throw new InvalidEntityException("Table name for '".$this->class_name."' contains an illegal characters.");
        }
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
        $relationship = new Relationship($name, $type);

        $relationship->setSource($this->reflection_obj->name)
                     ->setSourceTable($this->getTableName())
                     ->setTarget($annotation->target)
                     ->setTargetTable($this->external_mapper->getEntityMetadata($annotation->target)->getTableName())
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
                            $this->testConditionAnnotation($condition);

                            $conditions[] = new Condition(
                                $condition->column,
                                $condition->method,
                                $condition->value,
                                $condition->comparison
                            );
                        } else {
                            throw new UnexpectedValueException(self::ERR_UNKNOWN_CONDITION);
                        }
                    }
                    $sortables[] = new Sortable($sortable->column, $conditions, $sortable->name);
                } elseif (is_string($sortable)) {
                    $sortables[] = new Sortable($sortable);
                } else {
                    throw new UnexpectedValueException(self::ERR_UNKNOWN_SORTABLE);
                }
            }
            $relationship->setSortableBy($sortables);
        }

        return $relationship;
    }

    /**
     * Check a condition and throw an exception if it is invalid
     *
     * @param ConditionAnnotation $condition
     */
    private function testConditionAnnotation(ConditionAnnotation $condition)
    {
        if ($condition->column && $condition->method) {
            throw new UnexpectedValueException(self::ERR_CONDITION_CONFLICT);
        }

        if (!$condition->column && !$condition->method) {
            throw new UnexpectedValueException(
                self::ERR_CONDITION_PREREQUISITES
            );
        }
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
        /** @noinspection PhpParamsInspection */
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
            $index->setColumns($annotation_index->columns ?: []);
            $index->setMethods($annotation_index->methods ?: []);
            $indices[] = $index;
        }

        return $indices;
    }

    /**
     * Get table sortables
     *
     * @return Sortable[]
     */
    public function getSortables()
    {
        $sortables            = [];
        $annotation_sortables = $this->getEntityAnnotation()->sortable_by;

        foreach ($annotation_sortables as $sortable) {
            $conditions = [];

            if ($sortable instanceof SortableAnnotation) {
                if ($sortable->conditions) {
                    foreach ($sortable->conditions as $condition) {
                        if ($condition instanceof ConditionAnnotation) {
                            $this->testConditionAnnotation($condition);

                            $conditions[] = new Condition(
                                $condition->column,
                                $condition->method,
                                $condition->value,
                                $condition->comparison
                            );
                        } else {
                            throw new UnexpectedValueException(self::ERR_UNKNOWN_CONDITION);
                        }
                    }
                }
                $sortables[] = new Sortable($sortable->column, $conditions, $sortable->name);
            } elseif (is_string($sortable)) {
                $sortables[] = new Sortable($sortable);
            } else {
                throw new UnexpectedValueException(self::ERR_UNKNOWN_SORTABLE);
            }
        }

        return $sortables;
    }

    /**
     * Get the Entity metadata object
     *
     * @return Entity
     */
    public function getEntityMetadata()
    {
        $entity = new Entity($this->reflection_obj->name, $this->getTableName());
        $entity->setColumns($this->getColumns());
        $entity->setRelationships($this->getRelationships());
        $entity->setIndices($this->getIndices());
        $entity->setSortables($this->getSortables());
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
            $this->entity_annotation = $this->annotation_reader->getClassAnnotation(
                $this->reflection_obj,
                self::ENTITY_ANNOTATION
            );

            if (!$this->entity_annotation) {
                throw new NoMetadataException(
                    'Entity "'.$this->class_name.'" does not contain an @Entity annotation'
                );
            }
        }

        return $this->entity_annotation;
    }
}
