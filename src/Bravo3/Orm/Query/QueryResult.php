<?php
namespace Bravo3\Orm\Query;

use Bravo3\Orm\Services\EntityManager;

/**
 * QueryResult objects are a traversable lazy-loading entity holder
 */
class QueryResult implements \Countable, \Iterator, \ArrayAccess
{
    /**
     * @var string[]
     */
    protected $id_list;

    /**
     * @var array
     */
    protected $entities = [];

    /**
     * @var QueryInterface
     */
    protected $query;

    /**
     * @var EntityManager
     */
    protected $entity_manager;

    /**
     * @var \ArrayIterator
     */
    protected $iterator;

    /**
     * @var int
     */
    protected $full_size;

    /**
     * @var bool
     */
    protected $use_cache;

    public function __construct(
        EntityManager $entity_manager,
        QueryInterface $query,
        array $results,
        $full_size = null,
        $use_cache = true
    ) {
        $this->entity_manager = $entity_manager;
        $this->query          = $query;
        $this->id_list        = $results;
        $this->iterator       = new \ArrayIterator($this->id_list);
        $this->full_size      = $full_size;
        $this->use_cache      = (bool)$use_cache;
    }

    /**
     * Get the full list of IDs returned in the query
     *
     * @return string[]
     */
    public function getIdList()
    {
        return $this->id_list;
    }

    /**
     * Result set size
     *
     * @return int
     */
    public function count()
    {
        return count($this->id_list);
    }

    /**
     * Get the search query
     *
     * @return QueryInterface
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Return an entity from the results by its ID
     *
     * @param string $id
     * @return object
     */
    public function getEntityById($id)
    {
        try {
            if (!array_key_exists($id, $this->entities)) {
                $this->hydrateEntity($id);
            }

            return $this->entities[$id];
        } catch (\Exception $e) {}
    }

    /**
     * Get the full size of the query, without any limits applied to it
     *
     * This information may not be available, and should be explicitly requested when creating the query.
     *
     * @return int
     */
    public function getFullSize()
    {
        return $this->full_size;
    }

    /**
     * Hydrate an entity
     *
     * @param string $id
     * @return $this
     */
    private function hydrateEntity($id)
    {
        $this->entities[$id] = $this->entity_manager->retrieve($this->query->getClassName(), $id, $this->use_cache);

        return $this;
    }

    /**
     * Return the current entity
     *
     * @return object
     */
    public function current()
    {
        if ($result = $this->getEntityById($this->iterator->current())) {
            return $result;
        } else {
            $this->next();
            return $this->current();
        }
    }

    /**
     * Move forward to the next entity
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * Get the key of the current element
     *
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * Checks if current position is valid
     *
     * @return boolean Returns true on success or false on failure.
     */
    public function valid()
    {
        return $this->iterator->valid();
    }

    /**
     * Rewind the Iterator to the first element
     */
    public function rewind()
    {
        $this->iterator->rewind();
    }

    /**
     * Check if an ID is in the result set
     *
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->id_list);
    }

    /**
     * Retrieve the entity at position $offset
     *
     * @param int $offset
     * @return object
     */
    public function offsetGet($offset)
    {
        return $this->getEntityById($this->id_list[$offset]);
    }

    /**
     * You cannot set query elements, calling this function will throw a \LogicException
     *
     * @param int   $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException("You cannot set a query result item");
    }

    /**
     * You cannot unset query elements, calling this function will throw a \LogicException
     *
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException("You cannot unset a query result item");
    }
}
