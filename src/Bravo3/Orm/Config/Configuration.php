<?php
namespace Bravo3\Orm\Config;

class Configuration
{
    /**
     * @var string
     */
    protected $cache_dir;

    /**
     * @var bool
     */
    protected $hydration_exceptions_as_events;

    public function __construct($cache_dir = null, $hydration_exceptions_as_events = null)
    {
        $this->cache_dir                      = $cache_dir;
        $this->hydration_exceptions_as_events = $hydration_exceptions_as_events;
    }

    /**
     * Get CacheDir
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cache_dir;
    }

    /**
     * Set CacheDir
     *
     * @param string $cache_dir
     * @return $this
     */
    public function setCacheDir($cache_dir)
    {
        $this->cache_dir = $cache_dir;
        return $this;
    }

    /**
     * getHydrationExceptionsAsEvents
     *
     * @return bool
     */
    public function getHydrationExceptionsAsEvents()
    {
        return $this->hydration_exceptions_as_events;
    }

    /**
     * setHydrationExceptionsAsEvents
     *
     * @param bool
     * @return $this
     */
    public function setHydrationExceptionsAsEvents($hydration_exceptions_as_events)
    {
        $this->hydration_exceptions_as_events = $hydration_exceptions_as_events;

        return $this;
    }

}
