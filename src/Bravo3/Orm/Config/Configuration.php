<?php
namespace Bravo3\Orm\Config;

/**
 * ORM configuration container.
 */
class Configuration
{
    /**
     * @var string
     */
    protected $cache_dir;

    /**
     * @var bool
     */
    protected $force_unique_keys;

    /**
     * @param string $cache_dir         Directory to store cache objects, such as proxy object class files
     * @param bool   $force_unique_keys Unique key restraint mode
     */
    public function __construct($cache_dir = null, $force_unique_keys = true)
    {
        $this->cache_dir         = $cache_dir;
        $this->force_unique_keys = (bool)$force_unique_keys;
    }

    /**
     * Get the cache directory, used by proxy objects.
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cache_dir;
    }

    /**
     * Set the cache directory, used by proxy objects.
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
     * Get the enforcement state of unique key restraints.
     *
     * @return boolean
     */
    public function getForceUniqueKeys()
    {
        return $this->force_unique_keys;
    }

    /**
     * Set the enforcement of unique key restraints.
     *
     * @param boolean $force_unique_keys
     * @return $this
     */
    public function setForceUniqueKeys($force_unique_keys)
    {
        $this->force_unique_keys = (bool)$force_unique_keys;
        return $this;
    }
}
