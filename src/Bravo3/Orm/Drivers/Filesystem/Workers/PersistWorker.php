<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

use Bravo3\Orm\Drivers\Common\WorkerInterface;

class PersistWorker implements WorkerInterface
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return void
     */
    public function execute(array $parameters)
    {
        $filename = $parameters['filename'];
        $payload  = $parameters['payload'];

        if (file_exists($filename)) {
            // Write file, maintaining permissions
            file_put_contents($filename, $payload);
        } else {
            // Write file and set the requested umask
            file_put_contents($filename, $payload);
            chmod($filename, $parameters['umask']);
        }
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['filename', 'payload', 'umask'];
    }
}
