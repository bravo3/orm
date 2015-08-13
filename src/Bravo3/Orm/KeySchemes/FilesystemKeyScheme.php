<?php
namespace Bravo3\Orm\KeySchemes;

/**
 * Identical to the StandardKeyScheme except the default delimiter is a forward-slash
 */
class FilesystemKeyScheme extends StandardKeyScheme
{
    /**
     * Should not ever be referenced (constructor changes logic) but if this is referenced by other classes this should
     * be a filesystem style directory separator, not a NoSQL-style object namespace separator.
     */
    const DEFAULT_DELIMITER = '/';

    public function __construct($delimiter = null)
    {
        $this->delimiter = $delimiter ?: DIRECTORY_SEPARATOR;
    }
}
