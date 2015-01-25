<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Enum\Direction;
use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Query\IndexedQuery;
use Bravo3\Orm\Query\QueryResult;
use Bravo3\Orm\Query\SortedQuery;
use Bravo3\Orm\Services\Io\Reader;

class QueryManager extends AbstractManagerUtility
{
    /**
     * Create a query against a table matching one or more indices
     *
     * @param IndexedQuery $query
     * @return QueryResult
     */
    public function indexedQuery(IndexedQuery $query)
    {
        $metadata = $this->getMapper()->getEntityMetadata($query->getClassName());

        $master_list = null;
        foreach ($query->getIndices() as $index_name => $index_key) {
            $index = $metadata->getIndexByName($index_name);
            if (!$index) {
                throw new InvalidArgumentException('Index "'.$index_name.'" does not exist in query table');
            }

            $key = $this->getKeyScheme()->getIndexKey($index, $index_key);
            $set = $this->getDriver()->scan($key);

            $results = [];
            foreach ($set as $key) {
                $results[] = $this->getDriver()->getSingleValueIndex($key);
            }

            if ($master_list === null) {
                $master_list = $results;
            } else {
                $master_list = array_intersect($master_list, $results);
            }
        }

        return new QueryResult($this->entity_manager, $query, array_values($master_list));
    }

    /**
     * Get all foreign entities ordered by a sort column
     *
     * If you have applied a limit to the query but need to know the full size of the unfiltered set, you must set
     * $check_full_set_size to true to gather this information at the expense of a second database query.
     *
     * @param SortedQuery $query
     * @param bool        $check_full_set_size
     * @return QueryResult
     */
    public function sortedQuery(SortedQuery $query, $check_full_set_size = false)
    {
        $metadata     = $this->getMapper()->getEntityMetadata($query->getClassName());
        $reader       = new Reader($metadata, $query->getEntity());
        $relationship = $metadata->getRelationshipByName($query->getRelationshipName());

        if (!$relationship) {
            throw new InvalidArgumentException('Relationship "'.$query->getRelationshipName().'" does not exist');
        }

        // Important, else the QueryResult class will try to hydrate the wrong entity
        $query->setClassName($relationship->getTarget());
        $key = $this->getKeyScheme()->getSortIndexKey($relationship, $query->getSortBy(), $reader->getId());

        $results = $this->getDriver()->getSortedIndex(
            $key,
            $query->getDirection() == Direction::DESC(),
            $query->getStart(),
            $query->getEnd()
        );

        if (!$query->getStart() && !$query->getEnd()) {
            $full_size = count($results);
        } elseif ($check_full_set_size) {
            $full_size = $this->getDriver()->getSortedIndexSize($key);
        } else {
            $full_size = null;
        }

        return new QueryResult($this->entity_manager, $query, $results, $full_size);
    }
}
