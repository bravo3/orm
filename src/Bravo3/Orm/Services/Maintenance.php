<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Query\IndexedQuery;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Performs maintenance tasks on ORM-controlled tables
 */
class Maintenance
{
    /**
     * @var EntityManager
     */
    protected $entity_manager;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param EntityManager   $entity_manager
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $entity_manager, LoggerInterface $logger = null)
    {
        $this->entity_manager = $entity_manager;
        $this->logger         = $logger ?: new NullLogger();
    }

    /**
     * Will rebuild a table, repairing indices and re-serialising content
     *
     * The end result will be:
     * - new inverse indices will be created
     * - changes to serialisation will be updated on all entities
     * - added/removed fields will be updated on all entities
     *
     * @param string $class_name
     * @param int    $batch_size
     */
    public function rebuild($class_name, $batch_size = 100)
    {
        $this->maintenanceOperation(
            function () use ($class_name, $batch_size) {
                $metadata = $this->entity_manager->getMapper()->getEntityMetadata($class_name);
                $this->logger->info("Rebuilding `".$metadata->getTableName()."`..");
                $records = $this->entity_manager->indexedQuery(new IndexedQuery($class_name, ['@id' => '*']), false);
                $this->logger->info(
                    number_format($records->count()).' records to rebuild, '.number_format($batch_size).' at a time'
                );
                $ts = microtime(true);
                $this->rebuildRecords($records, $batch_size);
                $delta = microtime(true) - $ts;
                $this->logger->info(
                    "Rebuild of `".$metadata->getTableName()."` completed in ".number_format($delta, 2)." seconds"
                );
            }
        );
    }

    /**
     * Execute a function in maintenance mode
     *
     * @param callable $closure
     */
    protected function maintenanceOperation($closure)
    {
        $mode = $this->entity_manager->getMaintenanceMode();
        $this->entity_manager->setMaintenanceMode(true);
        try {
            $closure();
        } finally {
            $this->entity_manager->setMaintenanceMode($mode);
        }
    }

    /**
     * Re-persist an array of records
     *
     * @param \Traversable $records
     * @param int          $batch_size
     */
    private function rebuildRecords($records, $batch_size)
    {
        $count = 0;
        /** @var OrmProxyInterface $record */
        foreach ($records as $record) {
            $this->entity_manager->persist($record);

            if (++$count == $batch_size) {
                $this->entity_manager->flush();
                $count = 0;
            }
        }

        if ($count) {
            $this->entity_manager->flush();
        }
    }
}
