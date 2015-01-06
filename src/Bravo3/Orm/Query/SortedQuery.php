<?php
namespace Bravo3\Orm\Query;

use Bravo3\Orm\Enum\Direction;

class SortedQuery extends AbstractQuery implements QueryInterface
{
    /**
     * @var string
     */
    protected $relationship_name;

    /**
     * @var string
     */
    protected $sort_by;

    /**
     * @var object
     */
    protected $entity;

    /**
     * @var Direction
     */
    protected $direction;

    /**
     * @var int
     */
    protected $start;

    /**
     * @var int
     */
    protected $end;

    /**
     * @param object    $entity            Entity to retrieve relationships from
     * @param string    $relationship_name Name of the relationship on provided entity
     * @param string    $sort_by           Sort by column
     * @param Direction $direction         Assumes ascending if omitted
     * @param int       $start             Start index (inclusive), null/0 for beginning of set
     * @param int       $end               Stop index (inclusive), null/-1 for end of set, -2 for penultimate record
     */
    public function __construct(
        $entity,
        $relationship_name,
        $sort_by,
        Direction $direction = null,
        $start = null,
        $end = null
    ) {
        parent::__construct($entity);
        $this->entity            = $entity;
        $this->relationship_name = $relationship_name;
        $this->sort_by           = $sort_by;
        $this->direction         = $direction ?: Direction::ASC();
        $this->start             = $start;
        $this->end               = $end;
    }

    /**
     * Get relationship name
     *
     * @return string
     */
    public function getRelationshipName()
    {
        return $this->relationship_name;
    }

    /**
     * Set relationship name
     *
     * @param string $relationship_name
     * @return $this
     */
    public function setRelationshipName($relationship_name)
    {
        $this->relationship_name = $relationship_name;
        return $this;
    }

    /**
     * Get sorting field name
     *
     * @return string
     */
    public function getSortBy()
    {
        return $this->sort_by;
    }

    /**
     * Set sorting field name
     *
     * @param string $sort_by
     * @return $this
     */
    public function setSortBy($sort_by)
    {
        $this->sort_by = $sort_by;
        return $this;
    }

    /**
     * Get Entity
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set Entity
     *
     * @param object $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Get Direction
     *
     * @return Direction
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Set Direction
     *
     * @param Direction $direction
     * @return $this
     */
    public function setDirection(Direction $direction)
    {
        $this->direction = $direction;
        return $this;
    }

    /**
     * Get start index
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set start index
     *
     * @param int $start
     * @return $this
     */
    public function setStart($start)
    {
        $this->start = $start;
        return $this;
    }

    /**
     * Get end index
     *
     * @return int
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Set end index
     *
     * @param int $end
     * @return $this
     */
    public function setEnd($end)
    {
        $this->end = $end;
        return $this;
    }
}
