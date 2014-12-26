<?php
namespace Bravo3\Orm\Mappers;

interface MapperInterface
{
    /**
     * Get the metadata for an entity
     *
     * @param object $entity
     * @return MetadataInterface
     */
    public function getEntityMetadata($entity);
}
