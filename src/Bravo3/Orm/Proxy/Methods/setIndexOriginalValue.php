<?php
namespace Bravo3\Orm\Proxy\Methods;

use ProxyManager\Generator\MethodGenerator;
use ProxyManager\Generator\ParameterGenerator;

class setIndexOriginalValue extends MethodGenerator
{
    public function __construct()
    {
        parent::__construct('setIndexOriginalValue');
        $this->setParameter(new ParameterGenerator('name', 'string'));
        $this->setParameter(new ParameterGenerator('value', 'string'));
        $this->setBody('$this->_original_indices[$name] = $value; return $this;');
    }
}
