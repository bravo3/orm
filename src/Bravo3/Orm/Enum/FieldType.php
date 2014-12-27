<?php
namespace Bravo3\Orm\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static FieldType INT()
 * @method static FieldType DECIMAL()
 * @method static FieldType STRING()
 * @method static FieldType BOOL()
 * @method static FieldType DATETIME()
 */
final class FieldType extends AbstractEnumeration
{
    const INT      = 'int';
    const DECIMAL  = 'decimal';
    const STRING   = 'string';
    const BOOL     = 'bool';
    const DATETIME = 'datetime';
}
