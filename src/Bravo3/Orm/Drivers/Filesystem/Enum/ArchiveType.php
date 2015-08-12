<?php
namespace Bravo3\Orm\Drivers\Filesystem\Enum;

use Bravo3\Orm\Enum\OrmEnum;

/**
 * @method static ArchiveType TAR()
 * @method static ArchiveType ZIP()
 */
final class ArchiveType extends OrmEnum
{
    const TAR = \Phar::TAR;
    const ZIP = \Phar::ZIP;
}
