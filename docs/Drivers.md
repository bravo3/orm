Database Drivers
================
The database drivers are responsible for the low-level communication with the database. Noting that databases should
typically be document databases (although you could use a relational database server) the entity manager will to some
degree organise the indices so that the database itself only needs to worry about sets and documents.

If the document database does not support sets, the driver will need additional logic to maintain a set as the entity
manager expects sets to be supported.
