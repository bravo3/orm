Queries
=======
You can perform simple search queries on indexed fields to yield results similar to traditional SELECT SQL queries.
You can only do this on *indices*, not field values themselves, as field values are serialised and would require a lot
more effort to perform the query.

Queries are not ideal for performance and subtracts from the benefits of a document model database. Use only as a last
resort.

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

You can now create queries on the two indices we have:

    $result = $em->query(new Query('SluggedArticle', ['slug' => 'stuff*']));
    
The QueryResult object will only contain a list of ID's on return. When you request an entity, either directly by
asking for it or using it in a loop, the entities will be retrieved on demand.
 
`QueryResult` is an iterator, you can traverse it in a loop:

    foreach ($result as $entity) {
        echo $entity->getName()."\n";
    }

It is also an ArrayAccess implementation, you can request entities by ID:

    // Array of ID's in the query result
    var_dump($result->getIdList());
    
    // Get an individual entity, without making database calls to retrieve any other entities:
    $entity = $result['104'];
    echo $entity->getName()."\n";
    
