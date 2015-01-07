<?php
namespace Bravo3\Orm\Tests\Resources;

use Bravo3\Orm\Enum\OrmEnum;

/**
 * @method static Enum ALPHA()
 * @method static Enum BRAVO()
 */
final class Enum extends OrmEnum
{
    const ALPHA = 'alpha';
    const BRAVO = 'bravo';
}
