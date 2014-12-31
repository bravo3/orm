Automatically Update Create/Modify Time
=======================================
The ORM has native support to do this, however a custom implementation could be achieved by adding your own event
subscribers to the pre-persist event.

Native Support
--------------
To use the built-in functionality you need to implement the `CreateModifyInterface` on your entity class:

    /**
     * @Orm\Entity
     */
    class SomeEntity implements CreateModifyInterface
    {
        // ..
    }

This will give you a create time and a last-modified time - these fields will be automatically set on the pre-persist
event (not flush).


Custom Implementation
---------------------
If you wanted to build your own implementation or automatic field setters, you can do this by registering an event
listener or subscriber to the `Event::PRE_PERSIST` event.
 
    $entity_manager->getDispatcher()->addListener(Event::PRE_PERSIST, function(PersistEvent $event) {
        $entity = $event->getEntity();
        // Manipulate entity here
    });

You may prefer to use subscribers instead of listeners for a more pure OO approach. See the `CreateModifySubscriber`
class for an example implementation.
