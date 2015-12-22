<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Exceptions\AlreadyExistsException;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\UniqueIndex;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\Io\Reader;

/**
 * Persists and deletes unique keys.
 */
class UniqueKeyManager extends AbstractManagerUtility
{
    /**
     * Persist entity unique keys.
     *
     * @param object $entity   Local entity object
     * @param Entity $metadata Optionally provide entity metadata to prevent recalculation
     * @param Reader $reader   Optionally provide the entity reader
     * @param string $local_id Optionally provide the local entity ID to prevent recalculation
     * @return $this
     */
    public function persistUniqueKeys($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        /** @var $metadata Entity */
        list($metadata, $reader, $local_id) = $this->buildPrerequisites($entity, $metadata, $reader, $local_id);
        $this->traversePersistUniqueKeys($metadata->getUniqueIndices(), $entity, $reader, $local_id);
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
    public function deleteUniqueKeys($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        /** @var $metadata Entity */
        list($metadata, $reader,) = $this->buildPrerequisites($entity, $metadata, $reader, $local_id);
        $this->traverseDeleteUniqueKeys($metadata->getUniqueIndices(), $entity, $reader);
        return $this;
    }

    /**
     * Traverse an array of indices and persist them
     *
     * @param UniqueIndex[] $indices
     * @param object        $entity
     * @param Reader        $reader
     * @param string        $local_id
     */
    private function traversePersistUniqueKeys(array $indices, $entity, Reader $reader, $local_id)
    {
        $is_proxy     = $entity instanceof OrmProxyInterface;
        $force_unique = $this->entity_manager->getConfig()->getForceUniqueKeys();

        foreach ($indices as $index) {
            $index_value = $reader->getIndexValue($index);

            if ($is_proxy) {
                /** @var OrmProxyInterface $entity */
                $new_id         = $local_id != $entity->getOriginalId();
                $original_value = $entity->getIndexOriginalValue($index->getName());

                if ($original_value == $index_value) {
                    // Don't skip if ID has changed, but no need to delete any keys
                    if (!$new_id && !$this->entity_manager->getMaintenanceMode()) {
                        // Persisted value is indifferent, nothing to do
                        continue;
                    }
                } else {
                    /*
                     * The former index is now redundant and must be removed, however if forcing unique keys is
                     * disabled then the index might be "contested" and another entity owns the index. If other entity
                     * owns the index we don't want to delete else we'll be creating (more) data corruption.
                     */
                    $former_key = $this->getKeyScheme()->getUniqueIndexKey($index, $original_value);

                    if ($force_unique) {
                        // No possibility of contestation, just kill the previous index
                        $this->getDriver()->clearSingleValueIndex($former_key);
                    } else {
                        // Unique restraints are disabled - check if the index belows to this entity before deleting
                        $former_value = $this->getDriver()->getSingleValueIndex($former_key);
                        if ($former_value && ($former_value == $entity->getOriginalId())) {
                            $this->getDriver()->clearSingleValueIndex($former_key);
                        }
                    }
                }
            }

            $key = $this->getKeyScheme()->getUniqueIndexKey($index, $index_value);

            if ($force_unique) {
                $value = $this->getDriver()->getSingleValueIndex($key);

                if ($value && ($value != $entity->getOriginalId())) {
                    throw new AlreadyExistsException(
                        sprintf("Key '%s' for index '%s' is already owned by '%s' but wanted by '%s'",
                                $index_value,
                                $index->getName(),
                                $value,
                                $local_id)
                    );
                }
            }

            $this->getDriver()->setSingleValueIndex($key, $local_id);
        }
    }

    /**
     * Traverse an array of indices and persist them
     *
     * @param UniqueIndex[] $indices
     * @param object        $entity
     * @param Reader        $reader
     */
    private function traverseDeleteUniqueKeys(array $indices, $entity, Reader $reader)
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
                $this->getKeyScheme()->getUniqueIndexKey($index, $index_value)
            );
        }

    }
}
