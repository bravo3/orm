<?php
namespace Bravo3\ClassTools\Builder;

use Bravo3\ClassTools\Builder\Meta\ClassStruct;
use Bravo3\ClassTools\Builder\Meta\ClassType;
use Bravo3\ClassTools\Enum\PhpVersion;

/**
 * Creates code for a PHP class
 */
class ClassBuilder
{
    const PHP_HEADER = '<?php';

    /**
     * @var int
     */
    protected $php_version;

    /**
     * @param int       $php_version
     * @param CodeStyle $code_style
     */
    public function __construct($php_version = PhpVersion::PHP56, CodeStyle $code_style = null)
    {
        $this->php_version = $php_version;
        $this->code_style  = $code_style ?: new CodeStyle();
    }

    /**
     * Get CodeStyle
     *
     * @return CodeStyle
     */
    public function getCodeStyle()
    {
        return $this->code_style;
    }

    /**
     * Set CodeStyle
     *
     * @param CodeStyle $code_style
     * @return $this
     */
    public function setCodeStyle(CodeStyle $code_style)
    {
        $this->code_style = $code_style;
        return $this;
    }

    /**
     * Create code for a PHP class
     *
     * @param ClassStruct $class
     * @param bool        $php_header
     * @return string
     * @throws \Exception
     */
    public function createClassCode(ClassStruct $class, $php_header = true)
    {
        $code = new CodeBuilder($this->code_style);
        $this->createHeader($code, $class, $php_header)->createFooter($code);
        return $code->getCode();
    }

    /**
     * Create the class header
     *
     * @param CodeBuilder $code
     * @param ClassStruct $class
     * @param bool        $php_header
     * @return $this
     */
    protected function createHeader(CodeBuilder $code, ClassStruct $class, $php_header = true)
    {
        if ($php_header) {
            $code->addLine(self::PHP_HEADER, 0, 1);
        }

        $ns_pos = strrpos($class->getClassName(), "\\");
        if ($ns_pos === false) {
            $class_name = $class->getClassName();
            $namespace  = null;
        } else {
            $namespace  = substr($class->getClassName(), 0, $ns_pos);
            $class_name = substr($class->getClassName(), $ns_pos + 1);
        }

        if ($namespace) {
            $code->addLine('namespace '.$namespace.';', 0, 1);
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
            case ClassType::TYPE_CLASS:
                $line .= 'class ';
                break;
            case ClassType::TYPE_INTERFACE:
                $line .= 'interface ';
                break;
            case ClassType::TYPE_TRAIT:
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

        $code->addLine($line, 0, 0, CodeStyle::SCOPE_CLASS);

        return $this;
    }

    /**
     * Close off the class scope
     *
     * @param CodeBuilder $code
     * @return $this
     */
    protected function createFooter(CodeBuilder $code)
    {
        $code->closeScope(0, 1);
        return $this;
    }
}
