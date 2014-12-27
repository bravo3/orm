<?php
namespace Bravo3\Orm\Annotations;

abstract class AbstractRelationshipAnnotation
{
    /**
     * @var string
     */
    public $target;

    /**
     * @var string
     */
    public $inversed_by;

    /**
     * @var string
     */
    public $getter;

    /**
     * @var string
     */
    public $setter;
}
