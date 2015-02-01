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
use Bravo3\Orm\Services\Io\Reader;
use Bravo3\Orm\Services\Io\Writer;
use Bravo3\Orm\Traits\ProxyAwareTrait;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

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
     * Create a raw entity manager
     *
     * Do not construct an entity manager directly or it will lack access interceptors which are responsible for
     * caching and event dispatching.
     *
     * @see EntityManager::build()
     *
     * @param DriverInterface    $driver
     * @param MapperInterface    $mapper
     * @param SerialiserMap      $serialiser_map
     * @param KeySchemeInterface $key_scheme
     * @param Configuration      $configuration
     */
    protected function __construct(
        DriverInterface $driver,
        MapperInterface $mapper,
        SerialiserMap $serialiser_map = null,
        KeySchemeInterface $key_scheme = null,
        Configuration $configuration = null
    ) {
        $this->driver     = $driver;
        $this->mapper     = $mapper;
        $this->key_scheme = $key_scheme ?: $driver->getPreferredKeyScheme();
        $this->config     = $configuration ?: new Configuration();

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
     * @param DriverInterface    $driver
     * @param MapperInterface    $mapper
     * @param SerialiserMap      $serialiser_map
     * @param KeySchemeInterface $key_scheme
     * @param Configuration      $configuration
     * @return EntityManager
     */
    public static function build(
        DriverInterface $driver,
        MapperInterface $mapper,
        SerialiserMap $serialiser_map = null,
        KeySchemeInterface $key_scheme = null,
        Configuration $configuration = null
    ) {
        $em_conf    = $configuration ?: new Configuration();
        $proxy_conf = new \ProxyManager\Configuration();
        $proxy_conf->setProxiesTargetDir($em_conf->getCacheDir());
        $proxy_conf->setProxiesNamespace(Writer::PROXY_NAMESPACE);

        $proxy_factory      = new AccessInterceptorValueHolderFactory($proxy_conf);
        $interceptor_factor = new EntityManagerInterceptorFactory();

        $em    = new self($driver, $mapper, $serialiser_map, $key_scheme, $em_conf);
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

        if ($entity instanceof OrmProxyInterface) {
            $local_id = $entity->getOriginalId();
        } else {
            $local_id = $reader->getId();
        }

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
     * @return object
     */
    public function retrieve($class_name, $id)
    {
        $metadata = $this->mapper->getEntityMetadata($class_name);

        $serialised_data = $this->driver->retrieve(
            $this->key_scheme->getEntityKey($metadata->getTableName(), $id)
        );

        $writer = new Writer($metadata, $serialised_data, $this);
        return $writer->getProxy();
    }

    /**
     * Retrieve an entity
     *
     * @param string $class_name
     * @param string $index_name
     * @param string $index_key
     * @return object
     */
    public function retrieveByIndex($class_name, $index_name, $index_key)
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

        return $this->retrieve($class_name, $id);
    }

    /**
     * Create a query against a table matching one or more indices
     *
     * @param IndexedQuery $query
     * @return QueryResult
     */
    public function indexedQuery(IndexedQuery $query)
    {
        return $this->getQueryManager()->indexedQuery($query);
    }

    /**
     * Get all foreign entities ordered by a sort column
     *
     * If you have applied a limit to the query but need to know the full size of the unfiltered set, you must set
     * $check_full_set_size to true to gather this information at the expense of a second database query.
     *
     * @param SortedQuery $query
     * @param bool        $check_full_set_size
     * @return QueryResult
     */
    public function sortedQuery(SortedQuery $query, $check_full_set_size = false)
    {
        return $this->getQueryManager()->sortedQuery($query, $check_full_set_size);
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
}
