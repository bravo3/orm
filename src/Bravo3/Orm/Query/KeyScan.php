<?php
namespace Bravo3\Orm\Query;

/**
 * Perform an unoptimised key-scan on a set of indices
 */
class KeyScan extends AbstractQuery
{
    /**
     * @var array
     */
    protected $indices;

    /**
     * @param object|string $class_name
     * @param string[]      $indices
     */
    public function __construct($class_name, array $indices = [])
    {
        parent::__construct($class_name);
        $this->indices = $indices;
    }

    /**
     * Get index filters
     *
     * @return array
     */
    public function getIndices()
    {
        return $this->indices;
    }

    /**
     * Set index filters
     *
     * @param array $indices
     * @return $this
     */
    public function setIndices(array $indices)
    {
        $this->indices = $indices;
        return $this;
    }

    /**
     * Add an index filter
     *
     * @param string $index_name
     * @param string $value
     * @return $this
     */
    public function addIndex($index_name, $value)
    {
        $this->indices[$index_name] = $value;
        return $this;
    }
}
