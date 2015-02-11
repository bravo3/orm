<?php
namespace Bravo3\Orm\Annotations;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
class Condition
{
    /**
     * @var mixed
     */
    public $value;

    /**
     * @var string
     */
    public $column = '';

    /**
     * @var string
     */
    public $method = '';

    /**
     * @var string
     */
    public $comparison = '=';
}
