<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\Index;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\Io\Reader;

class IndexManager extends AbstractManagerUtility
{
    /**
     * Persist entity indices
     *
     * @param object $entity   Local entity object
     * @param Entity $metadata Optionally provide entity metadata to prevent recalculation
     * @param Reader $reader   Optionally provide the entity reader
     * @param string $local_id Optionally provide the local entity ID to prevent recalculation
     * @return $this
     */
    public function persistIndices($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        /** @var $metadata Entity */
        list($metadata, $reader, $local_id) = $this->buildPrerequisites($entity, $metadata, $reader, $local_id);
        $this->traversePersistIndices($metadata->getIndices(), $entity, $reader, $local_id);
        return $this;
    }

    /**
     * Delete all indices associated with an entity
     *
     * @param object $entity   Local entity object
     * @param Entity $metadata Optionally provide entity metadata to prevent recalculation
     * @param Reader $reader   Optionally provide the entity reader
     * @param string $local_id Optionally provide the local entity ID to prevent recalculation
     * @return $this
     */
    public function deleteIndices($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        /** @var $metadata Entity */
        list($metadata, $reader, $local_id) = $this->buildPrerequisites($entity, $metadata, $reader, $local_id);
        $this->traverseDeleteIndices($metadata->getIndices(), $entity, $reader, $local_id);
        return $this;
    }

    /**
     * Traverse an array of indices and persist them
     *
     * @param Index[] $indices
     * @param object  $entity
     * @param Reader  $reader
     * @param string  $local_id
     */
    private function traversePersistIndices(array $indices, $entity, Reader $reader, $local_id)
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

    /**
     * Traverse an array of indices and persist them
     *
     * @param Index[] $indices
     * @param object  $entity
     * @param Reader  $reader
     * @param string  $local_id
     */
    private function traverseDeleteIndices(array $indices, $entity, Reader $reader, $local_id)
    {
        $is_proxy = $entity instanceof OrmProxyInterface;

        foreach ($indices as $index) {
            if ($is_proxy) {
                /** @var OrmProxyInterface $entity */
                $index_value = $entity->getIndexOriginalValue($index->getName());
            } else {
                $index_value = $reader->getIndexValue($index);
            }

            $this->getDriver()->clearSingleValueIndex(
                $this->getKeyScheme()->getIndexKey($index, $index_value)
            );
        }

    }
}
