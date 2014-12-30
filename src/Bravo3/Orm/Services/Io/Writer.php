<?php
namespace Bravo3\Orm\Services\Io;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Enum\RelationshipType;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\Relationship;
use Bravo3\Orm\Proxy\OrmProxyFactory;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\EntityManager;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * Responsible for creating lazy-loading proxy objects of serialised data, that will deserialise and look-up related
 * entities as required. For this purpose, it requires a reference to the entity manager to access serialisers and
 * look-up other entities when requested.
 */
class Writer
{
    /**
     * @var Entity
     */
    protected $metadata;

    /**
     * @var SerialisedData
     */
    protected $serialised_data;

    /**
     * @var EntityManager
     */
    protected $entity_manager;

    /**
     * @var OrmProxyInterface
     */
    protected $proxy;

    /**
     * @var bool
     */
    protected $is_hydrated = false;

    /**
     * @var array
     */
    protected $hydrated_methods = [];

    /**
     * @var Reader
     */
    protected $reader = null;

    public function __construct(Entity $metadata, SerialisedData $data, EntityManager $entity_manager)
    {
        $this->metadata        = $metadata;
        $this->serialised_data = $data;
        $this->entity_manager  = $entity_manager;

        // TODO: cache here -
        // https://github.com/Ocramius/ProxyManager/blob/master/docs/tuning-for-production.md
        $factory = new OrmProxyFactory();
        $writer  = $this;

        // Create the proxy with a Closure responsible for lazy-loading via this instance of the Writer
        $this->proxy = $factory->createProxy(
            $metadata->getClassName(),
            function (LazyLoadingInterface $proxy, $method, array $parameters, & $initializer) use ($writer) {

                // Hydrate the primitive data
                if (!$writer->is_hydrated) {
                    $writer->hydrate();
                }

                // Hydrate foreign relatives on request
                $this->examineMethodForHydration($method);

                return true;
            }
        );
    }

    /**
     * Get the proxy of the entity
     *
     * @return object
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Deserialise and hydrate all primitive data in the proxy (not relationships)
     *
     * @return $this
     */
    public function hydrate()
    {
        $serialiser = $this->entity_manager->getSerialiserMap()->getSerialiser(
            $this->serialised_data->getSerialisationCode()
        );

        /** @var OrmProxyInterface $proxy */
        $proxy = $this->getProxy();

        // Deserialise and hydrate the entity
        $serialiser->deserialise($this->metadata, $this->serialised_data, $proxy);

        // Save the original state of all indices so we can compare on consequent persist calls
        $proxy->setOriginalId($this->getReader()->getId());
        foreach ($this->metadata->getIndices() as $index) {
            $proxy->setIndexOriginalValue($index->getName(), $this->getReader()->getIndexValue($index));
        }

        $this->is_hydrated = true;
        return $this;
    }

    /**
     * Check if $method refers to a related entity, if it does then use the EntityManager to hydrate that entity
     *
     * @param string $method
     * @return $this
     */
    private function examineMethodForHydration($method)
    {
        if (isset($this->hydrated_methods[$method])) {
            return $this;
        }

        $property = $this->metadata->getPropertyFor($method);
        if ($property) {
            $relative = $this->metadata->getRelationshipByName($property);
            if ($relative) {
                $this->proxy->setRelativeModified($property);
                $this->hydrateRelative($relative);
            }
        }

        $this->hydrated_methods[$method] = true;
        return $this;
    }

    /**
     * Hydrate a relationship
     *
     * @param Relationship $relative
     * @return $this
     */
    public function hydrateRelative(Relationship $relative)
    {
        $setter = $relative->getSetter();
        $key    = $this->entity_manager->getKeyScheme()->getRelationshipKey($relative, $this->getReader()->getId());

        if (RelationshipType::isMultiIndex($relative->getRelationshipType())) {
            $items = [];
            $ids   = $this->entity_manager->getDriver()->getMultiValueIndex($key);
            foreach ($ids as $id) {
                $items[] = $this->entity_manager->retrieve($relative->getTarget(), $id);
            }
            $this->proxy->$setter($items);
        } else {
            $id = $this->entity_manager->getDriver()->getSingleValueIndex($key);
            if ($id) {
                $this->proxy->$setter($this->entity_manager->retrieve($relative->getTarget(), $id));
            }
        }

        return $this;
    }

    /**
     * Lazy-loading Reader for current proxy
     *
     * @return Reader
     */
    protected function getReader()
    {
        if ($this->reader === null) {
            $this->reader = new Reader($this->metadata, $this->proxy);
        }

        return $this->reader;
    }
}
