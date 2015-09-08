Lookup Indices
==============
You can index fields other than the Id columns for purposes of retrieving entities by means other than the ID:

    <?php
    use Bravo3\Orm\Annotations\Column;
    use Bravo3\Orm\Annotations\Entity;
    use Bravo3\Orm\Annotations\Id;
    use Bravo3\Orm\Annotations\Index;
    
    /**
     * @Entity(indices={
     *      @Index(name="slug", columns={"slug"}),
     *      @Index(name="id_slug", methods={"getId", "getSlug"}),
     * })
     */
    class IndexedEntity
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
        protected $slug;
    
        /**
         * @var string
         * @Column(type="string")
         */
        protected $title;
        
        /**
         * @return string
         */
        public function getId()
        {
            return $this->id;
        }
        
        /**
         * @return string
         */
        public function getSlug()
        {
            return $this->slug;
        }
    }

The above example will save 2 indices to the database that can be used to retrieve the entity. The "slug" index would
be most useful:

    $entity = $em->retrieveByIndex('IndexedEntity', 'slug', 'my-slug');
    
However the second index demonstrates that an index can be a composition of fields:

    $entity = $em->retrieveByIndex('IndexedEntity', 'id_slug', '123.my-slug');
    
If you modify the ID column all indices will be updated when you next persist. If you change the value of the slug, 
then the index for the former slug is removed and a new index created. 

You can combine columns and methods in an index, however methods will always be appended to the index *after* 
all columns. This cannot be changed.
