<?php
namespace Bravo3\Orm\Tests;

use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Properties\Conf;
use Predis\Client;

abstract class AbstractOrmTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        $driver = $this->getDriver();
        $driver->setDebugMode(true);
        $mapper = new AnnotationMapper();
        return EntityManager::build($driver, $mapper);
    }

    protected function getDriver()
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
