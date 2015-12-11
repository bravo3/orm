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
    public function getRelationshipName(): string;

    /**
     * Get sorting field name
     *
     * @return string
     */
    public function getSortBy(): string;

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
    public function getDirection(): Direction;

    /**
     * Get start index
     *
     * @return int
     */
    public function getStart(): int;

    /**
     * Get end index
     *
     * @return int
     */
    public function getEnd(): int;
}
