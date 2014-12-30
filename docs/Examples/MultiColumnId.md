Multi-Column IDs
================
An ID can be a combination of columns of varying types:

    <?php
    use Bravo3\Orm\Annotations\Column;
    use Bravo3\Orm\Annotations\Entity;
    use Bravo3\Orm\Annotations\Id;

    /**
     * @Entity
     */
    class MultiIdEntity
    {
        /**
         * @var int
         * @Id
         * @Column(type="int")
         */
        protected $id1;
    
        /**
         * @var string
         * @Id
         * @Column(type="string")
         */
        protected $id2;
    
    }

An ID will always be converted to a string, regards of the number of columns. In the above example, if `$id1` is '10' 
and `$id2` is 'bravo', the ID sent to the database would be "10.bravo".

When performing a retrieval, you need to use the concatenated ID:

    $entity = $em->retrieve('MultiIdEntity', '10.bravo');
    
If you use a boolean field in your ID, it will be converted to an integer (0 or 1). DateTime fields cannot be used as
an index.
