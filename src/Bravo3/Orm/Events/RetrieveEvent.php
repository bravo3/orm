<?php
namespace Bravo3\Orm\Events;

use Bravo3\Orm\Services\EntityManager;

class RetrieveEvent extends EntityEvent
{
    /**
     * @var string
     */
    private $class_name;

    /**
     * @var string
     */
    private $id;

    function __construct(EntityManager $manager, $class_name, $id, $entity = null)
    {
        parent::__construct($manager, $entity);
        $this->class_name = $class_name;
        $this->id         = $id;
    }

    /**
     * Get ClassName
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->class_name;
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the entity
     *
     * If the event is aborted, this entity is returned from the retieve call
     *
     * @param object $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }
}
