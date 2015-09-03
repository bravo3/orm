<?php
namespace Bravo3\Orm\Query;

use Bravo3\Orm\Enum\Direction;

class ScoreFilterQuery extends SortedQuery implements QueryInterface
{
    /**
     * @var int
     */
    protected $min_score;

    /**
     * @var int
     */
    protected $max_score;

    /**
     * @param object    $entity            Entity to retrieve relationships from
     * @param string    $relationship_name Name of the relationship on provided entity
     * @param string    $sort_by           Sort by column
     * @param Direction $direction         Assumes ascending if omitted
     * @param int       $min_score         Starting member values to filter the sorted data-set
     * @param int       $max_score         Maximum value to filter out the members within the sorted data-set
     * @param int       $start             Start index (inclusive), null/0 for beginning of set
     * @param int       $end               Stop index (inclusive), null/-1 for end of set, -2 for penultimate record
     */
    public function __construct(
        $entity,
        $relationship_name,
        $sort_by,
        Direction $direction = null,
        $min_score,
        $max_score,
        $start = null,
        $end = null
    ) {
        parent::__construct($entity, $relationship_name, $sort_by, $direction, $start, $end);
        $this->min_score         = $min_score;
        $this->max_score         = $max_score;
    }

    /**
     * Get starting member values to filter the sorted data-set
     *
     * @return int
     */
    public function getMinScore()
    {
        return $this->min_score;
    }

    /**
     * Set starting member values to filter the sorted data-set
     *
     * @param int $min_score
     * @return $this
     */
    public function setMinScore($min_score)
    {
        $this->min_score = $min_score;
        return $this;
    }

    /**
     * Get maximum value to filter out the members within the sorted data-set
     *
     * @return int
     */
    public function getMaxScore()
    {
        return $this->max_score;
    }

    /**
     * Set maximum value to filter out the members within the sorted data-set
     *
     * @param int $max_score
     * @return $this
     */
    public function setMaxScore($max_score)
    {
        $this->max_score = $max_score;
        return $this;
    }

}
