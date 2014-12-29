Race Conditions in Relationships
================================
Relationships are persisted when the entity is which can create a potential race condition with new entities:

If you have two new entities and create a relationship between them then persist, the relationship isn't set until you
flush the entity manager. Therefore, at the time you persist the second entity, when it checks for relationships it 
will not see any, and therefore not think to clear the relationship the first persistence is creating. 

While this might appear to be ideal behaviour, as you don't want to clear a relationship just because you didn't set
it on both sides, what it will do is clear the local side of the relationship (set to null) and this creates a
desynchronisation between the forward and inverse indices.

To avoid this, when new entities are persisted without setting a relationship, no relationship data is persisted. This
solves the problem of index desynchronisation but could then create unexpected behaviour if the entity already existed
and contained existing relationships. Your new entity will overwrite primitive data structures but maintain the
relationships from the preexisting entity.

To avoid unexpected behaviour, you should always retrieve an existing entity and modify its data/relationships instead
of simply trying to overwrite it with a new instance of the entity.
