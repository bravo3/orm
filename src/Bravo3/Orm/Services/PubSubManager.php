<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Drivers\PubSubDriverInterface;
use Bravo3\Orm\Events\PubSubEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PubSubManager
{
    /**
     * @var PubSubDriverInterface
     */
    protected $driver;

    /**
     * @var EventDispatcher
     */
    protected $event_dispatcher;

    /**
     * @param PubSubDriverInterface $driver
     */
    public function __construct(PubSubDriverInterface $driver)
    {
        $this->driver           = $driver;
        $this->event_dispatcher = new EventDispatcher();
    }

    /**
     * Function triggers PubSub events based on the messages received on subscribed channels.
     *
     * @param array $payload
     *
     * @return PubSubEvent
     */
    public function eventTrigger(array $payload)
    {
        return $this->event_dispatcher->dispatch(
            static::generateEventName($payload['channel']),
            new PubSubEvent($payload['message'])
        );
    }

    /**
     * Returns the event name with the prefix prepended.
     *
     * @param $channel
     *
     * @return string
     */
    private static function generateEventName($channel)
    {
        return sprintf('%s-%s', PubSubDriverInterface::SUBSCRIPTION_PATTERN, $channel);
    }

    /**
     * Add a callback to a channel which will get triggered when channel receives a message via a publisher.
     *
     * @param string   $channel
     * @param callable $callback
     * @param int      $priority  Default: 0
     *
     * @return PubSubManager
     */
    public function addListener($channel, callable $callback, $priority = 0)
    {
        $this->event_dispatcher->addListener(
            static::generateEventName($channel),
            $callback,
            $priority
        );

        return $this;
    }

    /**
     * Removes the callbacks assigned to database driver PubSub mechanism.
     *
     * @param string   $channel
     * @param callable $callback
     *
     * @return PubSubManager
     */
    public function removeListener($channel, callable $callback)
    {
        $this->event_dispatcher->removeListener(static::generateEventName($channel), $callback);

        return $this;
    }

    /**
     * @return void
     */
    public function run()
    {
        $this->driver->listenToPubSub([$this, 'eventTrigger']);
    }
}