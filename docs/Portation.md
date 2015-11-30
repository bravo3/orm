Portation
=========

Database Portation
------------------
The database porter allows you to move all data from one database to another. This might be used for migrations or for
backup import/export purposes. If you take advantage of the filesystem and phar drivers, you can easily create 
`mysqldump` style export utilities.

To start a portation process you must first create at least 2 entity managers, a source manager, and a target manager.
These managers must be configured with your source and target databases and appropriate mappings (you can use the same
entities/maps).

The `Bravo3\Orm\Services\Porter` service allows you to attach these managers via the `registerManager()` function. Once
done, you can port a table by calling `portTable()`. The first argument of this function takes a class name; if you
need a list of all entities within a folder, consider using the [Entity Locator](Advanced/EntityLocator.md) service.

Mapping Portation
-----------------
It is possible to export currently registered mappings to another format. Currently the only supported export format
is YAML mappings. 
 
To perform an export you must load an instance of `Bravo3\Orm\Mappers\Portation\MapWriterInterface` (consider 
`Bravo3\Orm\Mappers\Yaml\YamlMapWriter`) with an entity manager containing already registered mappings (your source 
mappings). Calling `compileMetadataForEntity()` will run an export process on a given class, however it will not 
generate your output files until `flush()` is called.

If you require a list of all entities within a folder, alike the database portation, you can use the 
[Entity Locator](Advanced/EntityLocator.md) service.
