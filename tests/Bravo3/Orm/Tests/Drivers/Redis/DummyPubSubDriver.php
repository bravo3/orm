<?php
namespace Bravo3\Orm\Tests\Drivers\Redis;

use Bravo3\Orm\Drivers\PubSubDriverInterface;

class DummyPubSubDriver implements PubSubDriverInterface
{
    protected $message;

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function listenToPubSub(callable $callback)
    {
        call_user_func(
            $callback,
            [
                'channel' => 'unittest',
                'message' => $this->message,
            ]
        );
    }
}