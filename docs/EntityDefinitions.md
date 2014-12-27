Entity Definitions
==================

Properties in bold are required.

Class-Level Definitions
-----------------------

### Entity
Defines the class is an entity. A valid entity must have an Entity definition and at least 1 Id column.

* table: (string) Name of the table in the database, will use the class name if missing


Property-Level Definitions
--------------------------

### Id
Defines the column as a primary key field, this does not imply the type ('Column' is still required) nor does it imply
that the field is auto-increment.

### Column
Defines the property as a field in the table.

* **type**: (string) Field type: int, string, decimal, bool, datetime, *classname*
* name: (string) Field name when serialised in the database, will use the property name if ommitted
* getter: (string) An alternative getter function, if ommitted the getter will be the camel-case variant of the field name prefixed with "get" (eg 'getSomeField()')
* setter: (string) An alternative setter function, if ommitted the setter will be the camel-case variant of the field name prefixed with "set" (eg 'setSomeField($value)') 

### OneToMany

### ManyToMany

### ManyToOne

### OneToOne

