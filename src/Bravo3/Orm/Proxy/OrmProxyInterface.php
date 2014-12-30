<?php
namespace Bravo3\Orm\Proxy;

use ProxyManager\Proxy\LazyLoadingInterface;

interface OrmProxyInterface extends LazyLoadingInterface
{
    /**
     * Mark a relationship as modified
     *
     * @param string $name
     * @return $this
     */
    public function setRelativeModified($name);

    /**
     * Check if a relationship has been modified
     *
     * @param string $name
     * @return bool
     */
    public function isRelativeModified($name);

    /**
     * Set an indexes original value
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setIndexOriginalValue($name, $value);

    /**
     * Get an indexes original value
     *
     * @param string $name
     * @return string
     */
    public function getIndexOriginalValue($name);

    /**
     * Set the original ID
     *
     * @param string $value
     * @return $this
     */
    public function setOriginalId($value);

    /**
     * Get the original ID
     *
     * @return string
     */
    public function getOriginalId();
}
