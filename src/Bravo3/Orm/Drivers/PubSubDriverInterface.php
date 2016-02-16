<?php
namespace Bravo3\Orm\Drivers;

interface PubSubDriverInterface
{
    const SUBSCRIPTION_PATTERN = 'BRAVO3_EVT-*';

    /**
     * Start listening to subscribed channels of the database (if PubSub is supported)
     * @return void
     */
    public function listenToPubSub(callable $callback);

    /**
     * Publishes a message to the configured channel. Channel and Message length is limited based on the driver used.
     *
     * @param string $channel
     * @param string $message
     * @return int
     */
    public function publishMessage($channel, $message);
}