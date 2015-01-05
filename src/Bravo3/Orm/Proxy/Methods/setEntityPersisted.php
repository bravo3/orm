<?php
namespace Bravo3\Orm\Proxy\Methods;

use ProxyManager\Generator\MethodGenerator;
use ProxyManager\Generator\ParameterGenerator;

class setEntityPersisted extends MethodGenerator
{
    public function __construct()
    {
        parent::__construct('setEntityPersisted');
        $this->setParameter(new ParameterGenerator('id', 'string'));
        $this->setBody('$this->_modified_relatives = []; $this->_original_id = $id; return $this;');
    }
}
