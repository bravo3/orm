<?php
namespace Bravo3\Orm\Query;

use Bravo3\Orm\Services\Io\Reader;

abstract class AbstractQuery implements QueryInterface
{
    /**
     * @var string
     */
    protected $class_name;

    /**
     * @param string|object $class_name Class name or entity
     */
    public function __construct($class_name)
    {
        $this->setClassName($class_name);
    }

    /**
     * Get entity class name
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->class_name;
    }

    /**
     * Set entity class name by name or entity object
     *
     * @param string|object $class_name
     * @return void
     */
    public function setClassName($class_name)
    {
        $this->class_name = Reader::getEntityClassName($class_name);
    }
}
