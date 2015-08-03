<?php
namespace Bravo3\Orm\Tests;

use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Drivers\Filesystem\FilesystemDriver;
use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Properties\Conf;
use Predis\Client;

abstract class AbstractOrmTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Gets the default EntityManager (RedisDriver)
     *
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

    protected function getFsDriver()
    {
        $db_path = sys_get_temp_dir().'/bravo3-orm/fs-db/';

        if (file_exists($db_path)) {
            $this->delTree($db_path);
        }

        return new FilesystemDriver($db_path);
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
}
