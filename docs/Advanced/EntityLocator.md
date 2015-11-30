Entity Locator Service
======================
This service is intended to aid migration and export tasks with the ability to locate all entity classes within a
given filesystem directory. This service may validate the entity against a provided entity manager (and it's mapping
table), however if an entity manager is not provided, it will assume all detected classes are valid entities.

The `Bravo3\Orm\Services\EntityLocator` classes `locateEntities()` function will perform the search for you, you must
offer it a base directory and the namespace prefix of that directory. For example:

    $locator     = new EntityLocator($entity_manager);
    $class_names = $locator->locateEntities('src/Foo/Entity', 'Foo\Entity');
    
This will search for entities in the 'src/Foo/Entity' directory, and assume they have class names in the form
`Foo\Entity\MyEntity`, etc. This locator is intended for PSR-0 or PSR-4 naming, and requires a corresponding auto-loader
has already been installed in the PHP process.
