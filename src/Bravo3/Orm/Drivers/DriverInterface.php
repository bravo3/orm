<?php
namespace Bravo3\Orm\Drivers;

use Bravo3\Orm\Drivers\Common\Ref;
use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\Traits\DebugInterface;

interface DriverInterface extends DebugInterface
{
    /**
     * Persist some primitive data
     *
     * @param string         $key
     * @param SerialisedData $data
     * @param int            $ttl
     * @return void
     */
    public function persist($key, SerialisedData $data, $ttl = null);

    /**
     * Delete a primitive document
     *
     * @param string $key
     * @return void
     */
    public function delete($key);

    /**
     * Retrieve an object
     *
     * @param string $key
     * @return SerialisedData
     */
    public function retrieve($key);

    /**
     * Execute the current unit of work
     *
     * @return void
     */
    public function flush();

    /**
     * Purge the current unit of work, clearing any unexecuted commands
     *
     * @return void
     */
    public function purge();

    /**
     * Scan key-value indices and return the value of all matching keys
     *
     * @param string $key
     * @return string[]
     */
    public function scan($key);

    /**
     * Get the drivers preferred key scheme
     *
     * @return KeySchemeInterface
     */
    public function getPreferredKeyScheme();

    /**
     * Set a key-value index
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setSingleValueIndex($key, $value);

    /**
     * Get the value of a key-value index
     *
     * If the key does not exist, null should be returned.
     *
     * @param string $key
     * @return string|null
     */
    public function getSingleValueIndex($key);

    /**
     * Clear the value of a key-value index
     *
     * @param string $key
     * @return string
     */
    public function clearSingleValueIndex($key);

    /**
     * Clear all values from a set index
     *
     * @param string $key
     * @return void
     */
    public function clearMultiValueIndex($key);

    /**
     * Add one or many values to a list index
     *
     * @param string       $key
     * @param string|array $value
     * @return void
     */
    public function addMultiValueIndex($key, $value);

    /**
     * Remove one or more values from a set index
     *
     * @param string       $key
     * @param string|array $value
     * @return void
     */
    public function removeMultiValueIndex($key, $value);

    /**
     * Get a list of all values on a set index
     *
     * If the key does not exist, an empty array should be returned.
     *
     * @param string $key
     * @return string[]
     */
    public function getMultiValueIndex($key);

    /**
     * Clear an entire sorted index
     *
     * @param string $key
     * @return void
     */
    public function clearSortedIndex($key);

    /**
     * Add or update an item in a sorted index
     *
     * @param string $key
     * @param mixed  $score
     * @param string $value
     * @return void
     */
    public function addSortedIndex($key, $score, $value);

    /**
     * Remove an item from a sorted index
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function removeSortedIndex($key, $value);

    /**
     * Get a range of values in a sorted index
     *
     * If $start/$stop are === null, they are assumed to be the start/end of the entire set.
     *
     * @param string $key
     * @param bool   $reverse
     * @param int    $start
     * @param int    $stop
     * @return string[]
     */
    public function getSortedIndex($key, $reverse = false, $start = null, $stop = null);

    /**
     * Get the size of a sorted index, without any filters applied
     *
     * @param string $key
     * @return int
     */
    public function getSortedIndexSize($key);

    /**
     * Get all refs to an entity
     *
     * @param string $key Entity ref key
     * @return Ref[]
     */
    public function getRefs($key);

    /**
     * Add a ref to an entity
     *
     * @param string $key Entity ref key
     * @param Ref    $ref Reference to add
     * @return void
     */
    public function addRef($key, Ref $ref);

    /**
     * Remove a ref from an entity
     *
     * If the reference does not exist, no action is taken.
     *
     * @param string $key Entity ref key
     * @param Ref    $ref Reference to remove
     * @return void
     */
    public function removeRef($key, Ref $ref);

    /**
     * Clear all refs from an entity (delete a ref list)
     *
     * @param string $key
     * @return void
     */
    public function clearRefs($key);
}
