<?php
namespace Bravo3\Orm\KeySchemes;

/**
 * Identical to the StandardKeyScheme except the default delimiter is a forward-slash
 */
class FilesystemKeyScheme extends StandardKeyScheme
{
    const DEFAULT_DELIMITER = '/';
}
