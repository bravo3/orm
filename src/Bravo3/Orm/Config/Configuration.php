<?php
namespace Bravo3\Orm\Config;

class Configuration
{
    /**
     * @var string
     */
    protected $cache_dir;

    public function __construct($cache_dir = null)
    {
        $this->cache_dir = $cache_dir;
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
}
