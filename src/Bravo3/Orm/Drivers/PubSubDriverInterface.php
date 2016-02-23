<?php
namespace Bravo3\Orm\Drivers;

interface PubSubDriverInterface
{
    const SUBSCRIPTION_PATTERN = 'BRAVO3_EVT';

    /**
     * Confirm that the driver supports Pub/Sub messaging.
     *
     * @return bool
     */
    public function isPubSubSupported();

    /**
     * Sets the PubSub messaging channel prefix used in the underlying database driver.
     *
     * @param string $prefix
     *
     * @return PubSubDriverInterface
     */
    public function setChannelPrefix($prefix);

    /**
     * Return the PubSub messaging channel prefix used in the underlying database driver.
     *
     * @return string
     */
    public function getChannelPrefix();

    /**
     * Start listening to subscribed channels of the database (if PubSub is supported)
     *
     * @param callable $callback
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