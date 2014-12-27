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
    protected $columns;

    /**
     * @var Column[]
     */
    protected $id_columns = null;

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
    public function setColumns($columns)
    {
        $this->columns = $columns;
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
        return $this;
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
}
