<?php
namespace Bravo3\Orm\Tests\Drivers\Redis;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Prophecy\Argument;

class RedisDriverTest extends AbstractOrmTest
{
    public function testMultiSet()
    {
        $driver = $this->getRedisDriver();
        $key    = 'test:multi:'.rand(10000, 99999);

        $driver->persist($key, new SerialisedData('xxxx', 'foo'));
        $driver->persist($key, new SerialisedData('xxxx', 'bar'));
        $driver->flush();
        $this->assertEquals('bar', $driver->retrieve($key)->getData());
    }

    public function testSingleSet()
    {
        $driver = $this->getRedisDriver();
        $key    = 'test:single:'.rand(10000, 99999);

        $driver->persist($key, new SerialisedData('xxxx', 'bar'));
        $driver->flush();
        $this->assertEquals('bar', $driver->retrieve($key)->getData());
        $this->assertEquals('xxxx', $driver->retrieve($key)->getSerialisationCode());
    }

    public function testDelete()
    {
        $driver = $this->getRedisDriver();
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
     * @expectedException \Exception
     */
    public function testClientConnectionFaliure()
    {
        $client = $this->prophesize(DummyClient::class);
        $driver = new RedisDriver(null, null, null, $client->reveal());

        $client->exists('doc:article:1')->shouldBeCalledTimes(1)->willReturn(true);
        $client->get('doc:article:1')->shouldBeCalledTimes(3)->willThrow(new \Exception);

        $driver->retrieve('doc:article:1');
    }

    public function testClientConnectionSuccessOnFirstIteration()
    {
        $client = $this->prophesize(DummyClient::class);
        $driver = new RedisDriver(null, null, null, $client->reveal());

        $client->exists('doc:article:1')->shouldBeCalledTimes(1)->willReturn(true);
        $client->get('doc:article:1')->shouldBeCalledTimes(1)->willReturn('Article');

        $driver->retrieve('doc:article:1');
    }

    public function testClientConnectionSucceedOnSecondIteration()
    {
        $client = $this->prophesize(DummyClient::class);
        $driver = new RedisDriver(null, null, null, $client->reveal());

        $client->exists('doc:article:1')->shouldBeCalledTimes(1)->willReturn(true);
        $client->get('doc:article:1')->shouldBeCalledTimes(1)->willThrow(new \Exception);
        $client->get('doc:article:1')->shouldBeCalledTimes(1)->willReturn('Article');

        $driver->retrieve('doc:article:1');
    }

    public function testRetryDelay()
    {
        $driver = $this->getRedisDriver();

        $driver->setRetryDelayCoefficient(1.5);
        $driver->setInitialRetryDelay(200);

        // 1 iteration
        $delay = $driver->calculateRetryDelay(0);
        $this->assertEquals(0, $delay);

        // 2 iteration (300 ms)
        $delay = $driver->calculateRetryDelay(1);
        $this->assertEquals(300000, $delay);

        // 3 iteration (600 ms)
        $delay = $driver->calculateRetryDelay(2);
        $this->assertEquals(600000, $delay);
    }
}
