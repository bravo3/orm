<?php
namespace Bravo3\Orm\Mappers\Yaml;

use Bravo3\Orm\Enum\FieldType;
use Bravo3\Orm\Enum\RelationshipType;
use Bravo3\Orm\Exceptions\MappingViolationException;
use Bravo3\Orm\Exceptions\NoMetadataException;
use Bravo3\Orm\Mappers\AbstractMapper;
use Bravo3\Orm\Mappers\DereferencingMapperInterface;
use Bravo3\Orm\Mappers\Metadata\Column;
use Bravo3\Orm\Mappers\Metadata\Condition;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\UniqueIndex;
use Bravo3\Orm\Mappers\Metadata\Relationship;
use Bravo3\Orm\Mappers\Metadata\SortedIndex;
use Bravo3\Orm\Services\Io\Reader;
use Symfony\Component\Yaml\Yaml;

/**
 * YAML metadata mapper
 */
class YamlMapper extends AbstractMapper implements DereferencingMapperInterface
{
    /**
     * @var string[]
     */
    private $unprocessed_maps = [];

    /**
     * @var Yaml
     */
    private $parser = null;

    /**
     * @var Entity[]
     */
    protected $entities = [];

    /**
     * @param string[] $maps
     */
    public function __construct(array $maps = [])
    {
        $this->unprocessed_maps = $maps;
    }

    /**
     * Lazy-load the YAML parser
     *
     * @return Yaml
     */
    protected function getParser()
    {
        if (!$this->parser) {
            $this->parser = new Yaml();
        }

        return $this->parser;
    }

    /**
     * Register a new map file
     *
     * @param string $fn
     * @return $this
     */
    public function registerMap($fn)
    {
        $this->unprocessed_maps[] = $fn;
        return $this;
    }

    /**
     * Process all unprocessed map files
     *
     * @return $this
     */
    protected function processMaps()
    {
        foreach ($this->unprocessed_maps as $map_fn) {
            $this->loadMap($map_fn);
        }

        $this->unprocessed_maps = [];

        return $this;
    }

    /**
     * Parse a map file, loading metadata from YAML
     *
     * @param string $fn
     * @return $this
     */
    protected function loadMap($fn)
    {
        $map = $this->getParser()->parse(file_get_contents($fn));
        foreach ($map as $class => $schema) {
            $table  = $this->getNode($schema, Schema::TABLE_NAME);
            $entity = new Entity($class, $table);

            // Columns
            $columns = $this->getNode($schema, Schema::COLUMNS);
            foreach ($columns as $property => $column_schema) {

                if ($this->getNode($column_schema, Schema::REL_ASSOCIATION, false)) {
                    $rel = $this->createRelationship($property, $column_schema);
                    $rel->setSource($class);
                    $entity->addRelationship($rel);
                } else {
                    $entity->addColumn($this->createColumn($property, $column_schema));
                }
            }

            // Table sortables
            $entity->setSortedIndices($this->createSortables($schema));

            // Table indices
            $indices = $this->getNode($schema, Schema::STD_INDICES, false, []);
            foreach ($indices as $name => $index_schema) {
                $index = new UniqueIndex($table, $name);
                $index->setColumns($this->getNode($index_schema, Schema::INDEX_COLUMNS, false, []));
                $index->setMethods($this->getNode($index_schema, Schema::INDEX_METHODS, false, []));
                $entity->addUniqueIndex($index);
            }

            // Add to map
            $this->entities[$class] = $entity;
        }
        return $this;
    }

    /**
     * Create a column from schema
     *
     * @param string $property
     * @param array  $column_schema
     * @return Column
     */
    private function createColumn($property, array $column_schema)
    {
        $column = new Column($property);
        $column->setId($this->getNode($column_schema, Schema::COLUMN_ID, false, false));
        $column->setGetter($this->getNode($column_schema, Schema::GETTER, false));
        $column->setSetter($this->getNode($column_schema, Schema::SETTER, false));
        $column->setClassName($this->getNode($column_schema, Schema::COLUMN_CLASS, false));
        $column->setProperty($property);

        /** @var FieldType $type */
        $type = FieldType::memberByValue($this->getNode($column_schema, Schema::COLUMN_TYPE, false, 'string'));
        $column->setType($type);

        return $column;
    }

    /**
     * Create a relationship from schema
     *
     * @param string $property
     * @param array  $column_schema
     * @return Relationship
     */
    private function createRelationship($property, array $column_schema)
    {
        $assoc        = $this->getNode($column_schema, Schema::REL_ASSOCIATION, true);
        $relationship = new Relationship($property, RelationshipType::memberByValue($assoc));

        $relationship->setTarget($this->getNode($column_schema, Schema::REL_TARGET, true))
                     ->setInversedBy($this->getNode($column_schema, Schema::REL_INVERSED_BY, false))
                     ->setGetter($this->getNode($column_schema, Schema::GETTER, false))
                     ->setSetter($this->getNode($column_schema, Schema::SETTER, false))
                     ->setSortedindices($this->createSortables($column_schema));

        return $relationship;
    }

    /**
     * Create a set of sortables
     *
     * @param array $column_schema
     * @return SortedIndex[]
     */
    private function createSortables(array $column_schema)
    {
        $out       = [];
        $sortables = $this->getNode($column_schema, Schema::SORT_INDICES, false, []);
        foreach ($sortables as $name => $sortable_schema) {
            $conditions        = [];
            $condition_schemas = $this->getNode($sortable_schema, Schema::INDEX_CONDITIONS, false, []);
            foreach ($condition_schemas as $condition_schema) {
                $conditions[] = new Condition(
                    $this->getNode($condition_schema, Schema::CONDITION_COLUMN, false),
                    $this->getNode($condition_schema, Schema::CONDITION_METHOD, false),
                    $this->getNode($condition_schema, Schema::CONDITION_VALUE),
                    $this->getNode($condition_schema, Schema::CONDITION_COMPARISON, false, '=')
                );
            }

            $out[] = new SortedIndex($this->getNode($sortable_schema, 'column'), $conditions, $name);
        }

        return $out;
    }

    /**
     * Get a node from the metadata map
     *
     * @param array  $map
     * @param string $node
     * @param bool   $required
     * @param mixed  $default
     * @return mixed
     * @throws MappingViolationException
     */
    protected function getNode($map, $node, $required = true, $default = null)
    {
        if (!is_array($map)) {
            throw new MappingViolationException("Metadata parse error: '".$map."' is not an array but should be");
        }

        if (!array_key_exists($node, $map)) {
            if ($required) {
                throw new MappingViolationException("Metadata parse error: ".$node." is required but missing");
            }

            return $default;
        }

        return $map[$node];
    }

    /**
     * Get the metadata for an entity, including column information
     *
     * If you do not provide a $relative_mapper then relationship metadata will not be hydrated.
     *
     * @param string|object $entity Entity or class name of the entity
     * @return Entity
     */
    public function getEntityMetadata($entity)
    {
        if (is_object($entity)) {
            $class = Reader::getEntityClassName($entity);
        } else {
            $class = $entity;
        }

        $this->processMaps();

        if (array_key_exists($class, $this->entities)) {
            return $this->entities[$class];
        } else {
            throw new NoMetadataException("No metadata is registered for class '".$class."'");
        }
    }

    /**
     * Get an entities full class name from its table name
     *
     * @param string $table_name
     * @return string
     */
    public function getClassFromTable($table_name)
    {
        $this->processMaps();

        foreach ($this->entities as $metadata) {
            if ($metadata->getTableName() == $table_name) {
                return $metadata->getClassName();
            }
        }

        throw new NoMetadataException("No metadata is registered for table '".$table_name."'");
    }
}
