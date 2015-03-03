<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Config\Configuration;
use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Query\IndexedQuery;
use Bravo3\Orm\Query\QueryResult;
use Bravo3\Orm\Query\SortedQuery;
use Bravo3\Orm\Serialisers\JsonSerialiser;
use Bravo3\Orm\Serialisers\SerialiserMap;
use Bravo3\Orm\Services\Aspect\CreateModifySubscriber;
use Bravo3\Orm\Services\Aspect\EntityManagerInterceptorFactory;
use Bravo3\Orm\Services\Cache\EntityCachingInterface;
use Bravo3\Orm\Services\Cache\EphemeralEntityCache;
use Bravo3\Orm\Services\Io\Reader;
use Bravo3\Orm\Services\Io\Writer;
use Bravo3\Orm\Traits\ProxyAwareTrait;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EntityManager
{
    use ProxyAwareTrait;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var MapperInterface
     */
    protected $mapper;

    /**
     * @var SerialiserMap
     */
    protected $serialiser_map;

    /**
     * @var KeySchemeInterface
     */
    protected $key_scheme;

    /**
     * @var RelationshipManager
     */
    protected $relationship_manager = null;

    /**
     * @var IndexManager
     */
    protected $index_manager = null;

    /**
     * @var QueryManager
     */
    protected $query_manager = null;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher = null;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var EntityCachingInterface
     */
    protected $cache;

    /**
     * @var bool
     */
    protected $maintenance_mode = false;

    /**
     * Create a raw entity manager
     *
     * Do not construct an entity manager directly or it will lack access interceptors which are responsible for
     * caching and event dispatching.
     *
     * @see EntityManager::build()
     *
     * @param DriverInterface        $driver
     * @param MapperInterface        $mapper
     * @param SerialiserMap          $serialiser_map
     * @param KeySchemeInterface     $key_scheme
     * @param Configuration          $configuration
     * @param EntityCachingInterface $cache
     */
    protected function __construct(
        DriverInterface $driver,
        MapperInterface $mapper,
        SerialiserMap $serialiser_map = null,
        KeySchemeInterface $key_scheme = null,
        Configuration $configuration = null,
        EntityCachingInterface $cache = null
    ) {
        $this->driver     = $driver;
        $this->mapper     = $mapper;
        $this->key_scheme = $key_scheme ?: $driver->getPreferredKeyScheme();
        $this->config     = $configuration ?: new Configuration();
        $this->cache      = $cache ?: new EphemeralEntityCache();

        if ($serialiser_map) {
            $this->serialiser_map = $serialiser_map;
        } else {
            $this->serialiser_map = new SerialiserMap();
            $this->serialiser_map->addSerialiser(new JsonSerialiser());
        }

        $this->registerDefaultSubscribers();
    }

    /**
     * Register default event subscribers
     */
    protected function registerDefaultSubscribers()
    {
        $this->getDispatcher()->addSubscriber(new CreateModifySubscriber());
    }

    /**
     * Create a new entity manager
     *
     * @param DriverInterface        $driver
     * @param MapperInterface        $mapper
     * @param SerialiserMap          $serialiser_map
     * @param KeySchemeInterface     $key_scheme
     * @param Configuration          $configuration
     * @param EntityCachingInterface $cache
     * @return EntityManager
     */
    public static function build(
        DriverInterface $driver,
        MapperInterface $mapper,
        SerialiserMap $serialiser_map = null,
        KeySchemeInterface $key_scheme = null,
        Configuration $configuration = null,
        EntityCachingInterface $cache = null
    ) {
        $em_conf    = $configuration ?: new Configuration();
        $proxy_conf = new \ProxyManager\Configuration();
        $proxy_conf->setProxiesTargetDir($em_conf->getCacheDir());
        $proxy_conf->setProxiesNamespace(Writer::PROXY_NAMESPACE);

        $proxy_factory      = new AccessInterceptorValueHolderFactory($proxy_conf);
        $interceptor_factor = new EntityManagerInterceptorFactory();

        $em    = new self($driver, $mapper, $serialiser_map, $key_scheme, $em_conf, $cache);
        $proxy = $proxy_factory->createProxy(
            $em,
            $interceptor_factor->getPrefixInterceptors(),
            $interceptor_factor->getSuffixInterceptors()
        );

        $em->setProxy($proxy);
        return $proxy;
    }

    /**
     * Get the serialiser mappings
     *
     * @return SerialiserMap
     */
    public function getSerialiserMap()
    {
        return $this->serialiser_map;
    }

    /**
     * Set the serialiser map
     *
     * @param SerialiserMap $serialiser_map
     * @return $this
     */
    public function setSerialiserMap($serialiser_map)
    {
        $this->serialiser_map = $serialiser_map;
        return $this->getProxy();
    }

    /**
     * Persist an entity
     *
     * @param object $entity Entity object to persist
     * @param int    $ttl    Optional TTL if the driver supports it, seconds past current time
     * @return $this
     */
    public function persist($entity, $ttl = null)
    {
        $metadata   = $this->mapper->getEntityMetadata(Reader::getEntityClassName($entity));
        $serialiser = $this->getSerialiserMap()->getDefaultSerialiser();
        $reader     = new Reader($metadata, $entity);
        $id         = $reader->getId();

        $this->validateId($id);

        if ($ttl) {
            $this->driver->debugLog("Caching ".$metadata->getTableName().' '.$id.' (TTL: '.$ttl.')');
        } else {
            $this->driver->debugLog("Persisting ".$metadata->getTableName().' '.$id);
        }

        $this->driver->persist(
            $this->key_scheme->getEntityKey($metadata->getTableName(), $id),
            $serialiser->serialise($metadata, $entity),
            $ttl
        );

        $this->getRelationshipManager()->persistRelationships($entity, $metadata, $reader, $id);
        $this->getIndexManager()->persistIndices($entity, $metadata, $reader, $id);

        if ($entity instanceof OrmProxyInterface) {
            $entity->setEntityPersisted($id);
        }

        return $this->getProxy();
    }

    /**
     * Throw an exception if an ID contains illegal chars
     *
     * @param string $id
     */
    private function validateId($id)
    {
        $id = str_replace(['-', '.', '_', '~', '/', '\\'], '', $id);

        if ($id && !ctype_alnum($id)) {
            throw new InvalidArgumentException("Entity ID '".$id."' contains illegal characters");
        }
    }

    /**
     * Delete an entity
     *
     * Any modifications to the entity will be ignored; the persisted state (ID, relationships) of the entity will be
     * deleted.
     *
     * If a new entity is passed to this function, any persisted entity with matching ID & class will be deleted. No
     * error will be raised if a persisted entity is not matched.
     *
     * @param object $entity
     * @return $this
     */
    public function delete($entity)
    {
        $metadata = $this->mapper->getEntityMetadata($entity);
        $reader   = new Reader($metadata, $entity);

        // Force entity hydration
        /** @noinspection PhpExpressionResultUnusedInspection */
        isset($entity->_);

        if ($entity instanceof OrmProxyInterface) {
            $local_id = $entity->getOriginalId();
        } else {
            $local_id = $reader->getId();
        }

        $this->validateId($local_id);

        // Delete document
        $this->driver->delete(
            $this->key_scheme->getEntityKey($metadata->getTableName(), $local_id)
        );

        // Delete relationships & indices
        $this->getRelationshipManager()->deleteRelationships($entity, $metadata, $reader, $local_id);
        $this->getIndexManager()->deleteIndices($entity, $metadata, $reader, $local_id);

        return $this->getProxy();
    }

    /**
     * Retrieve an entity
     *
     * @param string $class_name
     * @param string $id
     * @param bool   $use_cache
     * @return object
     */
    public function retrieve($class_name, $id, $use_cache = true)
    {
        $this->validateId($id);

        if ($use_cache && $this->cache->exists($class_name, $id)) {
            return $this->cache->retrieve($class_name, $id);
        }

        $metadata = $this->mapper->getEntityMetadata($class_name);

        $serialised_data = $this->driver->retrieve(
            $this->key_scheme->getEntityKey($metadata->getTableName(), $id)
        );

        $writer = new Writer($metadata, $serialised_data, $this);
        $entity = $writer->getProxy();
        $this->cache->store($class_name, $id, $entity);

        return $entity;
    }

    /**
     * Retrieve an entity by ClassName + Id
     *
     * @param string $class_name
     * @param int    $id
     * @param bool   $use_cache
     * @return null|object
     */
    public function retrieveEntityOrNull($class_name, $id, $use_cache = true)
    {
        try {
            return $this->retrieve($class_name, $id, $use_cache);
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Retrieve an entity by an index
     *
     * @param string $class_name
     * @param string $index_name
     * @param string $index_key
     * @param bool   $use_cache
     * @return object
     */
    public function retrieveByIndex($class_name, $index_name, $index_key, $use_cache = true)
    {
        $metadata = $this->mapper->getEntityMetadata($class_name);
        $index    = $metadata->getIndexByName($index_name);

        if (!$index) {
            throw new InvalidArgumentException('Index "'.$index_name.'" is not valid');
        }

        $id = $this->driver->getSingleValueIndex($this->key_scheme->getIndexKey($index, $index_key));

        if (!$id) {
            throw new NotFoundException('Index "'.$index_key.'" not found');
        }

        return $this->retrieve($class_name, $id, $use_cache);
    }

    /**
     * Create a query against a table matching one or more indices
     *
     * @param IndexedQuery $query
     * @param bool         $use_cache
     * @return QueryResult
     */
    public function indexedQuery(IndexedQuery $query, $use_cache = true)
    {
        return $this->getQueryManager()->indexedQuery($query, $use_cache);
    }

    /**
     * Get all foreign entities ordered by a sort column
     *
     * If you have applied a limit to the query but need to know the full size of the unfiltered set, you must set
     * $check_full_set_size to true to gather this information at the expense of a second database query.
     *
     * @param SortedQuery $query
     * @param bool        $check_full_set_size
     * @param bool        $use_cache
     * @return QueryResult
     */
    public function sortedQuery(SortedQuery $query, $check_full_set_size = false, $use_cache = true)
    {
        return $this->getQueryManager()->sortedQuery($query, $check_full_set_size, $use_cache);
    }

    /**
     * Will force a database update of an entity
     *
     * This will also convert a fresh entity to an OrmProxyInterface
     *
     * @param object $entity
     * @return object
     */
    public function refresh(&$entity)
    {
        $metadata = $this->getMapper()->getEntityMetadata($entity);
        $reader   = new Reader($metadata, $entity);

        $entity = $this->retrieve($metadata->getClassName(), $reader->getId());
        return $entity;
    }

    /**
     * Execute the current unit of work
     *
     * @return $this
     */
    public function flush()
    {
        $this->driver->flush();
        return $this->getProxy();
    }

    /**
     * Purge the current unit of work, clearing any unexecuted commands
     *
     * @return $this
     */
    public function purge()
    {
        $this->driver->purge();
        return $this->getProxy();
    }

    /**
     * Get the underlying driver
     *
     * @return DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get the key scheme
     *
     * @return KeySchemeInterface
     */
    public function getKeyScheme()
    {
        return $this->key_scheme;
    }

    /**
     * Get the entity mapper
     *
     * @return MapperInterface
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * Lazy-loading relationship manager
     *
     * @return RelationshipManager
     */
    protected function getRelationshipManager()
    {
        if ($this->relationship_manager === null) {
            $this->relationship_manager = new RelationshipManager($this);
        }

        return $this->relationship_manager;
    }

    /**
     * Lazy-loading index manager
     *
     * @return IndexManager
     */
    protected function getIndexManager()
    {
        if ($this->index_manager === null) {
            $this->index_manager = new IndexManager($this);
        }

        return $this->index_manager;
    }

    /**
     * Lazy-loading query manager
     *
     * @return QueryManager
     */
    protected function getQueryManager()
    {
        if ($this->query_manager === null) {
            $this->query_manager = new QueryManager($this);
        }

        return $this->query_manager;
    }

    /**
     * Get the event dispatcher, lazy-loading
     *
     * @return EventDispatcher
     */
    public function getDispatcher()
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new EventDispatcher();
        }

        return $this->dispatcher;
    }

    /**
     * Get Config
     *
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set Config
     *
     * @param Configuration $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Set caching service
     *
     * @param EntityCachingInterface $cache
     * @return $this
     */
    public function setCache(EntityCachingInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * Get caching service
     *
     * @return EntityCachingInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Get the state of the maintenance mode
     *
     * @return boolean
     */
    public function getMaintenanceMode()
    {
        return $this->maintenance_mode;
    }

    /**
     * Enable or disable maintenance mode
     *
     * @param bool $enabled
     * @return $this
     */
    public function setMaintenanceMode($enabled = true)
    {
        $this->maintenance_mode = $enabled;
        return $this;
    }

    /**
     * Add an event subscriber
     *
     * @param EventSubscriberInterface $subscriber
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->getDispatcher()->addSubscriber($subscriber);
    }

    /**
     * Add an event listener
     *
     * @param string   $event_name
     * @param callable $listener
     * @param int      $priority
     */
    public function addListener($event_name, $listener, $priority = 0)
    {
        $this->getDispatcher()->addListener($event_name, $listener, $priority);
    }
}
