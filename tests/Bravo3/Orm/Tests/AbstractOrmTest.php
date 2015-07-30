<?php
namespace Bravo3\Orm\Tests;

use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\Drivers\Filesystem\FilesystemDriver;
use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\KeySchemes\FilesystemKeyScheme;
use Bravo3\Orm\KeySchemes\StandardKeyScheme;
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
    protected function entityManagerDataProvider()
    {
        $drivers = [
            [$this->getRedisDriver(), new StandardKeyScheme()],
            [$this->getFsDriver(), new FilesystemKeyScheme()],
        ];

        $ems = [];

        foreach ($drivers as $index => $driver_arr) {
            /** @var DriverInterface $driver */
            $driver     = $driver_arr[0];
            $key_scheme = $driver_arr[1];

            $driver->setDebugMode(true);
            $mapper = new AnnotationMapper();
            $em     = EntityManager::build($driver, $mapper, null, $key_scheme);

            $temp = sys_get_temp_dir().'/bravo3-orm/'.$index;
            if (!file_exists($temp)) {
                mkdir($temp, 0777, true);
            }
            $em->getConfig()->setCacheDir($temp);

            $ems[] = $em;
        }

        return $ems;
    }

    protected function getFsDriver()
    {
        return new FilesystemDriver();
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
