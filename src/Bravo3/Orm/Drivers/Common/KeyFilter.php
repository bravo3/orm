<?php
namespace Bravo3\Orm\Drivers\Common;

use Bravo3\Orm\Exceptions\NotSupportedException;

/**
 * A filter for the query wildcards available in docs/Queries.md
 */
class KeyFilter
{
    /**
     * Create a key filter, checking that the platform supports the function 'fnmatch'
     */
    public function __construct()
    {
        if (!function_exists('fnmatch')) {
            throw new NotSupportedException(
                "'fnmatch' is not supported on this system and therefore cannot perform a key scan"
            );
        }
    }

    /**
     * Check that $test matches the expression $exp
     *
     * @param string $test
     * @param string $exp
     * @return bool
     */
    public function match($test, $exp)
    {
        return fnmatch($exp, $test);
    }
}
