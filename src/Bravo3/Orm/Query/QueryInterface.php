<?php
namespace Bravo3\Orm\Query;

interface QueryInterface
{
    /**
     * Get entity class name
     *
     * @return string
     */
    public function getClassName(): string;

    /**
     * Set entity class name by name or entity object
     *
     * @param string|object $class_name
     * @return void
     */
    public function setClassName($class_name);
}
