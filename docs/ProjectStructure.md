Project Structure
=================

### Annotations
Contains the annotation classes for entity metadata.

### Drivers
A strategy pattern approach to implementing database drivers.

### Enum
Enumeration classes, all enumeration class should extend the `eloquent/enumeration` library.

### Exceptions
All exceptions in the project implement the OrmException, and extend an SPL exception.

### KeySchemes
The classes allow you to implement different strategies for the key names to documents stored in the database. 
Different databases may have different conventions, or your application may prefer a custom scheme to the document
structure.

### Mappers
Mappers allow you to handle the entity metadata in differing strategies. Such strategies could be a YAML configuration
file or annotation reader.

### Proxy
Entities returned from the entity manager are ghost proxies, this folder contains the classes required to manage those
proxies.

### Serialisers
Strategies for different serialisation techniques, such as JSON (the default serialiser). 

### Services
All primary services
