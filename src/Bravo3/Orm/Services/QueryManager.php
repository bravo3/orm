<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Enum\Direction;
use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Query\IndexedQuery;
use Bravo3\Orm\Query\QueryResult;
use Bravo3\Orm\Query\SortedQueryInterface;
use Bravo3\Orm\Services\Io\Reader;

class QueryManager extends AbstractManagerUtility
{
    /**
     * Create a query against a table matching one or more indices
     *
     * @param IndexedQuery $query
     * @param bool         $use_cache
     * @return QueryResult
     */
    public function indexedQuery(IndexedQuery $query, $use_cache = true)
    {
        $metadata   = $this->getMapper()->getEntityMetadata($query->getClassName());
        $prefix     = $this->getKeyScheme()->getEntityKey($metadata->getTableName(), '');
        $prefix_len = strlen($prefix);

        $master_list = null;
        foreach ($query->getIndices() as $index_name => $index_key) {
            if ($index_name == '@id') {
                $key = $this->getKeyScheme()->getEntityKey($metadata->getTableName(), $index_key);
                $set = $this->getDriver()->scan($key);

                $results = [];
                foreach ($set as $search_key) {
                    $results[] = substr($search_key, $prefix_len);
                }
            } else {
                $index = $metadata->getIndexByName($index_name);
                if (!$index) {
                    throw new InvalidArgumentException('Index "'.$index_name.'" does not exist on the entity');
                }

                $key = $this->getKeyScheme()->getIndexKey($index, $index_key);
                $set = $this->getDriver()->scan($key);

                $results = [];
                foreach ($set as $search_key) {
                    $results[] = $this->getDriver()->getSingleValueIndex($search_key);
                }
            }

            if ($master_list === null) {
                $master_list = $results;
            } else {
                $master_list = array_intersect($master_list, $results);
            }
        }

        return new QueryResult($this->entity_manager, $query, array_values($master_list), null, $use_cache);
    }

    /**
     * Get all foreign entities ordered by a sort column
     *
     * If you have applied a limit to the query but need to know the full size of the unfiltered set, you must set
     * $check_full_set_size to true to gather this information at the expense of a second database query.
     *
     * @param SortedQueryInterface $query
     * @param bool                 $check_full_set_size
     * @param bool                 $use_cache
     * @return QueryResult
     */
    public function sortedQuery(SortedQueryInterface $query, bool $check_full_set_size = false, bool $use_cache = true)
    {
        $metadata = $this->getMapper()->getEntityMetadata($query->getClassName());

        if ($query->getRelationshipName()) {
            // Entity relationship based query
            $reader       = new Reader($metadata, $query->getEntity());
            $relationship = $metadata->getRelationshipByName($query->getRelationshipName());

            if (!$relationship) {
                throw new InvalidArgumentException('Relationship "'.$query->getRelationshipName().'" does not exist');
            }

            // Important, else the QueryResult class will try to hydrate the wrong entity
            $query->setClassName($relationship->getTarget());
            $key = $this->getSortIndexKey($relationship, $query->getSortBy(), $reader->getId());
        } else {
            // Table based query
            $key = $this->getKeyScheme()->getTableSortKey($metadata->getTableName(), $query->getSortBy());
        }

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

        return new QueryResult($this->entity_manager, $query, $results, $full_size, $use_cache);
    }

    /**
     * Persist entity indices
     *
     * @param object $entity   Local entity object
     * @param Entity $metadata Optionally provide entity metadata to prevent recalculation
     * @param Reader $reader   Optionally provide the entity reader
     * @param string $local_id Optionally provide the local entity ID to prevent recalculation
     * @return $this
     */
    public function persistTableQueries($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        /** @var $metadata Entity */
        list($metadata, $reader, $local_id) = $this->buildPrerequisites($entity, $metadata, $reader, $local_id);

        foreach ($metadata->getSortables() as $sortable) {
            $key = $this->getKeyScheme()->getTableSortKey($metadata->getTableName(), $sortable->getName());

            // Test conditions
            $pass = true;
            foreach ($sortable->getConditions() as $condition) {
                if ($condition->getColumn()) {
                    if (!$condition->test($reader->getPropertyValue($condition->getColumn()))) {
                        $pass = false;
                        break;
                    }
                } elseif ($condition->getMethod()) {
                    if (!$condition->test($reader->getMethodValue($condition->getMethod()))) {
                        $pass = false;
                        break;
                    }
                }
            }

            if ($pass) {
                // Conditions met, add the index
                $this->getDriver()->addSortedIndex($key, $reader->getPropertyValue($sortable->getColumn()), $local_id);
            } else {
                // Conditions failed, remove the index
                $this->getDriver()->removeSortedIndex($key, $local_id);
            }
        }

        return $this;
    }

    /**
     * Persist entity indices
     *
     * @param object $entity   Local entity object
     * @param Entity $metadata Optionally provide entity metadata to prevent recalculation
     * @param Reader $reader   Optionally provide the entity reader
     * @param string $local_id Optionally provide the local entity ID to prevent recalculation
     * @return $this
     */
    public function deleteTableQueries($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        /** @var $metadata Entity */
        list($metadata, , $local_id) = $this->buildPrerequisites($entity, $metadata, $reader, $local_id);

        foreach ($metadata->getSortables() as $sortable) {
            $key = $this->getKeyScheme()->getTableSortKey($metadata->getTableName(), $sortable->getName());
            $this->getDriver()->removeSortedIndex($key, $local_id);
        }

        return $this;
    }
}
