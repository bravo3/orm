<?php
namespace Bravo3\Orm\Mappers\Metadata;

class Sortable
{
    /**
     * @var string
     */
    protected $column;

    /**
     * @var Condition[]
     */
    protected $conditions;

    public function __construct($column, array $conditions = [])
    {
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
    public function setConditions($conditions)
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
}
