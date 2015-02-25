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
