Serialisers
===========
Serialisers implement a strategy pattern and provide different methods of storing data. Multiple serialisers can be
registered to the entity manager, allowing you to deserialise many different encodings, but when storing entities the
default serialiser will be used. You can change the default serialiser by referencing the serialiser map on the entity
manager.

Examples of alternative serialisers could be to use compression, or store in a format specific to the database (e.g.
an API server might have a specific document format). 

Serialiser Code
---------------
The serialiser code is a 4-byte string that is unique to each serialiser. This code may be used as metadata for the
serialised document if the database engine supports such, or as a header prefixed to the serialised data if it does not
support parallel metadata. For the purpose of using the serialiser code as a header, it is important that the code is
exactly 4 bytes long.
