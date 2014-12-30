<?php
namespace Bravo3\Orm\Query;

class Query
{
    /**
     * @var string
     */
    protected $class_name;

    /**
     * @var array
     */
    protected $indices;

    public function __construct($class_name, array $indices = [])
    {
        $this->class_name = $class_name;
        $this->indices    = $indices;
    }

    /**
     * Get entity class name
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->class_name;
    }

    /**
     * Set entity class name
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
     * Get index filters
     *
     * @return array
     */
    public function getIndices()
    {
        return $this->indices;
    }

    /**
     * Set index filters
     *
     * @param array $indices
     * @return $this
     */
    public function setIndices($indices)
    {
        $this->indices = $indices;
        return $this;
    }

    /**
     * Add an index filter
     *
     * @param string $index_name
     * @param string $value
     * @return $this
     */
    public function addIndex($index_name, $value)
    {
        $this->indices[$index_name] = $value;
        return $this;
    }
}
