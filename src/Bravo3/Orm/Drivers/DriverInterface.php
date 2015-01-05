<?php
namespace Bravo3\Orm\Drivers;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\Query\Query;
use Bravo3\Orm\Traits\DebugInterface;

interface DriverInterface extends DebugInterface
{
    /**
     * Persist some primitive data
     *
     * @param string         $key
     * @param SerialisedData $data
     * @return void
     */
    public function persist($key, SerialisedData $data);

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
     * If the key does not exist, null should be returned
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
     * Get a range values in a sorted index
     *
     * If $min/$max are === null, they are assumed to be the started/end of the entire set
     *
     * @param string $key
     * @param bool   $reverse
     * @param int    $min
     * @param int    $max
     * @return string[]
     */
    public function getSortedIndex($key, $reverse = false, $min = null, $max = null);
}
