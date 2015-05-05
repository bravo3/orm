Document Object Relational Mapper
=================================

Purpose
-------
The purpose of this library is to blend ORM and ODM fundamentals with NoSQL database platforms, allowing you to use 
NoSQL databases with pseudo-relationships through means of a traditional entity manager.

Table of Contents
-----------------
* [Drivers](docs/Drivers.md)
* [Entity Mapping Definitions](docs/EntityDefinitions.md)
* [Events](docs/Events.md)
* [Important Notes](docs/ImportantNotes.md)
* [Index Types](docs/IndexTypes.md)
* [Key Schemes](docs/KeySchemes.md)
* [Project Structure](docs/ProjectStructure.md)
* [Queries](docs/Queries.md)
* [Race Conditions](docs/RaceConditions.md)
* [Serialisers](docs/Serialisers.md)

Further Examples
----------------
* [Auto-updating Time Stamps](docs/Examples/ModifyTime.md)
* [Getters and Setters](docs/Examples/GettersSetters.md)
* [Lookup Indices](docs/Examples/Index.md)
* [Multi-Column IDs](docs/Examples/MultiColumnId.md)
* [Sorted Relationships](docs/Queries.md)

Advanced/Internals
------------------
* [Refs](docs/Advanced/Refs.md)


Example
-------
If you intend to use Redis, please include Predis in your `composer.json`:

    "require": {
        "predis/predis": "~1.0"
    }

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
         * @param User[] $users
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
            $this->users[] = $user;
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

Major Planned Additions
-----------------------
* YAML metadata mapper
* Proxy caching
* Entity caching pool

Known Issues
------------
* Using traits in entities may cause issues with proxies when attempting to retrieve the entity
    * Caused by Zend MethodReflection claiming a comment is unterminated

Change Log
----------
### 0.3.13
* changed logic for retrieving entities. If nothing found (during writing) it will generate a new instance of the entity with the correct id
* retrieving an entity with missing relatives will now return blank new instances of the relatives

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

