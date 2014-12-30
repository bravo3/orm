<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Serialisers\JsonSerialiser;
use Bravo3\Orm\Serialisers\SerialiserMap;
use Bravo3\Orm\Services\Aspect\CreateModifySubscriber;
use Bravo3\Orm\Services\Aspect\EntityManagerInterceptorFactory;
use Bravo3\Orm\Services\Io\Reader;
use Bravo3\Orm\Services\Io\Writer;
use ProxyManager\Factory\AccessInterceptorValueHolderFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EntityManager
{
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
     * @var EventDispatcher
     */
    protected $dispatcher = null;

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
     */
    protected function __construct(
        DriverInterface $driver,
        MapperInterface $mapper,
        SerialiserMap $serialiser_map = null,
        KeySchemeInterface $key_scheme = null
    ) {
        $this->driver     = $driver;
        $this->mapper     = $mapper;
        $this->key_scheme = $key_scheme ?: $driver->getPreferredKeyScheme();

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
     * @return EntityManager
     */
    public static function build(
        DriverInterface $driver,
        MapperInterface $mapper,
        SerialiserMap $serialiser_map = null,
        KeySchemeInterface $key_scheme = null
    ) {
        $proxy_factory      = new AccessInterceptorValueHolderFactory();
        $interceptor_factor = new EntityManagerInterceptorFactory();

        return $proxy_factory->createProxy(
            new self($driver, $mapper, $serialiser_map, $key_scheme),
            $interceptor_factor->getPrefixInterceptors(),
            $interceptor_factor->getSuffixInterceptors()
        );
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
        return $this;
    }

    /**
     * Persist an entity
     *
     * @param object $entity
     * @return $this
     */
    public function persist($entity)
    {
        $metadata   = $this->mapper->getEntityMetadata(Reader::getEntityClassName($entity));
        $serialiser = $this->getSerialiserMap()->getDefaultSerialiser();
        $reader     = new Reader($metadata, $entity);
        $id         = $reader->getId();

        $this->driver->persist(
            $this->key_scheme->getEntityKey($metadata->getTableName(), $id),
            $serialiser->serialise($metadata, $entity)
        );

        $this->getRelationshipManager()->persistRelationships($entity, $metadata, $reader, $id);
        $this->getIndexManager()->persistIndices($entity, $metadata, $reader, $id);

        return $this;
    }

    /**
     * Delete an entity
     *
     * @param string $entity
     * @return $this
     */
    public function delete($entity)
    {
        $metadata = $this->mapper->getEntityMetadata(get_class($entity));
        $reader   = new Reader($metadata, $entity);

        $this->driver->delete(
            $this->key_scheme->getEntityKey($metadata->getTableName(), $reader->getId())
        );

        return $this;
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
        $entity = $writer->getProxy();

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
        return $this;
    }

    /**
     * Purge the current unit of work, clearing any unexecuted commands
     *
     * @return $this
     */
    public function purge()
    {
        $this->driver->purge();
        return $this;
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
    public function getIndexManager()
    {
        if ($this->index_manager === null) {
            $this->index_manager = new IndexManager($this);
        }

        return $this->index_manager;
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
}
