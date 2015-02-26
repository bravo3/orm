Reference Tables
================

Preamble
--------
Inverse relationships are a pain point. A forward relationship is simple, you know the value and can easily update it,
but an inverse relationship requires a complex delta of what entities have been added, removed and maintained in a
relationship. Even if the relationship isn't reciprocated you still must maintain a reference (ref) to the entity
you have referenced from the owning entity, this is required so that modifications to the referenced entity can still
update relationships unknown to it (such as removing references to it when you delete an entity).

Refs
----
Refs contain a list of unreciprocated relationships to an entity. Reciprocated relationships do not need a ref as they
can be inversed when persisting or deleting one side of the relationship.

From a performance point of view, this means that not inversing a relationship doesn't provide a performance gain
during persist operations as the ORM must still understand both sides of a uni-directional relationship.

Breaking Relationship Constraints
---------------------------------
When you add an entity to a one-to-many relationship, the added entity (on the 'many' side) is checked if it had an
existing relationship (its 'to-one' property). In this case, any existing relationship is broken and changed to the 
new entity that just pulled it into its own relationship. This is called 'breaking relationships' internally.

A non-reciprocated one-to-many relationship will not check the 'many' side of the relationship to check if it needs to
be broken, as the 'many' entity doesn't actually contain that relationship. This in effect, means that the left side
of a non-reciprocated relationship is moot and not considered. Technically, you can then create a many-to-many scenario
even though your relationship type was one-to-many. In this case, it is your responsibility to ensure that does not
happen.
