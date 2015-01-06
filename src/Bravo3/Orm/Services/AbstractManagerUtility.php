<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Services\Io\Reader;

abstract class AbstractManagerUtility
{
    /**
     * @var EntityManager
     */
    protected $entity_manager;

    public function __construct(EntityManager $entity_manager)
    {
        $this->entity_manager = $entity_manager;
    }

    /**
     * Get the driver belonging to the entity manager
     *
     * @return DriverInterface
     */
    protected function getDriver()
    {
        return $this->entity_manager->getDriver();
    }

    /**
     * Get the key scheme belonging to the entity manager
     *
     * @return KeySchemeInterface
     */
    protected function getKeyScheme()
    {
        return $this->entity_manager->getKeyScheme();
    }

    /**
     * Get the mapper belonging to the entity manager
     *
     * @return MapperInterface
     */
    protected function getMapper()
    {
        return $this->entity_manager->getMapper();
    }

    /**
     * Build requisite services & data if they were not provided
     *
     * @param object $entity
     * @param Entity $metadata
     * @param Reader $reader
     * @param string $local_id
     * @return array
     */
    protected function buildPrerequisites($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        if (!$metadata) {
            $metadata = $this->getMapper()->getEntityMetadata($entity);
        }

        if (!$reader) {
            $reader = new Reader($metadata, $entity);
        }

        if (!$local_id) {
            $local_id = $reader->getId();
        }

        return [$metadata, $reader, $local_id];
    }

    /**
     * Get the full ID of an entity
     *
     * @param object $entity
     * @return string
     */
    protected function getEntityId($entity)
    {
        $metadata = $this->getMapper()->getEntityMetadata(Reader::getEntityClassName($entity));
        $reader   = new Reader($metadata, $entity);
        return $reader->getId();
    }
}
