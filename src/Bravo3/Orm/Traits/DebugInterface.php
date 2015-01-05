<?php
namespace Bravo3\Orm\Traits;

interface DebugInterface
{
    /**
     * Enable or disable debug mode
     *
     * @param bool $mode
     * @return void
     */
    public function setDebugMode($mode);

    /**
     * Get the state of the debug mode
     *
     * @return boolean
     */
    public function getDebugMode();

    /**
     * Create a debug log
     *
     * @param string $msg
     * @return void
     */
    public function debugLog($msg);
}
