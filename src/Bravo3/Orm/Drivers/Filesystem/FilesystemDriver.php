<?php
namespace Bravo3\Orm\Drivers\Filesystem;

use Bravo3\Orm\Drivers\Common\Command;
use Bravo3\Orm\Drivers\Common\Ref;
use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Drivers\Common\UnitOfWork;
use Bravo3\Orm\Drivers\Common\WorkerPool;
use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Drivers\Filesystem\Io\IoDriverInterface;
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

/**
 * Filesystem (literal filesystem or any key/value store) driver
 *
 * When using any I/O driver that works with actual files, it is important to use the FilesystemKeyScheme else file
 * manipulations will have unexpected results.
 */
class FilesystemDriver implements DriverInterface
{
    use DebugTrait;

    /**
     * Files stored to the filesystem will contain the serialisation key, TTL and serialised data - these values are
     * delimited by this key.
     */
    const DATA_DELIMITER = ';';

    /**
     * @var UnitOfWork
     */
    protected $unit_of_work;

    /**
     * @var WorkerPool
     */
    protected $worker_pool;

    /**
     * @param IoDriverInterface $io_driver Filesystem IO driver
     */
    public function __construct(IoDriverInterface $io_driver)
    {
        $this->unit_of_work = new UnitOfWork();
        $this->createWorkerPool($io_driver);
    }

    /**
     * Create a worker pool with all workers registered
     *
     * @param IoDriverInterface $io_driver
     */
    protected function createWorkerPool(IoDriverInterface $io_driver)
    {
        $this->worker_pool = new WorkerPool(
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
            ],
            [
                'io_driver' => $io_driver
            ]
        );
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
                    'key'     => $key,
                    'payload' => $data->getSerialisationCode().self::DATA_DELIMITER.
                                 $ttl.self::DATA_DELIMITER.
                                 $data->getData(),
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
            new Command('delete', ['key' => $key])
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
            new Command('retrieve', ['key' => $key])
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
            new Command('scan', ['query' => $key])
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
        $this->unit_of_work->queueCommand(new Command('write', ['key' => $key, 'payload' => $value]));
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
        return $this->worker_pool->execute(new Command('read', ['key' => $key]));
    }

    /**
     * Clear the value of a key-value index
     *
     * @param string $key
     * @return string
     */
    public function clearSingleValueIndex($key)
    {
        $this->unit_of_work->queueCommand(new Command('delete', ['key' => $key]));
    }

    /**
     * Clear all values from a set index
     *
     * @param string $key
     * @return void
     */
    public function clearMultiValueIndex($key)
    {
        $this->unit_of_work->queueCommand(new Command('delete', ['key' => $key]));
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
        $this->unit_of_work->queueCommand(new Command('add_index', ['key' => $key, 'value' => $value]));
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
        $this->unit_of_work->queueCommand(new Command('remove_index', ['key' => $key, 'value' => $value]));
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
            new Command('retrieve_index', ['key' => $key])
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
        $this->unit_of_work->queueCommand(new Command('delete', ['key' => $key]));
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
            new Command('add_sorted_index', ['key' => $key, 'value' => $value, 'score' => $score])
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
        $this->unit_of_work->queueCommand(new Command('remove_sorted_index', ['key' => $key, 'value' => $value]));
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
                ['key' => $key, 'reverse' => $reverse, 'start' => $start, 'stop' => $stop]
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
        return $this->worker_pool->execute(new Command('get_index_size', ['key' => $key]));
    }

    /**
     * Get all refs to an entity
     *
     * @param string $key Entity ref key
     * @return Ref[]
     */
    public function getRefs($key)
    {
        $current = $this->worker_pool->execute(new Command('retrieve_index', ['key' => $key]));

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
        $this->unit_of_work->queueCommand(new Command('add_index', ['key' => $key, 'value' => (string)$ref]));
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
        $this->unit_of_work->queueCommand(new Command('remove_index', ['key' => $key, 'value' => (string)$ref]));
    }

    /**
     * Clear all refs from an entity (delete a ref list)
     *
     * @param string $key
     * @return void
     */
    public function clearRefs($key)
    {
        $this->unit_of_work->queueCommand(new Command('delete', ['key' => $key]));
    }
}
