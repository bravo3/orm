<?php
namespace Bravo3\Orm\Annotations;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
class Condition
{
    /**
     * @var string
     */
    public $column;

    /**
     * @var mixed
     */
    public $value;

    /**
     * @var string
     */
    public $comparison = '=';
}
