<?php
namespace Bravo3\Orm\Query;

use Bravo3\Orm\Enum\Direction;

/**
 * Perform a sorted query on a given source (table, entity) against a result set (records, relationship)
 */
interface SortedQueryInterface extends QueryInterface
{
    /**
     * Get relationship name
     *
     * @return string
     */
    public function getRelationshipName();

    /**
     * Get sorting field name
     *
     * @return string
     */
    public function getSortBy();

    /**
     * Get Entity
     *
     * @return object
     */
    public function getEntity();

    /**
     * Get Direction
     *
     * @return Direction
     */
    public function getDirection();

    /**
     * Get start index
     *
     * @return int
     */
    public function getStart();

    /**
     * Get end index
     *
     * @return int
     */
    public function getEnd();
}
