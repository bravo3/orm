<?php
namespace Bravo3\Orm\Mappers\Io;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\EntityManager;
use Bravo3\Orm\Mappers\Metadata\Entity;
use ProxyManager\Factory\LazyLoadingGhostFactory;
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
     * @var LazyLoadingInterface
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

    public function __construct(Entity $metadata, SerialisedData $data, EntityManager $entity_manager)
    {
        $this->metadata        = $metadata;
        $this->serialised_data = $data;
        $this->entity_manager  = $entity_manager;

        // TODO: cache here -
        // https://github.com/Ocramius/ProxyManager/blob/master/docs/tuning-for-production.md
        $factory = new LazyLoadingGhostFactory();
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
                $this->hydrateRelative($method);

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
     * Deserialise and hydrate the proxy
     *
     * @return $this
     */
    protected function hydrate()
    {
        $serialiser = $this->entity_manager->getSerialiserMap()->getSerialiser(
            $this->serialised_data->getSerialisationCode()
        );

        $serialiser->deserialise($this->metadata, $this->serialised_data, $this->getProxy());

        $this->is_hydrated = true;
        return $this;
    }

    /**
     * Check if $method refers to a related entity, if it does then use the EntityManager to hydrate that entity
     *
     * @param string $method
     * @return $this
     */
    protected function hydrateRelative($method)
    {
        if (isset($this->hydrated_methods[$method])) {
            return $this;
        }

        // TODO: hydrate FK

        $this->hydrated_methods[$method] = true;
        return $this;
    }
}
