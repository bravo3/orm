<?php
namespace Bravo3\Orm\Drivers\Filesystem;

use Bravo3\Orm\Drivers\Common\Command;
use Bravo3\Orm\Drivers\Common\Ref;
use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Drivers\Common\UnitOfWork;
use Bravo3\Orm\Drivers\Common\WorkerPool;
use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Drivers\Filesystem\Workers\AddIndexWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\AddSortedIndexWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\DeleteWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\GetIndexSizeWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\ReadWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\RemoveIndexWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\RemoveSortedIndexWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\RetrieveIndexWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\RetrieveSortedIndexWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\RetrieveWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\ScanWorker;
use Bravo3\Orm\Drivers\Filesystem\Workers\WriteWorker;
use Bravo3\Orm\KeySchemes\FilesystemKeyScheme;
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
                'read'                  => ReadWorker::class,
                'write'                 => WriteWorker::class,
                'retrieve'              => RetrieveWorker::class,
                'delete'                => DeleteWorker::class,
                'add_sorted_index'      => AddSortedIndexWorker::class,
                'remove_sorted_index'   => RemoveSortedIndexWorker::class,
                'retrieve_sorted_index' => RetrieveSortedIndexWorker::class,
                'add_index'             => AddIndexWorker::class,
                'remove_index'          => RemoveIndexWorker::class,
                'retrieve_index'        => RetrieveIndexWorker::class,
                'get_index_size'        => GetIndexSizeWorker::class,
                'scan'                  => ScanWorker::class,
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
        if (DIRECTORY_SEPARATOR == '/') {
            $data_dir = str_replace('\\', '/', $data_dir);
        } else {
            $data_dir = str_replace('/', '\\', $data_dir);
        }

        if (substr($data_dir, -1) != DIRECTORY_SEPARATOR) {
            $data_dir .= DIRECTORY_SEPARATOR;
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
                'write',
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
        $this->worker_pool->execute(
            new Command('delete', ['filename' => $this->keyToFilename($key, false)])
        );
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
        return $this->worker_pool->execute(
            new Command('scan', ['query' => $this->keyToFilename($key)])
        );
    }

    /**
     * Get the drivers preferred key scheme
     *
     * @return KeySchemeInterface
     */
    public function getPreferredKeyScheme()
    {
        return new FilesystemKeyScheme();
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
        $this->unit_of_work->queueCommand(
            new Command(
                'write',
                [
                    'filename' => $this->keyToFilename($key),
                    'payload'  => $value,
                    'umask'    => $this->getUmask()
                ]
            )
        );
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
        return $this->worker_pool->execute(
            new Command('read', ['filename' => $this->keyToFilename($key, false)])
        );
    }

    /**
     * Clear the value of a key-value index
     *
     * @param string $key
     * @return string
     */
    public function clearSingleValueIndex($key)
    {
        $this->unit_of_work->queueCommand(
            new Command('delete', ['filename' => $this->keyToFilename($key, false)])
        );
    }

    /**
     * Clear all values from a set index
     *
     * @param string $key
     * @return void
     */
    public function clearMultiValueIndex($key)
    {
        $this->unit_of_work->queueCommand(
            new Command('delete', ['filename' => $this->keyToFilename($key, false)])
        );
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
        $this->unit_of_work->queueCommand(
            new Command('add_index',
                        [
                            'filename' => $this->keyToFilename($key),
                            'value'    => $value,
                            'umask'    => $this->getUmask()
                        ])
        );
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
        $this->unit_of_work->queueCommand(
            new Command('remove_index',
                        [
                            'filename' => $this->keyToFilename($key),
                            'value'    => $value,
                            'umask'    => $this->getUmask()
                        ])
        );
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
        return $this->worker_pool->execute(
            new Command('retrieve_index', ['filename' => $this->keyToFilename($key, false)])
        );
    }

    /**
     * Clear an entire sorted index
     *
     * @param string $key
     * @return void
     */
    public function clearSortedIndex($key)
    {
        $this->unit_of_work->queueCommand(
            new Command('delete', ['filename' => $this->keyToFilename($key, false)])
        );
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
        $this->unit_of_work->queueCommand(
            new Command('add_sorted_index',
                        [
                            'filename' => $this->keyToFilename($key),
                            'value'    => $value,
                            'score'    => $score,
                            'umask'    => $this->getUmask()
                        ])
        );
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
        $this->unit_of_work->queueCommand(
            new Command('remove_sorted_index',
                        [
                            'filename' => $this->keyToFilename($key),
                            'value'    => $value,
                            'umask'    => $this->getUmask()
                        ])
        );
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
        return $this->worker_pool->execute(
            new Command(
                'retrieve_sorted_index',
                [
                    'filename' => $this->keyToFilename($key, false),
                    'reverse'  => $reverse,
                    'start'    => $start,
                    'stop'     => $stop,
                ]
            )
        );
    }

    /**
     * Get the size of a sorted index, without any filters applied
     *
     * @param string $key
     * @return int
     */
    public function getSortedIndexSize($key)
    {
        return $this->worker_pool->execute(
            new Command('get_index_size', ['filename' => $this->keyToFilename($key, false)])
        );
    }

    /**
     * Get all refs to an entity
     *
     * @param string $key Entity ref key
     * @return Ref[]
     */
    public function getRefs($key)
    {
        $current = $this->worker_pool->execute(
            new Command('retrieve_index', ['filename' => $this->keyToFilename($key, false)])
        );

        array_walk(
            $current,
            function (&$item) {
                $item = Ref::fromString($item);
            }
        );

        return $current;
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
        $this->unit_of_work->queueCommand(
            new Command('add_index',
                        [
                            'filename' => $this->keyToFilename($key),
                            'value'    => (string)$ref,
                            'umask'    => $this->getUmask()
                        ])
        );
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
        $this->unit_of_work->queueCommand(
            new Command('remove_index',
                        [
                            'filename' => $this->keyToFilename($key),
                            'value'    => (string)$ref,
                            'umask'    => $this->getUmask()
                        ])
        );
    }

    /**
     * Clear all refs from an entity (delete a ref list)
     *
     * @param string $key
     * @return void
     */
    public function clearRefs($key)
    {
        $this->unit_of_work->queueCommand(
            new Command('delete', ['filename' => $this->keyToFilename($key, false)])
        );
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
            $dir = dirname($fn);

            if (!is_dir($dir)) {
                mkdir(dirname($fn), $this->getUmask(true), true);
            }
        }

        return $fn;
    }
}
