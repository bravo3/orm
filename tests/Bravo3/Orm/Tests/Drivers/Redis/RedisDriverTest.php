<?php
namespace Bravo3\Orm\Tests\Drivers\Redis;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\Exceptions\NotFoundException;

class RedisDriverTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @expectedException \Bravo3\Orm\Exceptions\NotFoundException
     */
    public function testNotFound()
    {
        $driver = $this->getDriver();
        $key    = 'test:not-found:'.rand(10000, 99999);

        $driver->persist($key, new SerialisedData('xxxx', 'foo'));
        $driver->retrieve($key);
    }

    protected function getDriver()
    {
        return new RedisDriver(['host' => 'localhost', 'database' => 1]);
    }
}
