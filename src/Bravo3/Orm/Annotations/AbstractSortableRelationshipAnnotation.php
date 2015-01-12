<?php
namespace Bravo3\Orm\Annotations;

abstract class AbstractSortableRelationshipAnnotation extends AbstractRelationshipAnnotation
{
    /**
     * @var array
     */
    public $sortable_by = [];
}