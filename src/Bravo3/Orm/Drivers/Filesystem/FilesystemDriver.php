<?php
namespace Bravo3\Orm\Drivers\Filesystem;

use Bravo3\Orm\Drivers\Common\Command;
use Bravo3\Orm\Drivers\Common\Ref;
use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Drivers\Common\UnitOfWork;
use Bravo3\Orm\Drivers\Common\WorkerPool;
use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Drivers\Filesystem\Workers\PersistWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\RetrieveWorker;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\Traits\DebugTrait;

class FilesystemDriver implements DriverInterface
{
    use DebugTrait;

    /**
     * Files stored to the filesystem will contain the serialisation key, TTL and serialised data - these values are
     * delimited by this key.
     */
    const DATA_DELIMITER = ';';

    /**
     * @var string
     */
    protected $data_dir;

    /**
     * @var int
     */
    protected $umask;

    /**
     * @var UnitOfWork
     */
    protected $unit_of_work;

    /**
     * @var WorkerPool
     */
    protected $worker_pool;

    /**
     * @param string $data_dir Base directory for database
     * @param int    $umask    Filesystem umask
     */
    public function __construct($data_dir, $umask = 0660)
    {
        $this->setDataDir($data_dir);
        $this->umask        = $umask;
        $this->unit_of_work = new UnitOfWork();
        $this->worker_pool  = new WorkerPool(
            [
                'persist'  => PersistWorker::class,
                'retrieve' => RetrieveWorker::class,
            ]
        );
    }

    /**
     * Get DataDir
     *
     * @return string
     */
    public function getDataDir()
    {
        return $this->data_dir;
    }

    /**
     * Set DataDir
     *
     * @param string $data_dir
     * @return $this
     */
    public function setDataDir($data_dir)
    {
        if (PATH_SEPARATOR == '/') {
            $data_dir = str_replace('\\', '/', $data_dir);
        } else {
            $data_dir = str_replace('/', '\\', $data_dir);
        }

        if (substr($data_dir, -1) != PATH_SEPARATOR) {
            $data_dir .= PATH_SEPARATOR;
        }

        $this->data_dir = $data_dir;
        return $this;
    }

    /**
     * Get Umask
     *
     * @param bool $directory True if you need a umask for a directory (executable)
     * @return int
     */
    public function getUmask($directory = false)
    {
        if ($directory) {
            return $this->addExecuteBit($this->umask);
        } else {
            return $this->umask;
        }
    }

    /**
     * Adds the execute bit (001) to an octal trio of RWX bits where each trio has the read (100) bit
     *
     * @param $umask
     * @return int
     */
    private function addExecuteBit($umask)
    {
        for ($trio = 0; $trio < 3; $trio++) {
            $r = 4 * pow(8, $trio);
            $x = 1 * pow(8, $trio);

            if (($umask | $r) == $umask) {
                $umask = $umask | $x;
            }
        }

        return $umask;
    }

    /**
     * Set Umask
     *
     * @param int $umask
     * @return $this
     */
    public function setUmask($umask)
    {
        $this->umask = $umask;
        return $this;
    }

    /**
     * Create a debug log
     *
     * @param string $msg
     * @return void
     */
    public function debugLog($msg)
    {
        if (!$this->getDebugMode() || !$msg) {
            return;
        }

        // ..
    }

    /**
     * Persist some primitive data
     *
     * @param string         $key
     * @param SerialisedData $data
     * @param int            $ttl
     * @return void
     */
    public function persist($key, SerialisedData $data, $ttl = null)
    {
        if (!$ttl) {
            $ttl = 0;
        } else {
            $ttl = time() + $ttl;
        }

        $this->unit_of_work->queueCommand(
            new Command(
                'persist',
                [
                    'filename' => $this->keyToFilename($key),
                    'payload'  => $data->getSerialisationCode().self::DATA_DELIMITER.
                                  $ttl.self::DATA_DELIMITER.
                                  $data->getData(),
                    'umask'    => $this->getUmask()
                ]
            )
        );
    }

    /**
     * Delete a primitive document
     *
     * @param string $key
     * @return void
     */
    public function delete($key)
    {
        $fn = $this->keyToFilename($key, false);

        if (file_exists($fn)) {
            unlink($fn);
        }
    }

    /**
     * Retrieve an object
     *
     * @param string $key
     * @return SerialisedData
     */
    public function retrieve($key)
    {
        return $this->worker_pool->execute(
            new Command('retrieve', ['key' => $key, 'filename' => $this->keyToFilename($key, false)])
        );
    }

    /**
     * Execute the current unit of work
     *
     * @return void
     */
    public function flush()
    {
        while ($work = $this->unit_of_work->getWork()) {
            $this->worker_pool->execute($work);
        }
    }

    /**
     * Purge the current unit of work, clearing any unexecuted commands
     *
     * @return void
     */
    public function purge()
    {
        $this->unit_of_work->purge();
    }

    /**
     * Scan key-value indices and return the value of all matching keys
     *
     * @param string $key
     * @return string[]
     */
    public function scan($key)
    {
        // TODO: Implement scan() method.
    }

    /**
     * Get the drivers preferred key scheme
     *
     * @return KeySchemeInterface
     */
    public function getPreferredKeyScheme()
    {
        // TODO: Implement getPreferredKeyScheme() method.
    }

    /**
     * Set a key-value index
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setSingleValueIndex($key, $value)
    {
        // TODO: Implement setSingleValueIndex() method.
    }

    /**
     * Get the value of a key-value index
     *
     * If the key does not exist, null should be returned.
     *
     * @param string $key
     * @return string|null
     */
    public function getSingleValueIndex($key)
    {
        // TODO: Implement getSingleValueIndex() method.
    }

    /**
     * Clear the value of a key-value index
     *
     * @param string $key
     * @return string
     */
    public function clearSingleValueIndex($key)
    {
        // TODO: Implement clearSingleValueIndex() method.
    }

    /**
     * Clear all values from a set index
     *
     * @param string $key
     * @return void
     */
    public function clearMultiValueIndex($key)
    {
        // TODO: Implement clearMultiValueIndex() method.
    }

    /**
     * Add one or many values to a list index
     *
     * @param string       $key
     * @param string|array $value
     * @return void
     */
    public function addMultiValueIndex($key, $value)
    {
        // TODO: Implement addMultiValueIndex() method.
    }

    /**
     * Remove one or more values from a set index
     *
     * @param string       $key
     * @param string|array $value
     * @return void
     */
    public function removeMultiValueIndex($key, $value)
    {
        // TODO: Implement removeMultiValueIndex() method.
    }

    /**
     * Get a list of all values on a set index
     *
     * If the key does not exist, an empty array should be returned.
     *
     * @param string $key
     * @return string[]
     */
    public function getMultiValueIndex($key)
    {
        // TODO: Implement getMultiValueIndex() method.
    }

    /**
     * Clear an entire sorted index
     *
     * @param string $key
     * @return void
     */
    public function clearSortedIndex($key)
    {
        // TODO: Implement clearSortedIndex() method.
    }

    /**
     * Add or update an item in a sorted index
     *
     * @param string $key
     * @param mixed  $score
     * @param string $value
     * @return void
     */
    public function addSortedIndex($key, $score, $value)
    {
        // TODO: Implement addSortedIndex() method.
    }

    /**
     * Remove an item from a sorted index
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function removeSortedIndex($key, $value)
    {
        // TODO: Implement removeSortedIndex() method.
    }

    /**
     * Get a range of values in a sorted index
     *
     * If $start/$stop are === null, they are assumed to be the start/end of the entire set.
     *
     * @param string $key
     * @param bool   $reverse
     * @param int    $start
     * @param int    $stop
     * @return string[]
     */
    public function getSortedIndex($key, $reverse = false, $start = null, $stop = null)
    {
        // TODO: Implement getSortedIndex() method.
    }

    /**
     * Get the size of a sorted index, without any filters applied
     *
     * @param string $key
     * @return int
     */
    public function getSortedIndexSize($key)
    {
        // TODO: Implement getSortedIndexSize() method.
    }

    /**
     * Get all refs to an entity
     *
     * @param string $key Entity ref key
     * @return Ref[]
     */
    public function getRefs($key)
    {
        // TODO: Implement getRefs() method.
    }

    /**
     * Add a ref to an entity
     *
     * @param string $key Entity ref key
     * @param Ref    $ref Reference to add
     * @return void
     */
    public function addRef($key, Ref $ref)
    {
        // TODO: Implement addRef() method.
    }

    /**
     * Remove a ref from an entity
     *
     * If the reference does not exist, no action is taken.
     *
     * @param string $key Entity ref key
     * @param Ref    $ref Reference to remove
     * @return void
     */
    public function removeRef($key, Ref $ref)
    {
        // TODO: Implement removeRef() method.
    }

    /**
     * Clear all refs from an entity (delete a ref list)
     *
     * @param string $key
     * @return void
     */
    public function clearRefs($key)
    {
        // TODO: Implement clearRefs() method.
    }

    /**
     * Get a filename for a key, validating the directory exists
     *
     * @param string $key          Object key
     * @param bool   $validate_dir Set to false to skip directory creation
     * @return string
     */
    private function keyToFilename($key, $validate_dir = true)
    {
        $fn = $this->data_dir.$key;

        if ($validate_dir) {
            $dir = basename($fn);

            if (is_dir($dir)) {
                mkdir(basename($fn), $this->getUmask(true), true);
            }
        }

        return $fn;
    }
}
