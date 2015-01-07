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

* **type**: (string) Field type: int, string, decimal, bool, datetime, object, set
* name: (string) Field name when serialised in the database, will use the property name if omitted
* getter: (string) An alternative getter function, if omitted the getter will be the camel-case variant of the field name prefixed with "get" (eg 'getSomeField()')
* setter: (string) An alternative setter function, if omitted the setter will be the camel-case variant of the field name prefixed with "set" (eg 'setSomeField($value)')
* class_name: (string) If using a serialisable object, you must specify the objects class name

### OneToMany
* target: (string) Target class name
* inverse_by: (string) Property name of inverse relationship
* sortable_by: (string[]) List of properties on foreign entity that this relationship can be sorted by

### ManyToMany
* target: (string) Target class name
* inverse_by: (string) Property name of inverse relationship
* sortable_by: (string[]) List of properties on foreign entity that this relationship can be sorted by

### ManyToOne
* target: (string) Target class name
* inverse_by: (string) Property name of inverse relationship

### OneToOne
* target: (string) Target class name
* inverse_by: (string) Property name of inverse relationship
