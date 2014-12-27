<?php
namespace Bravo3\Orm\Serialisers;

use Bravo3\Orm\Exceptions\OutOfBoundsException;
use Traversable;

class SerialiserMap implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var SerialiserInterface[]
     */
    protected $serialisers = [];

    /**
     * @var string
     */
    protected $default_serialiser;

    public function addSerialiser(SerialiserInterface $serialiser)
    {
        $this->serialisers[$serialiser->getSerialiserCode()] = $serialiser;

        if (count($this->serialisers) == 1) {
            $this->default_serialiser = $serialiser->getSerialiserCode();
        }
    }

    /**
     * Get a serialiser by ID
     *
     * @param string $id
     * @return SerialiserInterface
     */
    public function getSerialiser($id)
    {
        return $this[$id];
    }

    /**
     * Remove a serialiser from the list
     *
     * @param string $id
     * @return $this
     */
    public function removeSerialiser($id)
    {
        unset($this->serialisers[$id]);
        return $this;
    }

    /**
     * Get the default serialiser
     *
     * @return SerialiserInterface
     */
    public function getDefaultSerialiser()
    {
        return $this->getSerialiser($this->default_serialiser);
    }

    /**
     * Set the default serialiser by ID
     *
     * @param string $id
     * @return $this
     */
    public function setDefaultSerialiserId($id)
    {
        $this->default_serialiser = $id;
        return $this;
    }

    /**
     * Get the ID of the default serialiser
     *
     * @return string
     */
    public function getDefaultSerialiserId()
    {
        return $this->default_serialiser;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     * @return boolean true on success or false on failure.
     *                      </p>
     *                      <p>
     *                      The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->serialisers);
    }

    /**
     * Get a serialiser by ID
     *
     * @param string $offset
     * @return SerialiserInterface
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new OutOfBoundsException('Serialiser with ID "'.$offset.'" does not exist');
        }

        return $this->serialisers[$offset];
    }

    /**
     * This function is an alias for addSerialiser(), the offset is ignored
     *
     * @param null                $offset
     * @param SerialiserInterface $value
     * @return $this
     */
    public function offsetSet($offset, $value)
    {
        $this->addSerialiser($value);
        return $this;
    }

    /**
     * This function is an alias for removeSerialiser()
     *
     * @param string $offset
     * @return $this
     */
    public function offsetUnset($offset)
    {
        $this->removeSerialiser($offset);
        return $this;
    }

    /**
     * Retrieve an interator for all serialisers in the map
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->serialisers);
    }
}
