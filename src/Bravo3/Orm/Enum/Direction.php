<?php
namespace Bravo3\Orm\Enum;

use Eloquent\Enumeration\AbstractEnumeration;

/**
 * @method static Direction ASC()
 * @method static Direction DESC()
 */
final class Direction extends AbstractEnumeration
{
    const ASC  = 'ASC';
    const DESC = 'DESC';
}
