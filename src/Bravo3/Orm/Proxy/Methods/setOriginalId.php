<?php
namespace Bravo3\Orm\Proxy\Methods;

use ProxyManager\Generator\MethodGenerator;
use ProxyManager\Generator\ParameterGenerator;

class setOriginalId extends MethodGenerator
{
    public function __construct()
    {
        parent::__construct('setOriginalId');
        $this->setParameter(new ParameterGenerator('value', 'string'));
        $this->setBody('$this->_original_id = $value; return $this;');
    }
}
