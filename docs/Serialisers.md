Serialisers
===========

Serialiser Code
---------------
The serialiser code is a 4-byte string that is unique to each serialiser. This code may be used as metadata for the
serialised document if the database engine supports such, or as a header prefixed to the serialised data if it does not
support parallel metadata. For the purpose of using the serialiser code as a header, it is important that the code is
exactly 4 bytes long.
