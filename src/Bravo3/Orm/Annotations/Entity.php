<?php
namespace Bravo3\Orm\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Entity
{
    /**
     * @var string
     */
    public $table;
}
