<?php
namespace Bravo3\Orm\Events;

use Bravo3\Orm\Services\EntityManager;

class EntityManagerEvent extends AbortableEvent
{
    /**
     * @var EntityManager
     */
    protected $entity_manager;

    public function __construct(EntityManager $entity_manager)
    {
        $this->entity_manager = $entity_manager;
    }

    /**
     * Get EntityManager
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entity_manager;
    }
}
