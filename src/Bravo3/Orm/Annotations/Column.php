<?php
namespace Bravo3\Orm\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Column
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $getter;

    /**
     * @var string
     */
    public $setter;
}
