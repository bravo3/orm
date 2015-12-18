<?php
namespace Bravo3\Orm\Mappers\Metadata;

class Entity
{
    /**
     * @var string
     */
    protected $class_name;

    /**
     * @var string
     */
    protected $table_name;

    /**
     * @var Column[]
     */
    protected $columns = [];

    /**
     * @var Column[]
     */
    protected $id_columns = null;

    /**
     * @var Relationship[]
     */
    protected $relationships = [];

    /**
     * @var UniqueIndex[]
     */
    protected $unique_indices = [];

    /**
     * @var SortedIndex[]
     */
    protected $sorted_indices = [];

    /**
     * Used to lookup the property that matches a getter/setter function
     *
     * @var array
     */
    protected $property_map = null;

    /**
     * @param string $class_name
     * @param string $table_name
     */
    public function __construct($class_name, $table_name)
    {
        $this->class_name = $class_name;
        $this->table_name = $table_name;
    }

    /**
     * Get ClassName
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->class_name;
    }

    /**
     * Set the entity class name
     *
     * @param string $class_name
     * @return $this
     */
    public function setClassName($class_name)
    {
        $this->class_name = $class_name;
        return $this;
    }

    /**
     * Get the entity class name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    /**
     * Set TableName
     *
     * @param string $table_name
     * @return $this
     */
    public function setTableName($table_name)
    {
        $this->table_name = $table_name;
        return $this;
    }

    /**
     * Get columns
     *
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get a column by its property name, or null if no such column exists
     *
     * @param $name
     * @return Column|null
     */
    public function getColumnByProperty($name)
    {
        foreach ($this->columns as $column) {
            if ($column->getProperty() == $name) {
                return $column;
            }
        }

        return null;
    }

    /**
     * Set columns
     *
     * @param Column[] $columns
     * @return $this
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        $this->resetMaps();
        return $this;
    }

    /**
     * Add a column
     *
     * @param Column $column
     * @return $this
     */
    public function addColumn(Column $column)
    {
        $this->columns[] = $column;
        $this->resetMaps();
        return $this;
    }

    /**
     * Clear the maps based on the columns
     */
    private function resetMaps()
    {
        $this->id_columns   = null;
        $this->property_map = null;
    }

    /**
     * Get all columns that are marked an as an ID field
     *
     * @return Column[]
     */
    public function getIdColumns()
    {
        if ($this->id_columns === null) {
            $this->id_columns = [];
            foreach ($this->columns as $column) {
                if ($column->isId()) {
                    $this->id_columns[] = $column;
                }
            }
        }
        return $this->id_columns;
    }

    /**
     * Get relationships
     *
     * @return Relationship[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * Set relationships
     *
     * @param Relationship[] $relationships
     * @return $this
     */
    public function setRelationships(array $relationships)
    {
        $this->relationships = $relationships;
        $this->property_map  = null;
        return $this;
    }

    /**
     * Add a relationship
     *
     * @param Relationship $relationship
     * @return $this
     */
    public function addRelationship(Relationship $relationship)
    {
        $this->relationships[] = $relationship;
        $this->property_map    = null;
        return $this;
    }

    /**
     * Get a relationship by its name, or null if no such relationship exists
     *
     * @param string $name
     * @return Relationship|null
     */
    public function getRelationshipByName($name)
    {
        foreach ($this->relationships as $relationship) {
            if ($relationship->getName() == $name) {
                return $relationship;
            }
        }

        return null;
    }

    /**
     * Get the name of the property matching the given function
     *
     * @param string $fn
     * @return string|null
     */
    public function getPropertyFor($fn)
    {
        if ($this->property_map === null) {
            $this->generatePropertyMap();
        }

        if (isset($this->property_map[$fn])) {
            return $this->property_map[$fn];
        } else {
            return null;
        }
    }

    /**
     * Generate a list of all getters/setters and what property they refer to
     */
    private function generatePropertyMap()
    {
        $this->property_map = [];

        foreach ($this->columns as $column) {
            $this->property_map[$column->getGetter()] = $column->getProperty();
            $this->property_map[$column->getSetter()] = $column->getProperty();
        }

        foreach ($this->relationships as $relationship) {
            $this->property_map[$relationship->getGetter()] = $relationship->getName();
            $this->property_map[$relationship->getSetter()] = $relationship->getName();
        }
    }

    /**
     * Get indices
     *
     * @return UniqueIndex[]
     */
    public function getUniqueIndices()
    {
        return $this->unique_indices;
    }

    /**
     * Set indices
     *
     * @param UniqueIndex[] $unique_indices
     * @return $this
     */
    public function setUniqueIndices(array $unique_indices)
    {
        $this->unique_indices = $unique_indices;
        return $this;
    }

    /**
     * Add an index
     *
     * @param UniqueIndex $index
     * @return $this
     */
    public function addUniqueIndex(UniqueIndex $index)
    {
        $this->unique_indices[] = $index;
        return $this;
    }

    /**
     * Get an index by its name, or null if no such index exists
     *
     * @param string $name
     * @return UniqueIndex|null
     */
    public function getUniqueIndexByName($name)
    {
        foreach ($this->unique_indices as $index) {
            if ($index->getName() == $name) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Get Sortables
     *
     * @return SortedIndex[]
     */
    public function getSortedIndices()
    {
        return $this->sorted_indices;
    }

    /**
     * Set Sortables
     *
     * @param SortedIndex[] $sorted_indices
     * @return $this
     */
    public function setSortedIndices(array $sorted_indices)
    {
        $this->sorted_indices = $sorted_indices;
        return $this;
    }

    /**
     * Get a table sortable by name
     *
     * @param string $name
     * @return SortedIndex|null
     */
    public function getSortableByName($name)
    {
        foreach ($this->sorted_indices as $sortable) {
            if ($sortable->getName() == $name) {
                return $sortable;
            }
        }

        return null;
    }

    /**
     * Add a sortable to the table metadata
     *
     * @param SortedIndex $sortable
     * @return $this
     */
    public function addSortedIndex(SortedIndex $sortable)
    {
        $this->sorted_indices[] = $sortable;
        return $this;
    }
}
