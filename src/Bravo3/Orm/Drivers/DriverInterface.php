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
}
