<?php
namespace Bravo3\Orm\Mappers\Metadata;

class SortedIndex
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $column;

    /**
     * @var Condition[]
     */
    protected $conditions;

    /**
     * @param string      $column
     * @param Condition[] $conditions
     * @param string      $name
     */
    public function __construct($column, array $conditions = [], $name = null)
    {
        $this->name       = $name ?: $column;
        $this->column     = $column;
        $this->conditions = $conditions;
    }

    /**
     * Get Column
     *
     * @return string
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * Set Column
     *
     * @param string $column
     * @return $this
     */
    public function setColumn($column)
    {
        $this->column = $column;
        return $this;
    }

    /**
     * Get Conditions
     *
     * @return Condition[]
     */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
     * Set Conditions
     *
     * @param Condition[] $conditions
     * @return $this
     */
    public function setConditions(array $conditions)
    {
        $this->conditions = $conditions;
        return $this;
    }

    /**
     * Add a condition
     *
     * @param Condition $condition
     * @return $this
     */
    public function addCondition(Condition $condition)
    {
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
