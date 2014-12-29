<?php
namespace Bravo3\Orm\Drivers;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;

interface DriverInterface
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
     * @param string $key
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
}
