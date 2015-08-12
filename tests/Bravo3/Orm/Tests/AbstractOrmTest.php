<?php
namespace Bravo3\Orm\Tests;

use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Drivers\Filesystem\Enum\ArchiveType;
use Bravo3\Orm\Drivers\Filesystem\FilesystemDriver;
use Bravo3\Orm\Drivers\Filesystem\Io\NativeIoDriver;
use Bravo3\Orm\Drivers\Filesystem\Io\PharIoDriver;
use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\Enum\RelationshipType;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Mappers\Metadata\Index;
use Bravo3\Orm\Mappers\Metadata\Relationship;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Properties\Conf;
use Predis\Client;

abstract class AbstractOrmTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Gets the default EntityManager (RedisDriver)
     *
     * @deprecated Use the entityManagerDataProvider instead
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        $driver = $this->getRedisDriver();
        $driver->setDebugMode(true);
        $mapper = new AnnotationMapper();
        $em     = EntityManager::build($driver, $mapper);

        $temp = sys_get_temp_dir().'/bravo3-orm';
        if (!file_exists($temp)) {
            mkdir($temp, 0777, true);
        }
        $em->getConfig()->setCacheDir($temp);

        return $em;
    }

    /**
     * Gets an array of EntityManager's with different drivers
     *
     * @return EntityManager[]
     */
    public function entityManagerDataProvider()
    {
        $drivers = [
            $this->getRedisDriver(),
            $this->getFsDriver(),
            $this->getTarDriver(),
            $this->getZipDriver(),
        ];

        $ems = [];

        /** @var DriverInterface $driver */
        foreach ($drivers as $index => $driver) {
            $driver->setDebugMode(true);
            $mapper = new AnnotationMapper();
            $em     = EntityManager::build($driver, $mapper, null, $driver->getPreferredKeyScheme());

            $temp = sys_get_temp_dir().'/bravo3-orm/'.$index;
            if (!file_exists($temp)) {
                mkdir($temp, 0777, true);
            }
            $em->getConfig()->setCacheDir($temp);

            $ems[] = [$em];
        }

        return $ems;
    }

    private function getFsDriver()
    {
        $db_path = sys_get_temp_dir().'/bravo3-orm/fs-db/';

        if (file_exists($db_path)) {
            $this->delTree($db_path);
        }

        return new FilesystemDriver(new NativeIoDriver($db_path));
    }

    private function getTarDriver()
    {
        $fn = sys_get_temp_dir().'/bravo3-orm/tar.db';

        if (file_exists($fn)) {
            unlink($fn);
        }

        return new FilesystemDriver(new PharIoDriver($fn, ArchiveType::TAR()));
    }

    private function getZipDriver()
    {
        $fn = sys_get_temp_dir().'/bravo3-orm/zip.db';

        if (file_exists($fn)) {
            unlink($fn);
        }

        return new FilesystemDriver(new PharIoDriver($fn, ArchiveType::ZIP()));
    }

    private function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    protected function getRedisDriver()
    {
        return new RedisDriver($this->getPredisParams());
    }

    /**
     * @deprecated Stop using this, use the driver directly instead
     * @return Client
     */
    protected function getRawRedisClient()
    {
        return new Client($this->getPredisParams());
    }

    private function getPredisParams()
    {
        Conf::init(__DIR__.'/../../../config/', 'parameters.yml');
        return [
            'host'     => Conf::get('parameters.redis_host'),
            'port'     => Conf::get('parameters.redis_port'),
            'database' => Conf::get('parameters.redis_database')
        ];
    }

    /**
     * Does a native driver lookup to see if an entity key exists
     *
     * @param EntityManager $em
     * @param string        $table
     * @param string        $id
     * @return bool
     */
    protected function exists(EntityManager $em, $table, $id)
    {
        try {
            $em->getDriver()->retrieve($this->getEntityKey($em, $table, $id));
            return true;
        } catch (NotFoundException $e) {
            return false;
        }
    }

    protected function getEntityKey(EntityManager $em, $table, $id)
    {
        return $em->getKeyScheme()->getEntityKey($table, $id);
    }

    protected function getEntityRefKey(EntityManager $em, $table, $id)
    {
        return $em->getKeyScheme()->getEntityRefKey($table, $id);
    }

    protected function getIndexKey(EntityManager $em, $table, $index, $key)
    {
        return $em->getKeyScheme()->getIndexKey(new Index($table, $index), $key);
    }

    protected function getRelKey(EntityManager $em, $from, $to, $id, $property, RelationshipType $type)
    {
        $rel = new Relationship($property, $type);
        $rel->setSourceTable($from)->setTargetTable($to);
        return $em->getKeyScheme()->getRelationshipKey($rel, $id);
    }
}
