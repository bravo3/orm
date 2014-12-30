<?php
namespace Bravo3\Orm\Services\Aspect;

interface InterceptorFactoryInterface
{
    /**
     * Get all prefix interceptors
     *
     * @return array
     */
    public function getPrefixInterceptors();

    /**
     * Get all suffix interceptors
     *
     * @return array
     */
    public function getSuffixInterceptors();
}
