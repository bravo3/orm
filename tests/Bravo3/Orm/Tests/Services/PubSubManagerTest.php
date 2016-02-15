<?php
namespace Bravo3\Orm\Tests\Services;

use Bravo3\Orm\Events\PubSubEvent;
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

    public function testPubSub()
    {
        $this->pubsub_manager->addListener('test_sub_channel', function(PubSubEvent $e) {
            exit($e->getMessage());
        });

        $this->pubsub_manager->run();

    }
}
