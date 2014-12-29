Bravo3 Document Object Relational Mapper
========================================

Purpose
-------
The purpose of this library is to blend ORM and ODM fundamentals with NoSQL database platforms, allowing you to use 
NoSQL databases with pseudo-relationships through means of a traditional entity manager.

Example
-------
Creating an entity manager for a Redis database with annotation mappings:

    $em = new EntityManager(
        new RedisDriver(['host' => 'example.com']),
        new AnnotationMapper()
    );

Persisting a simple relationship:

    $address = new Address();
    $address->setId(1)->setStreet("123 Example St");

    $user = new User();
    $user->setId(1)->setName("Harry")->setAddress($address);
    
    $em->persist($user)->persist($address)->flush();
    
Retrieving a relationship with lazy-loading:

    $user = $em->retrieve('User', 1);   // Only user entity retrieved
    $address = $user->getAddress();     // DB call to get address made here
    
Example entity files:

*User.php*

    <?php
    use Bravo3\Orm\Annotations as Orm;
    
    /**
     * @Orm\Entity(table="users")
     */
    class User
    {
        /**
         * @var int
         * @Orm\Id
         * @Orm\Column(type="int")
         */
        protected $id;
    
        /**
         * @var string
         * @Orm\Column(type="string")
         */
        protected $name;
    
        /**
         * @var Address
         * @Orm\ManyToOne(target="Address", inversed_by="users")
         */
        protected $address;
    
        // Other getters and setters here
    
        /**
         * Get Address
         *
         * @return Address
         */
        public function getAddress()
        {
            return $this->address;
        }
    
        /**
         * Set Address
         *
         * @param Address $address
         * @return $this
         */
        public function setAddress(Address $address)
        {
            $this->address = $address;
            return $this;
        }
    }

*Address.php*

    <?php
    use Bravo3\Orm\Annotations as Orm;
    
    /**
     * @Orm\Entity
     */
    class Address
    {
        /**
         * @var int
         * @Orm\Id
         * @Orm\Column(type="int")
         */
        protected $id;
    
        /**
         * @var string
         * @Orm\Column(type="string")
         */
        protected $street;
    
        /**
         * @var User[]
         * @Orm\OneToMany(target="User", inversed_by="address")
         */
        protected $users;
    
        // Other getters and setters here
    
        /**
         * Get users
         *
         * @return User[]
         */
        public function getUsers()
        {
            return $this->users;
        }
    
        /**
         * Set users
         *
         * @param User $user
         * @return $this
         */
        public function setUsers(array $users)
        {
            $this->users = $users;
            return $this;
        }

        /**
         * Add a user
         *
         * @param User $user
         * @return $this
         */
        public function addUser(User $user)
        {
            $this->users[] $user;
            return $this;
        }
    }


Bundled Strategies
------------------

### Databases

* Redis

### Serialisation

* JSON

### Entity Metadata Mappers

* Annotation

### Key Schemes

* Configurable delimiter, defaulting to Redis-style

