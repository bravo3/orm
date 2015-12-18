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
    public $unique_indices = [];

    /**
     * @var array
     */
    public $sorted_indices = [];
}
