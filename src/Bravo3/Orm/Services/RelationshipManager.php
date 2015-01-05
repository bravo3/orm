<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Enum\RelationshipType;
use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Exceptions\InvalidEntityException;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Mappers\Metadata\Relationship;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\Io\Reader;
use Bravo3\Orm\Traits\EntityManagerAwareTrait;

class RelationshipManager
{
    use EntityManagerAwareTrait;

    /**
     * Persist entity relationships
     *
     * @param object $entity   Local entity object
     * @param Entity $metadata Optionally provide entity metadata to prevent recalculation
     * @param Reader $reader   Optionally provide the entity reader
     * @param string $local_id Optionally provide the local entity ID to prevent recalculation
     */
    public function persistRelationships($entity, Entity $metadata = null, Reader $reader = null, $local_id = null)
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

        $this->traverseRelationships($metadata->getRelationships(), $entity, $reader, $local_id);
    }

    /**
     * Traverse an array of relationships and persist them
     *
     * @param Relationship[] $relationships
     * @param object         $entity
     * @param Reader         $reader
     * @param string         $local_id
     */
    private function traverseRelationships(array $relationships, $entity, Reader $reader, $local_id)
    {
        $is_proxy = $entity instanceof OrmProxyInterface;

        foreach ($relationships as $relationship) {
            // If the entity is not a proxy (i.e. a new entity) we still must allow for the scenario in which a new
            // entity is created over the top of an existing entity (same ID), as such, we still need to check every
            // relationship attached to the entity
            if ($is_proxy) {
                /** @var OrmProxyInterface $entity */
                if (!$entity->isRelativeModified($relationship->getName())) {
                    // Only if we have a proxy object and the relationship has not been modified, can we skip the
                    // relationship update
                    continue;
                }
            }

            $key   = $this->getKeyScheme()->getRelationshipKey($relationship, $local_id);
            $value = $reader->getPropertyValue($relationship->getName());

            // This test allows NEW (not a proxy) entities that have NOT set a relationship to inherit existing
            // relationships which could be useful if the relationship was set by a foreign entity
            // See: docs/RaceConditions.md
            if ($is_proxy || $value) {
                $this->persistForwardRelationship($relationship, $key, $value);
                if (count($relationship->getSortableBy())) {
                    $this->persistForwardSortIndices($relationship, $local_id, $value);
                }

                // Modify the inversed relationships
                if ($relationship->getInversedBy()) {
                    $this->persistInversedRelationship($relationship, $key, $value, $local_id, $reader);
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
        foreach ($relationship->getSortableBy() as $sort_property) {
            $key = $this->getKeyScheme()->getSortIndexKey($relationship, $sort_property, $local_id);
            $this->getDriver()->clearSortedIndex($key);

            foreach ($value as $entity) {
                $metadata   = $this->getMapper()->getEntityMetadata($entity);
                $reader     = new Reader($metadata, $entity);
                $foreign_id = $reader->getId();
                $score      = $reader->getPropertyValue($sort_property);
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
        list($to_remove, $to_add) = $this->getRelationshipDeltas($key, $relationship, $value);

        $this->getDriver()->debugLog('@Setting inverse relationship: '.$key);

        // Remove local from all foreigners no longer in the relationship
        foreach ($to_remove as $foreign_id) {
            $inverse_key = $this->getKeyScheme()->getRelationshipKey($inverse_relationship, $foreign_id);

            if (RelationshipType::isMultiIndex($inverse_relationship->getRelationshipType())) {
                $this->getDriver()->removeMultiValueIndex($inverse_key, $local_id);
            } else {
                $this->getDriver()->clearSingleValueIndex($inverse_key);
            }

            // If the inverted relationship has sorting, remove the local from the sorted index
            foreach ($inverse_relationship->getSortableBy() as $sort_property) {
                $this->getDriver()->removeSortedIndex(
                    $this->getKeyScheme()->getSortIndexKey($inverse_relationship, $sort_property, $foreign_id),
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
                $this->breakFormerRelationship($inverse_relationship, $foreign_id);
                $this->getDriver()->setSingleValueIndex($inverse_key, $local_id);
            }

            foreach ($inverse_relationship->getSortableBy() as $sort_property) {
                $this->getDriver()->addSortedIndex(
                    $this->getKeyScheme()->getSortIndexKey($inverse_relationship, $sort_property, $foreign_id),
                    $reader->getPropertyValue($sort_property),
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
        $this->getDriver()->removeMultiValueIndex($inverse_key, $source_id);

        // Sorted index keys
        foreach ($inverse_relationship->getSortableBy() as $sort_field) {
            $sort_key = $this->getKeyScheme()->getSortIndexKey($inverse_relationship, $sort_field, $old_value);
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
     * Get the full ID of an entity
     *
     * @param object $entity
     * @return string
     */
    private function getEntityId($entity)
    {
        $metadata = $this->getMapper()->getEntityMetadata(Reader::getEntityClassName($entity));
        $reader   = new Reader($metadata, $entity);
        return $reader->getId();
    }

    /**
     * Get an array containing an array of foreign entities to remove the local entity from, and an array of foreign
     * entities to add the local entity to
     *
     * @param string          $key          Local relationship key
     * @param Relationship    $relationship Relationship in question
     * @param object|object[] $new_value    New local value containing foreign entities
     * @return array
     */
    private function getRelationshipDeltas($key, Relationship $relationship, $new_value)
    {
        $this->getDriver()->debugLog('Getting inverse relationship deltas: '.$key);

        // Work out what needs to be added, and what needs to be removed
        if (RelationshipType::isMultiIndex($relationship->getRelationshipType())) {
            $old_ids = $this->getDriver()->getMultiValueIndex($key);

            $new_ids = [];
            if ($new_value) {
                foreach ($new_value as $item) {
                    $new_ids[] = $this->getEntityId($item);
                }
            }

            $to_remove = array_diff($old_ids, $new_ids);
            $to_add    = array_diff($new_ids, $old_ids);
        } else {
            $old_id = $this->getDriver()->getSingleValueIndex($key);
            $new_id = $new_value ? $this->getEntityId($new_value) : null;

            $to_remove = [];
            $to_add    = [];

            if ($new_id != $old_id) {
                if ($old_id) {
                    $to_remove[] = $old_id;
                }
                if ($new_id) {
                    $to_add[] = $new_id;
                }
            }
        }

        return [$to_remove, $to_add];
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
     * Set a multi-value relationship index
     *
     * The strategy used here is to reset (clear and re-add) the entire index, the advantages of this are:
     * - Simple and straight-forward
     * - Forces the index to be synchronised every time the relationship is persisted
     *
     * Disadvantages of this are:
     * - Possible performance loss for large sets
     *
     * Other strategies would be to work out removed elements and delete them from the index. This could resolve the
     * performance hit for large lists, but also allows for possible desynchronisation of the index.
     *
     * Consider: perhaps ideally, a combination of both strategies might be useful.
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
