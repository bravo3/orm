<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\Index;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\Io\Reader;
use Bravo3\Orm\Traits\EntityManagerAwareTrait;

class IndexManager
{
    use EntityManagerAwareTrait;

    /**
     * Persist entity indices
     *
     * @param object $entity   Local entity object
     * @param Entity $metadata Optionally provide entity metadata to prevent recalculation
     * @param Reader $reader   Optionally provide the entity reader
     * @param string $local_id Optionally provide the local entity ID to prevent recalculation
     */
    public function persistIndices($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        if (!$metadata) {
            $metadata = $this->getMapper()->getEntityMetadata(Reader::getEntityClassName($entity));
        }

        if (!$reader) {
            $reader = new Reader($metadata, $entity);
        }

        if (!$local_id) {
            $local_id = $reader->getId();
        }

        $this->traverseIndices($metadata->getIndices(), $entity, $reader, $local_id);
    }

    /**
     * Traverse an array of indices and persist them
     *
     * @param Index[] $indices
     * @param object  $entity
     * @param Reader  $reader
     * @param string  $local_id
     */
    private function traverseIndices(array $indices, $entity, Reader $reader, $local_id)
    {
        $is_proxy = $entity instanceof OrmProxyInterface;

        foreach ($indices as $index) {
            $index_value = $reader->getIndexValue($index);

            if ($is_proxy) {
                /** @var OrmProxyInterface $entity */
                $new_id         = $local_id != $entity->getOriginalId();
                $original_value = $entity->getIndexOriginalValue($index->getName());

                if ($original_value == $index_value) {
                    // Don't skip if ID has changed, but no need to delete any keys
                    if (!$new_id) {
                        // Persisted value is indifferent, nothing to do
                        continue;
                    }
                } else {
                    // Former index is redundant, remove it
                    $this->getDriver()->clearSingleValueIndex(
                        $this->getKeyScheme()->getIndexKey($index, $original_value)
                    );
                }
            }

            $key   = $this->getKeyScheme()->getIndexKey($index, $index_value);
            $value = $local_id;
            $this->getDriver()->setSingleValueIndex($key, $value);
        }
    }
}
