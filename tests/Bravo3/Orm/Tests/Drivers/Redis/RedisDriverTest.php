<?php
namespace Bravo3\Orm\Tests\Drivers\Redis;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\Tests\AbstractOrmTest;

class RedisDriverTest extends AbstractOrmTest
{
    public function testMultiSet()
    {
        $driver = $this->getDriver();
        $key    = 'test:multi:'.rand(10000, 99999);

        $driver->persist($key, new SerialisedData('xxxx', 'foo'));
        $driver->persist($key, new SerialisedData('xxxx', 'bar'));
        $driver->flush();
        $this->assertEquals('bar', $driver->retrieve($key)->getData());
    }

    public function testSingleSet()
    {
        $driver = $this->getDriver();
        $key    = 'test:single:'.rand(10000, 99999);

        $driver->persist($key, new SerialisedData('xxxx', 'bar'));
        $driver->flush();
        $this->assertEquals('bar', $driver->retrieve($key)->getData());
        $this->assertEquals('xxxx', $driver->retrieve($key)->getSerialisationCode());
    }

    public function testDelete()
    {
        $driver = $this->getDriver();
        $key    = 'test:delete:'.rand(10000, 99999);

        $driver->persist($key, new SerialisedData('xxxx', 'bar'));
        $driver->flush();
        $this->assertEquals('bar', $driver->retrieve($key)->getData());
        $driver->delete($key);
        $this->assertEquals('bar', $driver->retrieve($key)->getData());
        $driver->flush();

        try {
            $driver->retrieve($key);
            $this->fail("Retrieved a deleted key");
        } catch (NotFoundException $e) {
            $this->assertContains($key, $e->getMessage());
        }
    }
}
