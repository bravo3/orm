<?php
namespace Bravo3\Orm\Mappers\Metadata;

class UniqueIndex
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string[]
     */
    protected $columns = [];

    /**
     * @var string[]
     */
    protected $methods = [];

    /**
     * @var string
     */
    protected $table_name;

    /**
     * @param string $table_name
     * @param string $index_name
     */
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
    public function setColumns(array $columns)
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
     * Get Methods
     *
     * @return string[]
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Set Methods
     *
     * @param string[] $methods
     * @return $this
     */
    public function setMethods(array $methods)
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * Add a single method to the index
     *
     * @param string $method
     * @return $this
     */
    public function addMethod($method)
    {
        $this->methods[] = $method;
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
