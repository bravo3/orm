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
}
