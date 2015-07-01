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

    /**
     * @var array
     */
    public $indices = [];

    /**
     * @var array
     */
    public $sortable_by = [];
}
