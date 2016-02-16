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

    public function pubSubListener_1(PubSubEvent $e)
    {
        $this->assertEquals('event-message', $e->getMessage());
    }

    public function pubSubListener_2(PubSubEvent $e)
    {
        $this->assertEquals('event-message', $e->getMessage());
    }

    public function pubSubListener_3(PubSubEvent $e)
    {
        $this->assertEquals('dummy-task', $e->getMessage());
    }

    public function testPubSubMultipleListeners()
    {
        // Setup driver
        $this->dummy_driver->setMessage('event-message');
        $this->dummy_driver->setChannel('unittest');

        // Listeners
        $this->pubsub_manager->addListener('unittest', [$this, 'pubSubListener_1']);
        $this->pubsub_manager->addListener('unittest', [$this, 'pubSubListener_2']);

        $this->pubsub_manager->run();
    }

    public function testPubSubMultipleChannelsAndListeners()
    {
        // Driver config 1
        $this->dummy_driver->setMessage('event-message');
        $this->dummy_driver->setChannel('unittest');

        // First set of listeners on 'unittest' channel
        $this->pubsub_manager->addListener('unittest', [$this, 'pubSubListener_1']);
        $this->pubsub_manager->addListener('unittest', [$this, 'pubSubListener_2']);

        // Driver config 2
        $this->dummy_driver->setMessage('dummy-task');
        $this->dummy_driver->setChannel('dummy');

        // Second set of listeners on 'dummy' channel
        $this->pubsub_manager->addListener('dummy', [$this, 'pubSubListener_3']);

        $this->pubsub_manager->run();
    }
}
