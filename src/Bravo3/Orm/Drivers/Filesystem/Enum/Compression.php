<?php
namespace Bravo3\Orm\Drivers\Filesystem\Enum;

use Bravo3\Orm\Enum\OrmEnum;

/**
 * @method static Compression NONE()
 * @method static Compression GZIP()
 * @method static Compression BZIP2()
 */
final class Compression extends OrmEnum
{
    const NONE  = 0;
    const GZIP  = \Phar::GZ;
    const BZIP2 = \Phar::BZ2;
}
