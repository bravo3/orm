<?php
namespace Bravo3\Orm\Tests\Services;

use Bravo3\Orm\Events\PubSubEvent;
use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Bravo3\Orm\Services\PubSubManager;
use Bravo3\Orm\Tests\AbstractOrmTest;

class PubSubManagerTest extends AbstractOrmTest
{
    /** @var PubSubManager pubsub_manager */
    protected $pubsub_manager = null;

    public function setUp()
    {
        /** @var PubSubManager pubsub_manager */
        $this->pubsub_manager = new PubSubManager($this->getRedisDriver());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testPubSubInvalidChannel()
    {
        $this->pubsub_manager->addListener('', function(PubSubEvent $e) {

            // Should never get here
            $this->assertEquals(true, false);
        });
    }

    public function pubSubListener(PubSubEvent $e)
    {
        $this->assertEquals('test', $e->getMessage());
    }

    public function testPubSub()
    {
        $this->pubsub_manager->addListener('bg_task', [$this, 'pubSubListener']);

        $this->pubsub_manager->run();

    }
}
