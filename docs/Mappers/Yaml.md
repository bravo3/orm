YAML Mapping
============
As an alternative to annotation mapping, it's possible to store all metadata on your entities in one or more YAML files.

The structure of the YAML file is identical annotations, the only difference being in how relationship associations are
loaded.

    Bravo3\Orm\Tests\Entities\Conditional\Asset:
        table: asset
        columns:
            id: { type: int, id: true }
            title: { type: string }
            time_created: { type: datetime }
            last_modified: { type: datetime }
            published: { type: bool }
            category: 
                association: mto
                target: Bravo3\Orm\Tests\Entities\Conditional\Category
                inversed_by: assets
        indices:
            slug: { columns: [id, address] }

All entities must contain their full class name as the key, without a leading backslash. `table` and `columns` are
mandatory fields, however everything else is optional.

Relationships
-------------
If a column has an `association` field it is considered a relationship and must also contain a `target` table. Valid
association values are `oto`, `mtm`, `otm` and `mto`, representing 'One to One', 'Many to Many', 'One to Many', 
'Many to One' respectively.

Sortables
---------
Table and column sortables both follow the same syntax, depending on where the `sortable` key is will change it from
a relationship sortable to a table sortable:

    Bravo3\Orm\Tests\Entities\SortedUser:
        table: sorted_user
        columns:
            id: { type: int, id: true }
            name: { type: string }
            active: { type: bool }
            leaf: 
                association: otm
                target: Bravo3\Orm\Tests\Entities\Refs\Leaf
                sortable: 
                    id: 
                        column: id
                        conditions: [{ value: true, column: published }]
        sortable:
            name_active: { column: name, conditions: [{ value: true, column: active }] }
            name_all: { column: name }

Object Columns
--------------
Columns may have the type `object`, which then adds a mandatory property of `class` which must be a serialisable object.
This works in the same manner as annotation mappings. `set` column types are assumed to be an array of scalar data 
types.

    Bravo3\Orm\Tests\Entities\ProductMore:
        table: products
        columns:
            id: { type: int, id: true }
            name: { type: string }
            short_description: { type: string }
            description: { type: string }
            price: { type: decimal }
            active: { type: bool }
            create_time: { type: datetime }
            enum: { type: object, class: Bravo3\Orm\Tests\Resources\Enum }
            list: { type: set }
