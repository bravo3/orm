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
     * @param SortedQuery $query
     * @return QueryResult
     */
    public function sortedQuery(SortedQuery $query)
    {
        $metadata     = $this->getMapper()->getEntityMetadata($query->getClassName());
        $reader       = new Reader($metadata, $query->getEntity());
        $relationship = $metadata->getRelationshipByName($query->getRelationshipName());

        if (!$relationship) {
            throw new InvalidArgumentException('Relationship "'.$query->getRelationshipName().'" does not exist');
        }

        // Important, else the QueryResult class will try to hydrate the wrong entity
        $query->setClassName($relationship->getTarget());

        $results = $this->getDriver()->getSortedIndex(
            $this->getKeyScheme()->getSortIndexKey($relationship, $query->getSortBy(), $reader->getId()),
            $query->getDirection() == Direction::DESC(),
            $query->getStart(),
            $query->getEnd()
        );

        return new QueryResult($this->entity_manager, $query, $results);
    }
}
