<?php
namespace Bravo3\Orm\Mappers;

use Bravo3\Orm\Mappers\Metadata\Entity;

interface MapperInterface
{
    /**
     * Get the metadata for an entity, including column information
     *
     * @param string|object $entity Entity or class name of the entity
     * @return Entity
     */
    public function getEntityMetadata($entity);

    /**
     * Set a mapper to use when resolving external entities (eg related classes)
     *
     * If you do not set this, the mapper should use itself to get metadata on external entities. This function is most
     * useful when using a chained mapper.
     *
     * @param MapperInterface $mapper
     */
    public function setExternalMapper(MapperInterface $mapper = null);
}
