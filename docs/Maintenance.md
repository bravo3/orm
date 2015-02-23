Database Maintenance
====================

Rebuilding Tables
-----------------
You will want to rebuild tables if:

* You want to reserialise documents (eg adding compression or changing protocol)
* You have modified a tables relationships and want to update the indices

If you have added an inverse relationship, existing entities will not automatically gain the new index keys to complete
the inverted relationship. Rebuilding tables will add the inverted index.

To rebuild:

    $maintenance = new Maintenance($entity_manager);
    $maintenance->rebuild(MyEntity::class);
    
Logging/Output
--------------
The maintenance class can accept a PSR logger, maintenance operations will treat this much like an output to show 
progress of the operation.
