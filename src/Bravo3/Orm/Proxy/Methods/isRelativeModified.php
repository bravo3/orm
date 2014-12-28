<?php
namespace Bravo3\Orm\Proxy\Methods;

use ProxyManager\Generator\MethodGenerator;
use ProxyManager\Generator\ParameterGenerator;

class isRelativeModified extends MethodGenerator
{
    public function __construct()
    {
        parent::__construct('isRelativeModified');
        $this->setParameter(new ParameterGenerator('name', 'string'));
        $this->setBody('return isset($this->_modified_relatives[$name]);');
    }
}
