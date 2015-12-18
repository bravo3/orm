<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Exceptions\UnexpectedValueException;
use Bravo3\Orm\Query\KeyScan;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Database migration, fixtures & portation
 */
class Porter implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EntityManager[]
     */
    protected $managers = [];

    /**
     * @param $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger ?: new NullLogger());
    }

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Register an entity manager
     *
     * @param string        $name    A short name to reference this manager during portation
     * @param EntityManager $manager Entity manager with driver to a source/target database
     * @return $this
     */
    public function registerManager($name, EntityManager $manager)
    {
        $this->managers[$name] = $manager;
        return $this;
    }

    /**
     * Get a registered entity manager
     *
     * @param string $name
     * @return EntityManager
     */
    public function getManager($name)
    {
        if (array_key_exists($name, $this->managers)) {
            return $this->managers[$name];
        } else {
            throw new UnexpectedValueException("Entity manager is not registered: ".$name);
        }
    }

    /**
     * List all registered entity managers
     *
     * @return string[]
     */
    public function listManagers()
    {
        return array_keys($this->managers);
    }

    /**
     * Port an entire database table from one database to another
     *
     * @param string $class_name  Fully qualified class name of entity to port
     * @param string $source      Source database name set when registering managers
     * @param string $destination Target database name set when registering managers
     * @param int    $batch_size  Number of entities to persist before flushing the destination manager
     * @return $this
     */
    public function portTable($class_name, $source, $destination, $batch_size = 100)
    {
        $src   = $this->getManager($source);
        $dest  = $this->getManager($destination);
        $table = $src->getMapper()->getEntityMetadata($class_name)->getTableName();

        $this->logger->notice('Copying '.$table.' from '.$source.' to '.$destination);
        $entities = $src->keyScan(new KeyScan($class_name, ['@id' => '*']), false);
        $this->logger->notice('Found '.number_format(count($entities)).' records');

        $maintenance = $dest->getMaintenanceMode();
        $dest->setMaintenanceMode(true);

        $counter = 0;
        $flush   = function () use (&$counter, $dest) {
            $this->logger->info('Flushing '.number_format($counter).' records');
            $dest->flush();
            $counter = 0;
        };

        foreach ($entities as $entity) {
            $dest->persist($entity);

            if (++$counter == $batch_size) {
                $flush();
            }
        }

        if ($counter) {
            $flush();
        }

        $dest->setMaintenanceMode($maintenance);
        $this->logger->notice('Portation complete for '.$table);

        return $this;
    }
}
