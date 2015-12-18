<?php
namespace Bravo3\Orm\Annotations;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
class UniqueIndex
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $columns;

    /**
     * @var array
     */
    public $methods;
}
