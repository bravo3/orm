<?php
namespace Bravo3\Orm\Events;

use Bravo3\Orm\Services\EntityManager;

class EntityEvent extends EntityManagerEvent
{
    /**
     * @var object
     */
    protected $entity;

    public function __construct(EntityManager $entity_manager, $entity)
    {
        parent::__construct($entity_manager);
        $this->entity = $entity;
    }

    /**
     * Get Entity
     *
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }
}
