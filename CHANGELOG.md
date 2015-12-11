Change Log
----------
### 0.6.0
* See [0.6.0.md](0.6.0.md)

### 0.5.6
* Added a YAML mapper
* Added map portation to easily convert between mapping types

### 0.5.5
* Add a chained mapper, allowing you to examine multiple forms of entity mapping in a single project

### 0.5.0
* Removed 'entity hydration errors as events' due to its dangerous nature
* Added a filesystem driver - designed for backup purposes but could also serve has a mini internal database

### 0.4.3
* Added the ability to perform sorted, conditional queries on all items in a table
* Added the ability to name sorted queries, allowing different configurations of conditions on the same column

### 0.3.13
* Changed logic for retrieving entities. If nothing found (during writing) it will generate a new instance of the entity with the correct id
* Retrieving an entity with missing relatives will now return blank new instances of the relatives

### 0.3.0
* Added ref tables to maintain non-reciprocated relationships
* Added restrictions to table names and entity ID values
* Changed sort index key names to include the relationship name (WARNING: will break existing data)
    * Multiple relationships with the same sort-by columns would conflict
    * Use the Maintenance#rebuild() function to repair sort indices

### 0.2.0
* Added EntityManager::refresh()
* The entity manager will now remember previously retrieved entities and return them instead of querying the database
* Added $use_cache parameter to all `retrieve*()` and `*Query()` functions on the entity manager

