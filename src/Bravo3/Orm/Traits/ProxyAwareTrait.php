<?php
namespace Bravo3\Orm\Traits;

trait ProxyAwareTrait
{
    /**
     * @var object
     */
    protected $proxy;

    /**
     * Get proxy object
     *
     * @return object
     */
    protected function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Set proxy object
     *
     * @param object $proxy
     */
    protected function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }
}
