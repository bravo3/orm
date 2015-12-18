<?php
namespace Bravo3\ClassTools\Builder;

use Bravo3\ClassTools\Builder\Meta\ClassStruct;
use Bravo3\ClassTools\Builder\Meta\ClassType;

class ClassBuilder
{

    public function createClassCode(ClassStruct $class, bool $php_header = true): string
    {
        $code = new CodeBuilder();
        $this->createHeader($code, $class, $php_header);
        return $code->getCode();
    }

    protected function createHeader(CodeBuilder $code, ClassStruct $class, bool $php_header)
    {
        if ($php_header) {
            $code->addLine('<?php', 0, 1);
        }

        $ns_pos = strrpos('\\', $class->getClassName());
        if ($ns_pos === false) {
            $class_name = $class->getClassName();
            $namespace  = null;
        } else {
            $namespace  = substr($class->getClassName(), 0, $ns_pos);
            $class_name = substr($class->getClassName(), $ns_pos + 1);
        }

        if ($namespace) {
            $code->addLine('namespace '.$namespace, 0, 1);
        }

        $line = '';
        if ($class->isAbstract()) {
            $line .= 'abstract ';
        } elseif ($class->getFinal()) {
            $line .= 'final ';
        }

        switch ($class->getClassType()) {
            default:
                throw new \Exception("Unknown class type: ".$class->getClassType());
            case ClassType::STANDARD:
                $line .= 'class ';
                break;
            case ClassType::INTERFACE:
                $line .= 'interface ';
                break;
            case ClassType::TRAIT:
                $line .= 'trait ';
                break;
        }

        $line .= $class_name;

        if ($class->getExtends()) {
            $line .= ' extends '.$class->getExtends();
        }

        if ($class->getImplements()) {
            $line .= ' implements '.implode(', ', $class->getImplements());
        }

        $code->addLine($code, $line)->addLine($code, '{')->indent();
    }

}
