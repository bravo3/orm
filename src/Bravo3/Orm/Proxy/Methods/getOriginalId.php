<?php
namespace Bravo3\Orm\Proxy\Methods;

use ProxyManager\Generator\MethodGenerator;

class getOriginalId extends MethodGenerator
{
    public function __construct()
    {
        parent::__construct('getOriginalId');
        $this->setBody('return $this->_original_id;');
    }
}
