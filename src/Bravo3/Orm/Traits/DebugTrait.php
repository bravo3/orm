<?php
namespace Bravo3\Orm\Traits;

trait DebugTrait
{
    protected $debug_mode = false;

    /**
     * Enable or disable debug mode
     *
     * @param boolean $debug_mode
     * @return void
     */
    public function setDebugMode($debug_mode)
    {
        $this->debug_mode = (bool)$debug_mode;
    }

    /**
     * Get the state of the debug mode
     *
     * @return boolean
     */
    public function getDebugMode()
    {
        return $this->debug_mode;
    }
}
