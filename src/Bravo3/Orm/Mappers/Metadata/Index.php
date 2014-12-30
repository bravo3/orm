<?php
namespace Bravo3\Orm\Mappers\Metadata;

class Index
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string[]
     */
    protected $columns;

    /**
     * @var string
     */
    protected $table_name;

    public function __construct($table_name, $index_name)
    {
        $this->table_name = $table_name;
        $this->name       = $index_name;
    }

    /**
     * Get index name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set index name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get columns
     *
     * @return string[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Set columns
     *
     * @param string[] $columns
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
     * @param string $column
     * @return $this
     */
    public function addColumn($column)
    {
        $this->columns[] = $column;
        return $this;
    }

    /**
     * Get TableName
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
}