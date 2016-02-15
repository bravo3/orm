<?php
namespace Bravo3\Orm\Events;

use Bravo3\Orm\Services\EntityManager;

class PubSubEvent extends EntityManagerEvent
{
    /**
     * Message received via the Pub/Sub event
     * @var string
     */
    protected $message;

    /**
     * @param EntityManager $entity_manager
     * @param               $message
     */
    public function __construct(EntityManager $entity_manager, $message)
    {
        parent::__construct($entity_manager);
        $this->message = $message;
    }

    /**
     * Returns the received message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
