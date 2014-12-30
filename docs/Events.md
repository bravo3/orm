Events
======
The entity manager fires pre and post events on most calls, each event is enumerated in the `Event` enum class.

Aborting Events
---------------
Both pre and post events can be aborted, however only aborting a *pre* event will actually cancel the operation.
Aborting a *post* event will not undo the action, but it does give you an opportunity to change the return value of
the operation. 

To change the return value, you must also call `$event->setAbort(true);`.

Example
-------
Manipulating an entity before persisting:

    $entity_manager->getDispatcher()->addListener(Event::PRE_PERSIST, function(PersistEvent $event) {
        $entity = $event->getEntity();
        // Manipulate entity here
    });

Manipulating the result of a retrieval:

    $entity_manager->getDispatcher()->addListener(Event::POST_RETRIEVE, function(RetrieveEvent $event) {
        $entity = $event->getEntity();
        if ($entity instanceof CoolEntity) {
            $entity->setCoolFactor(10);
        } else {
            $event->setReturnValue(new CoolEntity());
            $event->setAbort(true);
        }
    });

The above example will either modify the retrieved entity, or completely override the return result.