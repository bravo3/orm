<?php
namespace Bravo3\Orm\Drivers\Filesystem\Workers;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Drivers\Common\WorkerInterface;
use Bravo3\Orm\Drivers\Filesystem\FilesystemDriver;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\Exceptions\UnexpectedValueException;

/**
 * Read and parse an entity object, returning SerialisedData
 *
 * This class will honour the TTL, throwing a NotFoundException if the TTL has expired.
 */
class RetrieveWorker extends AbstractWorker
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return mixed
     */
    public function execute(array $parameters)
    {
        $key     = $parameters['key'];
        $payload = $this->readData($parameters['filename']);

        if ($payload !== null) {
            $data = explode(FilesystemDriver::DATA_DELIMITER, $payload, 3);

            if (count($data) != 3) {
                throw new UnexpectedValueException("Object data is corrupted: ".$key);
            }

            $ttl = (int)$data[1];
            if ($ttl > 0 && $ttl < time()) {
                unlink($parameters['filename']);
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
