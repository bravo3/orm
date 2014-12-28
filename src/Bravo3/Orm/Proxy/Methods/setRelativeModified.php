<?php
namespace Bravo3\Orm\Proxy\Methods;

use ProxyManager\Generator\MethodGenerator;
use ProxyManager\Generator\ParameterGenerator;

class setRelativeModified extends MethodGenerator
{
    public function __construct()
    {
        parent::__construct('setRelativeModified');
        $this->setParameter(new ParameterGenerator('name', 'string'));
        $this->setBody('$this->_modified_relatives[$name] = true; return $this;');
    }
}
