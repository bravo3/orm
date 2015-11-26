<?php
namespace Bravo3\Orm\Mappers;

abstract class AbstractMapper implements MapperInterface
{
    /**
     * @var MapperInterface
     */
    private $external_mapper = null;

    /**
     * Set a mapper to use when resolving external entities (eg related classes)
     *
     * If you do not set this, the mapper should use itself to get metadata on external entities. This function is most
     * useful when using a chained mapper.
     *
     * @param MapperInterface $mapper
     */
    public function setExternalMapper(MapperInterface $mapper = null)
    {
        $this->external_mapper = $mapper;
    }

    /**
     * Get a mapper used for resolving metadata on related entities
     *
     * @return MapperInterface
     */
    protected function getExternalMapper()
    {
        if ($this->external_mapper !== null) {
            return $this->external_mapper;
        } else {
            return $this;
        }
    }
}
