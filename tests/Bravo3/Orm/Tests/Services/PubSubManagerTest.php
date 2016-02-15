<?php
namespace Bravo3\Orm\Tests\Services;

use Bravo3\Orm\Events\PubSubEvent;
use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Services\PubSubManager;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Drivers\Redis\DummyPubSubDriver;

class PubSubManagerTest extends AbstractOrmTest
{
    /** @var PubSubManager pubsub_manager */
    protected $pubsub_manager = null;

    /** @var DummyPubSubDriver */
    protected $dummy_driver = null;

    public function setUp()
    {
        $this->dummy_driver = new DummyPubSubDriver();
        $this->pubsub_manager = new PubSubManager($this->dummy_driver);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testPubSubInvalidChannel()
    {
        $this->pubsub_manager->addListener(null, function(PubSubEvent $e) {

            // Should never get here
            $this->assertEquals(true, false);
        });
    }

    public function pubSubListener(PubSubEvent $e)
    {
        $this->assertEquals('event-message', $e->getMessage());
    }

    public function testPubSub()
    {
        $this->dummy_driver->setMessage('event-message');
        $this->pubsub_manager->addListener('unittest', [$this, 'pubSubListener']);
        $this->pubsub_manager->run();
    }
}
