Project Structure
=================

### Annotations
Contains the annotation classes for entity metadata.

### Drivers
A strategy pattern approach to implementing database drivers.

### Exceptions
All exceptions in the project implement the OrmException, and extend an SPL exception.

### KeySchemes
The classes allow you to implement different strategies for the key names to documents stored in the database. 
Different databases may have different conventions, or your application may prefer a custom scheme to the document
structure.

