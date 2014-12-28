<?php
namespace Bravo3\Orm\Serialisers;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Mappers\Metadata\Entity;

interface SerialiserInterface
{
    /**
     * Get a unique code for this serialiser, used as the header for the stored object
     *
     * @return string
     */
    public function getSerialiserCode();

    /**
     * Serialise the entity
     *
     * @param Entity $metadata
     * @param object $entity
     * @return SerialisedData
     */
    public function serialise(Entity $metadata, $entity);

    /**
     * Deserialise the entity
     *
     * @param Entity         $metadata Metadata object to match the entity
     * @param SerialisedData $data     Data to deserialise
     * @param object         $entity   Entity to hydrate
     */
    public function deserialise(Entity $metadata, SerialisedData $data, $entity);
}
