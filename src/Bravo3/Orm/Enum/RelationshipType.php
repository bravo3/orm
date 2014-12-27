<?php
namespace Bravo3\Orm\Enum;

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
}
