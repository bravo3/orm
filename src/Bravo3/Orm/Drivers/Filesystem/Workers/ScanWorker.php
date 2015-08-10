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
        $query     = $parameters['query'];
        $key_base  = $parameters['base'];
        $exp       = basename($query);
        $file_base = dirname($query);

        $out = [];
        foreach (scandir($file_base) as $file) {
            if (is_file($file_base.DIRECTORY_SEPARATOR.$file) && $this->expMatch($file, $exp)) {
                $out[] = $key_base.DIRECTORY_SEPARATOR.$file;
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
        return ['query', 'base'];
    }
}
