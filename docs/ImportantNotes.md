Important Notes
===============

Lazy-loading
------------
* Primitive entity data is not deserialised until you first access it
* All primitive data is hydrated when any other primitive property is first accessed
* Relationships are not fetched from the database until you first read it
* Different relationships are not hydrated together, they will be individually fetched when each relationship is accessed
* List relationships (eg One-To-Many) will be fully hydrated when the list is accessed

Relationship Columns
--------------------
* Relationships are not columns, you cannot mix a @Column annotation with a @OneToMany (or similar) annotation
* Relationships are not serialised in the main entity, they are handled by auxiliary indices
* You cannot reference relationships in entity's indices 
* Adding inverse relationships to existing relationships will not hydrate the inverse index, you will have a desychronised index
