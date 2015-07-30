<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Drivers\Common\WorkerInterface;
use Bravo3\Orm\Drivers\Filesystem\FilesystemDriver;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\Exceptions\UnexpectedValueException;

class RetrieveWorker implements WorkerInterface
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return mixed
     */
    public function execute(array $parameters)
    {
        $key = $parameters['key'];
        $fn  = $parameters['filename'];

        if (is_readable($fn)) {
            $data = explode(FilesystemDriver::DATA_DELIMITER, file_get_contents($fn), 3);
            if (count($data) != 3) {
                throw new UnexpectedValueException("Object data is corrupted: ".$key);
            }

            $ttl = (int)$data[1];
            if ($ttl > 0 && $ttl < time()) {
                unlink($fn);
                throw new NotFoundException("Object has expired: ".$key);
            }

            return new SerialisedData($data[0], $data[2]);
        } else {
            throw new NotFoundException("Key does not exist: ".$key);
        }
    }

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return ['key', 'filename'];
    }
}
