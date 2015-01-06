<?php
namespace Bravo3\Orm\Services\Aspect;

use Bravo3\Orm\Enum\Event;
use Bravo3\Orm\Events\PersistEvent;
use Bravo3\Orm\Traits\CreateModifyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateModifySubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            Event::PRE_PERSIST => 'updateTimeStamps'
        ];
    }

    /**
     * Check a pre-persist event for a CreateModifyTrait and update timestamps
     *
     * @param PersistEvent $event
     */
    public function updateTimeStamps(PersistEvent $event)
    {
        $entity = $event->getEntity();
        if (!($entity instanceof CreateModifyInterface)) {
            return;
        }

        $current_time = new \DateTime();

        // Force last-modified to be the current time
        $entity->setLastModified($current_time);

        // If the time created is still null, set it to the current time
        if (!$entity->getTimeCreated()) {
            $entity->setTimeCreated($current_time);
        }
    }
}
