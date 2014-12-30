<?php
namespace Bravo3\Orm\Events;

use Symfony\Component\EventDispatcher\Event;

class AbortableEvent extends Event
{
    protected $abort = false;

    protected $return_value;

    /**
     * Get Abort
     *
     * @return boolean
     */
    public function getAbort()
    {
        return $this->abort;
    }

    /**
     * Set Abort
     *
     * @param boolean $abort
     * @return $this
     */
    public function setAbort($abort)
    {
        $this->abort = $abort;
        return $this;
    }

    /**
     * Get the return value if aborted
     *
     * @return mixed
     */
    public function getReturnValue()
    {
        return $this->return_value;
    }

    /**
     * Set the return value if aborted
     *
     * @param mixed $return_value
     * @return $this
     */
    public function setReturnValue($return_value)
    {
        $this->return_value = $return_value;
        return $this;
    }
}