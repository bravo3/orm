<?php
namespace Bravo3\Orm\Enum;

use Bravo3\Orm\Exceptions\InvalidEntityException;
use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static RelationshipType ONETOONE()
 * @method static RelationshipType ONETOMANY()
 * @method static RelationshipType MANYTOONE()
 * @method static RelationshipType MANYTOMANY()
 */
final class RelationshipType extends AbstractEnumeration
{
    const ONETOONE   = 'oto';
    const ONETOMANY  = 'otm';
    const MANYTOONE  = 'mto';
    const MANYTOMANY = 'mtm';

    /**
     * Check if the given relationship type contains multiple values
     *
     * @param RelationshipType $relationship_type
     * @return bool
     */
    public static function isMultiIndex(RelationshipType $relationship_type)
    {
        return $relationship_type == RelationshipType::ONETOMANY() ||
               $relationship_type == RelationshipType::MANYTOMANY();
    }

    /**
     * Get the inverse relationship type of the given relationship type
     *
     * @param RelationshipType $relationship_type
     * @return RelationshipType
     */
    public static function getInverseRelationship(RelationshipType $relationship_type)
    {
        switch ($relationship_type) {
            default:
                throw new InvalidEntityException("Unknown relationship type: ".$relationship_type->key());

            case RelationshipType::ONETOONE():
                return RelationshipType::ONETOONE();

            case RelationshipType::ONETOMANY():
                return RelationshipType::MANYTOONE();

            case RelationshipType::MANYTOONE():
                return RelationshipType::ONETOMANY();

            case RelationshipType::MANYTOMANY():
                return RelationshipType::MANYTOMANY();
        }
    }
}
