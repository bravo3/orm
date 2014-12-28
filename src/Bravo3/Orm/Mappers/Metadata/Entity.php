<?php
namespace Bravo3\Orm\Mappers\Metadata;

class Entity
{
    /**
     * Use this string to join all ID columns in a table to return a single string key
     *
     * This string is not configurable and must never change!
     */
    const ID_DELIMITER = ':';

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
     * Used to lookup the property that matches a getter/setter function
     *
     * @var array
     */
    protected $property_map = null;

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
     * Get Columns
     *
     * @return Column[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Get a column by it's property name, or null if no such column exists
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
     * Set Columns
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
        $this->id_columns = null;
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
     * Get Relationships
     *
     * @return Relationship[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * Set Relationships
     *
     * @param Relationship[] $relationships
     * @return $this
     */
    public function setRelationships(array $relationships)
    {
        $this->relationships = $relationships;
        $this->property_map = null;
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
        $this->property_map = null;
        return $this;
    }

    /**
     * Get a relationship by it's name, or null if no such relationship exists
     *
     * @param $name
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
    public function getPropertyFor($fn) {
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
}
