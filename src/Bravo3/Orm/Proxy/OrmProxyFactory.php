<?php
namespace Bravo3\Orm\Proxy;

use ProxyManager\Factory\LazyLoadingGhostFactory;

class OrmProxyFactory extends LazyLoadingGhostFactory
{
    /**
     * @var OrmProxyFactory|null
     */
    private $generator;

    /**
     * {@inheritDoc}
     */
    protected function getGenerator()
    {
        return $this->generator ?: $this->generator = new OrmProxyGenerator();
    }
}