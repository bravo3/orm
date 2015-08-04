<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

use Bravo3\Orm\Exceptions\NotSupportedException;

/**
 * Performs a key scan
 */
class ScanWorker extends AbstractWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return string[]
     */
    public function execute(array $parameters)
    {
        $query = $parameters['query'];
        $exp   = basename($query);
        $base  = dirname($query);

        $files = scandir($base);

        $out = [];
        foreach ($files as $file) {
            if (is_file($base.DIRECTORY_SEPARATOR.$file) && $this->expMatch($file, $exp)) {
                $out[] = $file;
            }
        }

        return $out;
    }

    /**
     * Check that $file matches the expression $exp
     *
     * @param string $test
     * @param string $exp
     * @return bool
     */
    protected function expMatch($test, $exp)
    {
        if (!function_exists('fnmatch')) {
            throw new NotSupportedException(
                "'fnmatch' is not supported on this system and therefore cannot perform a key scan"
            );
        }

        return fnmatch($exp, $test);
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['query'];
    }
}
