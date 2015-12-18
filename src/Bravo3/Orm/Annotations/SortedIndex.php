<?php
namespace Bravo3\Orm\Annotations;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
class SortedIndex
{
    /**
     * @var string
     */
    public $name = null;

    /**
     * @var string
     */
    public $column;

    /**
     * @var array
     */
    public $conditions;
}
