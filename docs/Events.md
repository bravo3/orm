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

Hydration exceptions as Events
------------------------------
There is an option available to have `NotFoundException` exceptions that are thrown during hydration of related
entities be converted to `Event::HYDRATION_EXCEPTION` events containing the exception that was thrown.

    $entity_manager->getConfig()->setHydrationExceptionsAsEvents(true);
    $entity_manager->getDispatcher()->addListener(Event::HYDRATION_EXCEPTION, function(HydrationExceptionEvent $event) {
        $exception = $event->getException();
    });

This is useful only in cases where relationships still exist to non-existant entities and you would prefer to skip over
these during hydration rather than have an excption thrown which is impossible to recover from from within userland code.

The only caveat to using this approach currently is that a count of results returned from a sortedQuery may not be the
same as the number of iterations you can make over that result.
