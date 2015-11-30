<?php
namespace Bravo3\Orm\Mappers\Chained;

use Bravo3\Orm\Exceptions\NoMetadataException;
use Bravo3\Orm\Mappers\AbstractMapper;
use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Services\Io\Reader;

/**
 * A mapper that will check multiple sub-mappers in order
 */
class ChainedMapper extends AbstractMapper
{
    /**
     * @var MapperInterface[]
     */
    protected $mappers;

    /**
     * @param MapperInterface[] $mappers
     */
    public function __construct(array $mappers = [])
    {
        $this->mappers = $mappers;
    }

    /**
     * Get the metadata for an entity, including column information
     *
     * @param string|object $entity Entity or class name of the entity
     * @return Entity
     */
    public function getEntityMetadata($entity)
    {
        foreach ($this->mappers as $mapper) {
            try {
                return $mapper->getEntityMetadata($entity);
            } catch (NoMetadataException $e) {
            }
        }

        if (is_object($entity)) {
            $class = Reader::getEntityClassName($entity);
        } else {
            $class = $entity;
        }
        throw new NoMetadataException("No metadata found for '".$class."'");
    }

    /**
     * Register a new mapper
     *
     * @param MapperInterface $mapper
     * @return $this
     */
    public function registerMapper(MapperInterface $mapper)
    {
        $this->mappers[] = $mapper;
        return $this;
    }
}
