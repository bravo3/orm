Index Types
===========
The entity manager will maintain many different types of indices, these indices are responsible for:

1. Maintaining foreign relationships
2. Providing non-PK indices for entities, allowing retrievals on fields other than the ID
3. Sorted indices, allowing you to sort a table or a relationship

(1) Foreign Relationships
-------------------------
These are known generally as 'relationships', and refer to the linking of two entities. These indices come in either
key-value or list form, depending on the relationship type (one-to-many, many-to-one, etc)

(2) General Indices
-------------------
When using the word "index", it will normally imply a generic index, indexing one or more fields in a table for an
alternative method of retrieval. 

(3) Sorted Indices
------------------
A sorted index can apply to a table level, or an entity-entity relationship level. This allows you to say "get all
users, sorted by name" or perhaps "get all articles in given category, sorted by last-modified date".

The relationship level indices will duplicate the foreign relationship index, however it will contain additional sort
information.
