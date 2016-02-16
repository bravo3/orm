<?php
namespace Bravo3\Orm\Tests\Drivers\Redis;

use Bravo3\Orm\Drivers\PubSubDriverInterface;

class DummyPubSubDriver implements PubSubDriverInterface
{
    protected $message;

    protected $channel;

    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function listenToPubSub(callable $callback)
    {
        call_user_func(
            $callback,
            [
                'channel' => $this->channel,
                'message' => $this->message,
            ]
        );
    }

    public function publishMessage($channel, $message)
    {
        // do nothing
    }
}