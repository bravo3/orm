<?php
namespace Bravo3\Orm\Tests\Drivers\Redis;

use Bravo3\Orm\Drivers\PubSubDriverInterface;

class DummyPubSubDriver implements PubSubDriverInterface
{
    protected $message;

    protected $channel;

    protected $pubsub_channel_prefix;

    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function isPubSubSupported()
    {
        return true;
    }

    public function setChannelPrefix($prefix)
    {
        $this->pubsub_channel_prefix = $prefix;
    }

    public function getChannelPrefix()
    {
        return $this->pubsub_channel_prefix;
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