<?php
namespace Bravo3\Orm\Query;

use Bravo3\Orm\Enum\Direction;

/**
 * Performs a sorted query on an entity's relationship
 */
class SortedRelationshipQuery extends AbstractSortedQuery
{
    /**
     * @param object    $entity            Source reference point/object
     * @param string    $relationship_name Source object relationship if applicable
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
        parent::__construct($entity, $relationship_name, $sort_by, $direction, $start, $end);
    }
}
