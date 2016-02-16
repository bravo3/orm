<?php
namespace Bravo3\Orm\Services;

use Bravo3\Orm\Drivers\PubSubDriverInterface;
use Bravo3\Orm\Events\PubSubEvent;
use Bravo3\Orm\Exceptions\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PubSubManager
{
    const EVENT_PREFIX = 'PUBSUB';

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
     * Publishes a message to the configured channel. Channel and Message length is limited based on the driver used.
     * Returns true on success of delivery of the message, or false on failure.
     * Usually a message delivery fails if there are not subscribed clients for the channel.
     *
     * @param string $channel
     * @param string $message
     * @return bool
     */
    public function publish($channel, $message)
    {
        return (bool) $this->driver->publishMessage(static::generateEventName($channel), $message);
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
        return sprintf('%s-%s', static::EVENT_PREFIX, $channel);
    }

    /**
     * Add a callback to a channel which will get triggered when channel receives a message via a publisher.
     *
     * @param string   $channel
     * @param callable $callback
     * @param int      $priority  Default: 0
     *
     * @return PubSubManager
     * @throws InvalidArgumentException
     */
    public function addListener($channel, callable $callback, $priority = 0)
    {
        if (empty($channel)) {
            throw new InvalidArgumentException('Empty event channel name supplied.');
        }

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