<?php
namespace Bravo3\Orm\Annotations;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
class Index
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $columns;
}
