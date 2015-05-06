<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Drivers\Common\Ref;
use Bravo3\Orm\Enum\RelationshipType;
use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Exceptions\InvalidEntityException;
use Bravo3\Orm\Exceptions\UnexpectedValueException;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\Relationship;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\Io\Reader;

class RelationshipManager extends AbstractManagerUtility
{
    /**
     * Persist entity relationships
     *
     * @param object $entity   Local entity object
     * @param Entity $metadata Optionally provide entity metadata to prevent recalculation
     * @param Reader $reader   Optionally provide the entity reader
     * @param string $local_id Optionally provide the local entity ID to prevent recalculation
     * @return $this
     */
    public function persistRelationships($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        /** @var $metadata Entity */
        list($metadata, $reader, $local_id) = $this->buildPrerequisites($entity, $metadata, $reader, $local_id);
        $this->persistRelationshipsTraversal($metadata, $entity, $reader, $local_id);
        $this->updateRefs($metadata->getTableName(), $local_id, $reader);
        return $this;
    }

    /**
     * Delete relationship & sort indices
     *
     * @param object $entity   Local entity object
     * @param Entity $metadata Optionally provide entity metadata to prevent recalculation
     * @param Reader $reader   Optionally provide the entity reader
     * @param string $local_id Optionally provide the local entity ID to prevent recalculation
     * @return $this
     */
    public function deleteRelationships($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
    {
        /** @var $metadata Entity */
        list($metadata, $reader, $local_id) = $this->buildPrerequisites($entity, $metadata, $reader, $local_id);
        $this->deleteRelationshipsTraversal($metadata, $entity, $reader, $local_id);
        $this->deleteRefs($metadata->getTableName(), $local_id);
        return $this;
    }

    /**
     * Traverse the given list of relationships and delete them
     *
     * @param Entity $metadata
     * @param object $entity
     * @param Reader $reader
     * @param string $local_id
     */
    private function deleteRelationshipsTraversal(Entity $metadata, $entity, Reader $reader, $local_id)
    {
        $relationships = $metadata->getRelationships();
        $is_proxy      = $entity instanceof OrmProxyInterface;

        // If we're a proxy and modified the ID, get the un-modified ID else the result could be unexpected
        if ($is_proxy) {
            /** @var OrmProxyInterface $entity */
            $local_id = $entity->getOriginalId();
        }

        /** @var Relationship $relationship */
        foreach ($relationships as $relationship) {
            $inverse_relationship = $relationship->getInversedBy() ? $this->invertRelationship($relationship) : null;
            $forward_key          = $this->getKeyScheme()->getRelationshipKey($relationship, $local_id);

            if (!$inverse_relationship) {
                $value = $reader->getPropertyValue($relationship->getName());
                $this->deleteRelationshipRefs($relationship, $value, $local_id);
            }

            // Delete relationship keys
            if (RelationshipType::isMultiIndex($relationship->getRelationshipType())) {
                // Delete to-many forward
                $this->getDriver()->clearMultiValueIndex($forward_key);

                // Get all foreign ID's to remove local
                if ($relationship->getInversedBy()) {
                    $foreign_ids = $this->getDriver()->getMultiValueIndex($forward_key);
                    foreach ($foreign_ids as $foreign_id) {
                        $this->deleteInvertedRelationship($inverse_relationship, $foreign_id, $local_id);
                    }
                }
            } else {
                // Delete to-one forward
                $this->getDriver()->clearSingleValueIndex($forward_key);

                // Get the foreign ID to remove local
                if ($relationship->getInversedBy()) {
                    $foreign_id = $this->getDriver()->getSingleValueIndex($forward_key);
                    $this->deleteInvertedRelationship($inverse_relationship, $foreign_id, $local_id);
                }
            }

            // Delete forward sort keys (inverse keys deleted in #deleteInvertedRelationship())
            foreach ($relationship->getSortableBy() as $sortable) {
                $forward_sort_key = $this->getKeyScheme()->getSortIndexKey(
                    $relationship,
                    $sortable->getColumn(),
                    $local_id
                );
                $this->getDriver()->clearSortedIndex($forward_sort_key);
            }
        }
    }

    /**
     * Remove the local ID from an inverse relationships and sort indices
     *
     * @param Relationship $inverse_relationship
     * @param string       $foreign_id
     * @param string       $local_id
     */
    private function deleteInvertedRelationship(Relationship $inverse_relationship, $foreign_id, $local_id)
    {
        $inverse_key = $this->getKeyScheme()->getRelationshipKey($inverse_relationship, $foreign_id);
        if (RelationshipType::isMultiIndex($inverse_relationship->getRelationshipType())) {
            // Delete to-many inverse
            $this->getDriver()->removeMultiValueIndex($inverse_key, $local_id);
        } else {
            // Delete to-one inverse
            $this->getDriver()->clearSingleValueIndex($inverse_key);
        }

        foreach ($inverse_relationship->getSortableBy() as $sortable) {
            $sort_key = $this->getKeyScheme()->getSortIndexKey(
                $inverse_relationship,
                $sortable->getColumn(),
                $foreign_id
            );
            $this->getDriver()->removeSortedIndex($sort_key, $local_id);
        }
    }

    /**
     * Traverse an array of relationships and persist them
     *
     * @param Entity $metadata
     * @param object $entity
     * @param Reader $reader
     * @param string $local_id
     */
    private function persistRelationshipsTraversal(Entity $metadata, $entity, Reader $reader, $local_id)
    {
        $relationships = $metadata->getRelationships();
        $is_proxy      = $entity instanceof OrmProxyInterface;

        foreach ($relationships as $relationship) {
            $key   = $this->getKeyScheme()->getRelationshipKey($relationship, $local_id);
            $value = $reader->getPropertyValue($relationship->getName());

            // Skip relationship rules:
            // If the entity is not a proxy (i.e. a new entity) we still must allow for the scenario in which a new
            // entity is created over the top of an existing entity (same ID), as such, we still need to check every
            // relationship attached to the entity
            if (!$this->entity_manager->getMaintenanceMode() && $is_proxy) {
                /** @var OrmProxyInterface $entity */
                // Check if we can skip the update
                if (!$entity->isRelativeModified($relationship->getName())) {
                    // Looks like we can skip, but check inverted sort indices first -
                    if ($relationship->getInversedBy()) {
                        $inverse_relationship = $this->invertRelationship($relationship);
                        if ($inverse_relationship->getSortableBy()) {
                            // The inverse relationship has sortable columns, we need to check for local property
                            // changes that might have impacted this -
                            list(, , $maintain) = $this->getRelationshipDeltas($key, $relationship, $value);

                            $this->updateMaintainedRelationshipSortIndices(
                                $inverse_relationship,
                                $maintain,
                                $reader,
                                $local_id
                            );
                        }
                    }

                    // Nothing else to update on this relationship
                    continue;
                }
            }

            // This condition allows NEW (not a proxy) entities that have NOT set a relationship to inherit existing
            // relationships which could be useful if the relationship was set by a foreign entity
            // See: docs/RaceConditions.md
            if ($is_proxy || $value || $this->entity_manager->getMaintenanceMode()) {
                $this->persistForwardRelationship($relationship, $key, $value);
                if (count($relationship->getSortableBy())) {
                    $this->persistForwardSortIndices($relationship, $local_id, $value);
                }

                // Modify the inversed relationships
                if ($relationship->getInversedBy()) {
                    $this->persistInversedRelationship($relationship, $key, $value, $local_id, $reader);
                } else {
                    $this->persistRefs($relationship, $key, $value, $local_id);
                }
            }
        }
    }

    /**
     * Persist the forward side of a relationship
     *
     * @param Relationship    $relationship Forward relationship
     * @param string          $key          Relationship key
     * @param object|object[] $value        Relationship value
     */
    private function persistForwardRelationship(Relationship $relationship, $key, $value)
    {
        // Set the local relationship
        $this->getDriver()->debugLog('@Setting forward relationship: '.$key);
        if (RelationshipType::isMultiIndex($relationship->getRelationshipType())) {
            $this->setMultiValueRelationship($key, $value);
        } else {
            $this->setSingleValueRelationship($key, $value);
        }
    }

    /**
     * Persist forward sorted indices
     *
     * @param Relationship    $relationship
     * @param string          $local_id
     * @param object|object[] $value
     */
    private function persistForwardSortIndices(Relationship $relationship, $local_id, $value)
    {
        if ($value === null) {
            $value = [];
        } elseif (!is_array($value)) {
            $value = [$value];
        }

        $this->getDriver()->debugLog('@Setting forward sort indices for "'.$local_id.'"');
        foreach ($relationship->getSortableBy() as $sortable) {
            $key = $this->getKeyScheme()->getSortIndexKey($relationship, $sortable->getColumn(), $local_id);
            $this->getDriver()->clearSortedIndex($key);

            foreach ($value as $entity) {
                $metadata   = $this->getMapper()->getEntityMetadata($entity);
                $reader     = new Reader($metadata, $entity);
                $foreign_id = $reader->getId();

                // Test conditions
                foreach ($sortable->getConditions() as $condition) {
                    if ($condition->getColumn()) {
                        if (!$condition->test($reader->getPropertyValue($condition->getColumn()))) {
                            continue 2;
                        }
                    } elseif ($condition->getMethod()) {
                        if (!$condition->test($reader->getMethodValue($condition->getMethod()))) {
                            continue 2;
                        }
                    }
                }

                $score = $reader->getPropertyValue($sortable->getColumn());
                $this->getDriver()->addSortedIndex($key, $score, $foreign_id);
            }
        }
    }

    /**
     * Persist the inverse side of a relationship
     *
     * @param Relationship    $relationship Forward relationship
     * @param string          $key          Forward relationship key
     * @param object|object[] $value        Forward relationship value
     * @param string          $local_id     ID of local entity
     * @param Reader          $reader       Local entity reader, used for sorted indices
     */
    private function persistInversedRelationship(Relationship $relationship, $key, $value, $local_id, Reader $reader)
    {
        $inverse_relationship = $this->invertRelationship($relationship);
        list($to_remove, $to_add, $maintain) = $this->getRelationshipDeltas($key, $relationship, $value);

        $this->getDriver()->debugLog('@Setting inverse relationship: '.$key);

        // When we're in maintenance mode, force the index to be built
        if ($this->entity_manager->getMaintenanceMode()) {
            $to_add   = array_merge($to_add, $maintain);
            $maintain = [];
        }

        // Remove local from all foreigners no longer in the relationship
        foreach ($to_remove as $foreign_id) {
            $inverse_key = $this->getKeyScheme()->getRelationshipKey($inverse_relationship, $foreign_id);

            if (RelationshipType::isMultiIndex($inverse_relationship->getRelationshipType())) {
                $this->getDriver()->removeMultiValueIndex($inverse_key, $local_id);
            } else {
                $this->getDriver()->clearSingleValueIndex($inverse_key);
            }

            // If the inverted relationship has sorting, remove the local from the sorted index
            foreach ($inverse_relationship->getSortableBy() as $sortable) {
                $this->getDriver()->removeSortedIndex(
                    $this->getKeyScheme()->getSortIndexKey($inverse_relationship, $sortable->getColumn(), $foreign_id),
                    $local_id
                );
            }
        }

        // Add local to all foreigners now added to the relationship
        foreach ($to_add as $foreign_id) {
            $inverse_key = $this->getKeyScheme()->getRelationshipKey($inverse_relationship, $foreign_id);

            if (RelationshipType::isMultiIndex($inverse_relationship->getRelationshipType())) {
                $this->getDriver()->addMultiValueIndex($inverse_key, $local_id);
            } else {
                if (!$this->entity_manager->getMaintenanceMode()) {
                    $this->breakFormerRelationship($inverse_relationship, $foreign_id);
                }
                $this->getDriver()->setSingleValueIndex($inverse_key, $local_id);
            }

            foreach ($inverse_relationship->getSortableBy() as $sortable) {
                // Test conditions
                foreach ($sortable->getConditions() as $condition) {
                    if ($condition->getColumn()) {
                        if (!$condition->test($reader->getPropertyValue($condition->getColumn()))) {
                            continue 2;
                        }
                    } elseif ($condition->getMethod()) {
                        if (!$condition->test($reader->getMethodValue($condition->getMethod()))) {
                            continue 2;
                        }
                    }
                }

                // Add inverse index
                $this->getDriver()->addSortedIndex(
                    $this->getKeyScheme()->getSortIndexKey($inverse_relationship, $sortable->getColumn(), $foreign_id),
                    $reader->getPropertyValue($sortable->getColumn()),
                    $local_id
                );
            }
        }

        $this->updateMaintainedRelationshipSortIndices($inverse_relationship, $maintain, $reader, $local_id);
    }

    /**
     * Persist refs in place of inverted indices
     *
     * @param Relationship    $relationship Forward relationship
     * @param string          $key          Forward relationship key
     * @param object|object[] $value        Forward relationship value
     * @param string          $local_id     ID of local entity
     */
    private function persistRefs(Relationship $relationship, $key, $value, $local_id)
    {
        list($to_remove, $to_add, $maintain) = $this->getRelationshipDeltas($key, $relationship, $value);
        $ref = new Ref($relationship->getSource(), $local_id, $relationship->getName());

        // When we're in maintenance mode, force the ref table to be built
        if ($this->entity_manager->getMaintenanceMode()) {
            $to_add = array_merge($to_add, $maintain);
        }

        foreach ($to_remove as $foreign_id) {
            $ref_key = $this->getKeyScheme()->getEntityRefKey($relationship->getTargetTable(), $foreign_id);
            $this->getDriver()->removeRef($ref_key, $ref);
        }

        foreach ($to_add as $foreign_id) {
            $ref_key = $this->getKeyScheme()->getEntityRefKey($relationship->getTargetTable(), $foreign_id);
            $this->getDriver()->addRef($ref_key, $ref);
        }
    }

    /**
     * Delete all references created by this relationship
     *
     * @param Relationship    $relationship Forward relationship
     * @param object|object[] $value        Forward relationship value
     * @param string          $local_id     ID of local entity
     * @internal param string $key Forward relationship key
     */
    private function deleteRelationshipRefs(Relationship $relationship, $value, $local_id)
    {
        $ref = new Ref($relationship->getSource(), $local_id, $relationship->getName());

        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $foreign_entity) {
            if (null !== $foreign_entity) {
                $foreign_id = $this->getEntityId($foreign_entity);
                $ref_key    = $this->getKeyScheme()->getEntityRefKey($relationship->getTargetTable(), $foreign_id);
                $this->getDriver()->removeRef($ref_key, $ref);
            }
        }
    }

    /**
     * Update references to this entity (such as conditional sort indices)
     *
     * @param string $table_name Local table name
     * @param string $local_id   Local entity ID
     * @param Reader $reader     Local entity reader
     */
    private function updateRefs($table_name, $local_id, Reader $reader)
    {
        $ref_key = $this->getKeyScheme()->getEntityRefKey($table_name, $local_id);
        $refs    = $this->getDriver()->getRefs($ref_key);
        foreach ($refs as $ref) {
            $relationship = $this->getMapper()
                                 ->getEntityMetadata($ref->getSourceClass())
                                 ->getRelationshipByName($ref->getRelationshipName());

            if ($relationship->getSortableBy()) {
                $this->updateMaintainedRelationshipSortIndices(
                    $relationship,
                    [$ref->getEntityId()],
                    $reader,
                    $local_id
                );
            }
        }
    }

    /**
     * Remove all references to this entity
     *
     * @param string $table_name
     * @param string $local_id
     */
    private function deleteRefs($table_name, $local_id)
    {
        $ref_key = $this->getKeyScheme()->getEntityRefKey($table_name, $local_id);
        $refs    = $this->getDriver()->getRefs($ref_key);
        foreach ($refs as $ref) {
            $relationship = $this->getMapper()
                                 ->getEntityMetadata($ref->getSourceClass())
                                 ->getRelationshipByName($ref->getRelationshipName());

            $relationship_key = $this->getKeyScheme()->getRelationshipKey($relationship, $ref->getEntityId());

            switch ($relationship->getRelationshipType()) {
                default:
                    throw new UnexpectedValueException(
                        "Unknown relationship type: ".$relationship->getRelationshipType()->value()
                    );
                case RelationshipType::MANYTOMANY():
                case RelationshipType::ONETOMANY():
                    $this->getDriver()->removeMultiValueIndex($relationship_key, $local_id);
                    break;
                case RelationshipType::MANYTOONE():
                case RelationshipType::ONETOONE():
                    $this->getDriver()->clearSingleValueIndex($relationship_key);
                    break;
            }

            foreach ($relationship->getSortableBy() as $sortable) {
                $sort_key = $this->getKeyScheme()->getSortIndexKey(
                    $relationship,
                    $sortable->getColumn(),
                    $ref->getEntityId()
                );
                $this->getDriver()->removeSortedIndex($sort_key, $local_id);
            }
        }
        $this->getDriver()->clearRefs($ref_key);
    }

    /**
     * Re-test and update inverted sort-by columns for maintained relationship entities
     *
     * Entities in a relationship that are not maintained (added or removed) will be updated when the entire
     * relationship is added/removed
     *
     * @param Relationship $inverse_relationship Inverted relationship
     * @param string[]     $maintain             Array of foreign ID's that are the maintained part of the relationship
     * @param Reader       $reader               Local entity reader
     * @param string       $local_id             Local ID
     */
    private function updateMaintainedRelationshipSortIndices(
        Relationship $inverse_relationship,
        $maintain,
        Reader $reader,
        $local_id
    ) {
        if (!$inverse_relationship->getSortableBy()) {
            return;
        }

        // Update unchanged relationships, as the sort-by column or conditions may have changed
        foreach ($maintain as $foreign_id) {
            foreach ($inverse_relationship->getSortableBy() as $sortable) {
                $index_key = $this->getKeyScheme()->getSortIndexKey(
                    $inverse_relationship,
                    $sortable->getColumn(),
                    $foreign_id
                );

                // Test conditions
                foreach ($sortable->getConditions() as $condition) {
                    if ($condition->getColumn()) {
                        if (!$condition->test($reader->getPropertyValue($condition->getColumn()))) {
                            // Condition failed, remove index
                            $this->getDriver()->removeSortedIndex(
                                $index_key,
                                $local_id
                            );
                            continue 2;
                        }
                    } elseif ($condition->getMethod()) {
                        if (!$condition->test($reader->getMethodValue($condition->getMethod()))) {
                            // Condition failed, remove index
                            $this->getDriver()->removeSortedIndex(
                                $index_key,
                                $local_id
                            );
                            continue 2;
                        }
                    }
                }

                // Add/update inverse index
                $this->getDriver()->addSortedIndex(
                    $index_key,
                    $reader->getPropertyValue($sortable->getColumn()),
                    $local_id
                );
            }
        }
    }

    /**
     * When adding an entity on a one-to-many relationship, the foreign entity might have had a pre-existing entity
     * assigned in the inverted 'to-one' index. If it had a value, we now need to break that existing relationship as
     * we have inadvertently removed it by assigning it to a new local entity.
     *
     * This operation should only ever be applied to 'to-one' relationships, which should be the inverse of a 'to-many'
     * relationship. Other use is illogical.
     *
     * This call will remove only the inverse of the relationship provided (which would be the former forward of the
     * relationship that triggered this), breaking the forward relationship is assumed when overwriting the new
     * relationship.
     *
     * @param Relationship $relationship
     * @param string       $source_id
     */
    private function breakFormerRelationship(Relationship $relationship, $source_id)
    {
        $key = $this->getKeyScheme()->getRelationshipKey($relationship, $source_id);
        $this->getDriver()->debugLog('Checking for breakable former relationship: '.$key);
        $old_value = $this->getDriver()->getSingleValueIndex($key);

        if (!$old_value) {
            // No former relationship to break
            return;
        }

        $inverse_relationship = $this->invertRelationship($relationship);

        // Relationship keys
        $inverse_key = $this->getKeyScheme()->getRelationshipKey($inverse_relationship, $old_value);
        $this->getDriver()->debugLog('@Breaking former relationship: '.$inverse_key);
        if ($inverse_relationship->getRelationshipType() == RelationshipType::MANYTOONE() ||
            $inverse_relationship->getRelationshipType() == RelationshipType::ONETOONE()
        ) {
            $this->getDriver()->clearSingleValueIndex($inverse_key);
        } else {
            $this->getDriver()->removeMultiValueIndex($inverse_key, $source_id);
        }

        // Sorted index keys
        foreach ($inverse_relationship->getSortableBy() as $sortable) {
            $sort_key = $this->getKeyScheme()->getSortIndexKey(
                $inverse_relationship,
                $sortable->getColumn(),
                $old_value
            );
            $this->getDriver()->removeSortedIndex($sort_key, $source_id);
        }
    }

    /**
     * Returns the inverse equivalent of a given relationship
     *
     * @param Relationship $relationship
     * @return Relationship
     */
    public function invertRelationship(Relationship $relationship)
    {
        if (!$relationship->getInversedBy()) {
            throw new InvalidArgumentException('Relationship "'.$relationship->getName().'" is not inversed');
        }

        $metadata = $this->getMapper()->getEntityMetadata($relationship->getTarget());
        $inverse  = $metadata->getRelationshipByName($relationship->getInversedBy());

        if (!$inverse) {
            throw new InvalidEntityException(
                'Relationship "'.$relationship->getName().'" inverse side "'.$relationship->getInversedBy().
                '" cannot be not found'
            );
        }

        return $inverse;
    }

    /**
     * Get an array containing an array of foreign entities to remove the local entity from, and an array of foreign
     * entities to add the local entity to and an array of entities which remain the same in the relationship
     *
     * @param string          $key          Local relationship key
     * @param Relationship    $relationship Relationship in question
     * @param object|object[] $new_value    New local value containing foreign entities
     * @return array
     */
    private function getRelationshipDeltas($key, Relationship $relationship, $new_value)
    {
        $this->getDriver()->debugLog('Getting inverse relationship deltas: '.$key);

        if (RelationshipType::isMultiIndex($relationship->getRelationshipType())) {
            return $this->getRelationshipDeltasMulti($key, $relationship, $new_value);
        } else {
            return $this->getRelationshipDeltasSingle($key, $relationship, $new_value);
        }
    }

    /**
     * Get the deltas for a to-many relationship
     *
     * @param string          $key          Local relationship key
     * @param Relationship    $relationship Relationship in question
     * @param object|object[] $new_value    New local value containing foreign entities
     * @return array
     */
    private function getRelationshipDeltasMulti($key, Relationship $relationship, $new_value)
    {
        $old_ids = $this->getDriver()->getMultiValueIndex($key);

        $new_ids = [];
        if ($new_value) {
            foreach ($new_value as $item) {
                $new_ids[] = $this->getEntityId($item);
            }
        }

        return [array_diff($old_ids, $new_ids), array_diff($new_ids, $old_ids), array_intersect($old_ids, $new_ids)];
    }

    /**
     * Get the deltas for a to-one relationship
     *
     * @param string          $key          Local relationship key
     * @param Relationship    $relationship Relationship in question
     * @param object|object[] $new_value    New local value containing foreign entities
     * @return array
     */
    private function getRelationshipDeltasSingle($key, Relationship $relationship, $new_value)
    {
        $old_id = $this->getDriver()->getSingleValueIndex($key);
        $new_id = $new_value ? $this->getEntityId($new_value) : null;

        $to_remove = [];
        $to_add    = [];
        $maintain  = [];

        if ($new_id != $old_id) {
            if ($old_id) {
                $to_remove[] = $old_id;
            }
            if ($new_id) {
                $to_add[] = $new_id;
            }
        } else {
            $maintain[] = $old_id;
        }

        return [$to_remove, $to_add, $maintain];
    }

    /**
     * Set a single-key relationship index
     *
     * @param string $key
     * @param object $foreign_entity
     */
    private function setSingleValueRelationship($key, $foreign_entity)
    {
        if ($foreign_entity) {
            $rel_metadata = $this->getMapper()->getEntityMetadata($foreign_entity);
            $rel_reader   = new Reader($rel_metadata, $foreign_entity);
            $value        = $rel_reader->getId();
        } else {
            $value = null;
        }

        $this->getDriver()->setSingleValueIndex($key, $value);
    }

    /**
     * Set a forward multi-value relationship index
     *
     * When setting forward indices the set is always cleared and re-added. This will fully synchronise the list and
     * there is no need to calculate a delta, however it may be slow on large sets.
     *
     * Inverse relationships should always be updated via a delta operation.
     *
     * @param string        $key
     * @param object[]|null $foreign_entities
     */
    private function setMultiValueRelationship($key, $foreign_entities)
    {
        $this->getDriver()->clearMultiValueIndex($key);

        if ($foreign_entities) {
            $values = [];
            foreach ($foreign_entities as $entity) {
                $rel_metadata = $this->getMapper()->getEntityMetadata($entity);
                $rel_reader   = new Reader($rel_metadata, $entity);
                $values[]     = $rel_reader->getId();
            }
            $this->getDriver()->addMultiValueIndex($key, $values);
        }
    }
}
