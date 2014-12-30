<?php
namespace Bravo3\Orm\Proxy\Methods;

use ProxyManager\Generator\MethodGenerator;
use ProxyManager\Generator\ParameterGenerator;

class getIndexOriginalValue extends MethodGenerator
{
    public function __construct()
    {
        parent::__construct('getIndexOriginalValue');
        $this->setParameter(new ParameterGenerator('name', 'string'));
        $this->setBody('return isset($this->_original_indices[$name]) ? $this->_original_indices[$name] : null;');
    }
}
