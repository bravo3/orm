<?php
namespace Bravo3\Orm\Tests\Drivers\Redis;

use Bravo3\Orm\Drivers\PubSubDriverInterface;

class DummyPubSubDriver implements PubSubDriverInterface
{
    protected $callbacks = [];

    public function addSubscriber($channel_name, callable $callback)
    {
        $this->callbacks[$channel_name] = $callback;
    }

    public function removeSubscriber($channel_name)
    {
        unset($this->callbacks[$channel_name]);
    }

    public function listenToPubSub()
    {
        foreach ($this->callbacks as $channel => $callback) {
            call_user_func($callback, '');
        }
    }
}