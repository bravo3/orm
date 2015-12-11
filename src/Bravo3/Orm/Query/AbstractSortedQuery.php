<?php
namespace Bravo3\Orm\Query;

use Bravo3\Orm\Enum\Direction;

/**
 * Perform a sorted query on a given source (table, entity) against a result set (records, relationship)
 */
abstract class AbstractSortedQuery extends AbstractQuery implements SortedQueryInterface
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
     * @param mixed       $entity            Source reference point/object
     * @param string|null $relationship_name Source object relationship if applicable
     * @param string      $sort_by           Sort by column
     * @param Direction   $direction         Assumes ascending if omitted
     * @param int         $start             Start index (inclusive), null/0 for beginning of set
     * @param int         $end               Stop index (inclusive), null/-1 for end of set, -2 for penultimate record
     */
    public function __construct(
        $entity,
        $relationship_name,
        string $sort_by,
        Direction $direction = null,
        int $start = null,
        int $end = null
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
    public function getRelationshipName(): string
    {
        return $this->relationship_name;
    }

    /**
     * Set relationship name
     *
     * @param string $relationship_name
     * @return $this
     */
    public function setRelationshipName(string $relationship_name): self
    {
        $this->relationship_name = $relationship_name;
        return $this;
    }

    /**
     * Get sorting field name
     *
     * @return string
     */
    public function getSortBy(): string
    {
        return $this->sort_by;
    }

    /**
     * Set sorting field name
     *
     * @param string $sort_by
     * @return $this
     */
    public function setSortBy(string $sort_by): self
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
    public function setEntity($entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Get Direction
     *
     * @return Direction
     */
    public function getDirection(): Direction
    {
        return $this->direction;
    }

    /**
     * Set Direction
     *
     * @param Direction $direction
     * @return $this
     */
    public function setDirection(Direction $direction): self
    {
        $this->direction = $direction;
        return $this;
    }

    /**
     * Get start index
     *
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * Set start index
     *
     * @param int $start
     * @return $this
     */
    public function setStart(int $start): self
    {
        $this->start = $start;
        return $this;
    }

    /**
     * Get end index
     *
     * @return int
     */
    public function getEnd(): int
    {
        return $this->end;
    }

    /**
     * Set end index
     *
     * @param int $end
     * @return $this
     */
    public function setEnd(int $end): self
    {
        $this->end = $end;
        return $this;
    }
}
