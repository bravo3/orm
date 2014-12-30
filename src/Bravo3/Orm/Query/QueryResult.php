<?php
namespace Bravo3\Orm\Query;

use Bravo3\Orm\Exceptions\OutOfBoundsException;
use Bravo3\Orm\Services\EntityManager;

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
     * @var Query
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

    public function __construct(EntityManager $entity_manager, Query $query, array $results)
    {
        $this->entity_manager = $entity_manager;
        $this->query          = $query;
        $this->id_list        = $results;
        $this->iterator       = new \ArrayIterator($this->id_list);
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
     * @return Query
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
        if (!$this->offsetExists($id)) {
            throw new OutOfBoundsException("ID is not in result set");
        }

        if (!array_key_exists($id, $this->entities)) {
            $this->hydrateEntity($id);
        }

        return $this->entities[$id];
    }

    /**
     * Hydrate an entity
     *
     * @param string $id
     * @return $this
     */
    private function hydrateEntity($id)
    {
        $this->entities[$id] = $this->entity_manager->retrieve($this->query->getClassName(), $id);
        return $this;
    }

    /**
     * Return the current entity
     *
     * @return object
     */
    public function current()
    {
        return $this->getEntityById($this->iterator->current());
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
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return in_array($offset, $this->id_list);
    }

    /**
     * Retrieve an entity by ID
     *
     * @param string $offset
     * @return object
     */
    public function offsetGet($offset)
    {
        return $this->getEntityById($offset);
    }

    /**
     * You cannot set query elements, calling this function will throw a \LogicException
     *
     * @param void $offset
     * @param void $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException("You cannot set a query result item");
    }

    /**
     * Dehydrate an entity
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->entities[$offset]);
    }
}
