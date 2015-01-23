Queries
=======

Sorted Queries
--------------
The sorted query is the most efficient way to perform a query. Sorted queries work on a z-index and can be sorted in
ascending or descending order, as well as efficiently select a range for the query.

Sorted queries however must work on a numeric score for sorting, thus only the following field types can be properly
sorted:

* int
* decimal
* datetime (converted to a unix timestamp)

Lexicographical sorting is not natively supported, however it can be pseudo-sorted by serialising the string in the
form of a decimal number. As the bit length of a decimal is finite, only 7 characters of a limited character-set 
can be encoded (printable ASCII only).

    /**
     * @var Article[]
     * @Orm\OneToMany(
     *      target="Article",
     *      inversed_by="category",
     *      sortable_by={"title", "last_modified"}
     * )
     */
    protected $articles;

Sort columns can only be applied to 'to-many' relationships, all you need to do is add a 'sortable_by' clause to the
relationship metadata.

    $results = $em->sortedQuery(
            new SortedQuery($category, 'articles', 'last_modified', Direction::DESC(), 5, -6)
    );

The above will cut off the first and last 5 results from the entire set and result a QueryResult object.

The QueryResult object is a zero-indexed traversable, countable and an ArrayAccess implementation. It is a 
lazy-loading entity holder, entities will only be hydrated when you retrieve them.

    // Get the 3rd result:
    $entity = $result[2];   // database query to get entity here

### Conditional Sorted Queries
You want a list of all articles in a category ordered by their last modified timestamp, but only those with their 
'publish' flag set to true.

If you do a normal query you won't be able to filter the unpublished articles natively. You'll need to post-filter them
which will mean your pagination now falls out of order.

The solution is to create conditions on your sort indices:

    /**
     * @var Article[]
     * @Orm\OneToMany(
     *      target="Article",
     *      inversed_by="category",
     *      sortable_by={
     *          @Orm\Sortable(column="last_modified", conditions={
     *              @Orm\Condition(column="published", value=true),
     *              @Orm\Condition(column="id", value=50, comparison=">")
     *          }), "id"
     *      })
     */
    protected $articles;

This example will create two sort indices, one by `last_modified` with the condition that the article is published, and
that the article's ID is greater than 50. The second index is by ID without conditions.

It's important to note that you cannot retrieve an unfiltered version of the `last_modified` index now, the database
will only add articles to the index if the conditions are met. 

When using annotation mapping, you can add items to the `sortable_by` list either by a string or a @Sortable 
annotation, however you can only add conditions when using @Sortable with @Condition annotations.

Indexed Queries
---------------
You can perform simple search queries on indexed fields to yield results similar to traditional SELECT SQL queries.
You can only do this on *indices*, not field values themselves, as field values are serialised and would require a lot
more effort to perform the query.

Indexed Queries are not ideal for performance and subtracts from the benefits of a document model database. Use only 
as a last resort - these queries are KEY SCANS.

Assume the following entity:

    /**
     * @Entity(indices={
     *      @Index(name="slug", columns={"slug"}),
     *      @Index(name="name", columns={"name"})
     * })
     */
    class SluggedArticle
    {
        /**
         * @var int
         * @Id
         * @Column(type="int")
         */
        protected $id;
    
        /**
         * @var string
         * @Column(type="string")
         */
        protected $name;
    
        /**
         * @var string
         * @Column(type="string")
         */
        protected $slug;
    }

You can now create queries on any of the indices we have:

    $result = $em->query(new IndexQuery('SluggedArticle', ['slug' => 'hello*']));
    
If you specify multiple indices in the Query, the result will be an intersection of both indices (i.e. the entity must
match both index values):

    $query = new IndexQuery('SluggedArticle', [
        'slug' => 'hello*',
        'name' => '*world',
    ]);
    
The QueryResult object will only contain a list of ID's on return. When you request an entity, either directly by
asking for it or using it in a loop, the entities will be retrieved on demand.
 
`QueryResult` is an iterator, you can traverse it in a loop:

    foreach ($result as $entity) {
        echo $entity->getName()."\n";
    }

It is also an ArrayAccess implementation, you can request entities by index:

    // Array of ID's in the query result
    var_dump($result->getIdList());
    
    // Get an individual entity at index 2, without making database calls to retrieve any other entities:
    $entity = $result[2];
    echo $entity->getName()."\n";
    
Wildcards
---------
Queries can basic glob-style wildcards -
 
    *  - match any value 
    ?  - match any single character
    [] - match a set
    
To escape special characters, use the backslash (e.g. "hello\?")

Examples:

    h?llo matches hello, hallo and hxllo
    h*llo matches hllo and heeeello
    h[ae]llo matches hello and hallo, but not hillo
