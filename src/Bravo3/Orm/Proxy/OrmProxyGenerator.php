<?php
namespace Bravo3\Orm\Proxy;

use Bravo3\Orm\Proxy\Methods\isRelativeModified;
use Bravo3\Orm\Proxy\Methods\setRelativeModified;
use ProxyManager\ProxyGenerator\LazyLoadingGhostGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\PropertyGenerator;

class OrmProxyGenerator extends LazyLoadingGhostGenerator
{
    /**
     * {@inheritDoc}
     */
    public function generate(\ReflectionClass $originalClass, ClassGenerator $classGenerator)
    {
        parent::generate($originalClass, $classGenerator);

        $interfaces = $classGenerator->getImplementedInterfaces();
        $interfaces[] = 'Bravo3\Orm\Proxy\OrmProxyInterface';
        $classGenerator->setImplementedInterfaces($interfaces);

        $classGenerator->addProperty('_modified_relatives', [], PropertyGenerator::FLAG_PRIVATE);
        $classGenerator->addMethodFromGenerator(new setRelativeModified());
        $classGenerator->addMethodFromGenerator(new isRelativeModified());
    }
}
