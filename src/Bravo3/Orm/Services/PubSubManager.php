<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Drivers\PubSubDriverInterface;
use Bravo3\Orm\Events\PubSubEvent;

class PubSubManager
{

    /**
     * @var PubSubDriverInterface
     */
    protected $driver;

    /**
     * @param PubSubDriverInterface $driver
     */
    public function __construct(PubSubDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @param string   $channel
     * @param callable $callback
     *
     * @return PubSubManager
     */
    public function subscribe($channel, callable $callback)
    {
        $this->driver->addSubscriber($channel, function($message) use ($callback) {
            return call_user_func($callback, new PubSubEvent($message));
        });

        return $this;
    }

    /**
     * Removes the callbacks assigned to database driver PubSub mechanism.
     *
     * @param string $channel
     * @return PubSubManager
     */
    public function unsubscribe($channel)
    {
        $this->driver->removeSubscriber($channel);

        return $this;
    }

    /**
     * @return void
     */
    public function run()
    {
        $this->driver->listenToPubSub();
    }
}