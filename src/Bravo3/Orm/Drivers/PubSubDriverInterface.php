<?php
namespace Bravo3\Orm\Drivers;

interface PubSubDriverInterface
{
    /**
     * Add a callback to a particular subscription channel.
     *
     * @param string   $channel_name
     * @param callable $callback
     *
     * @return PubSubDriverInterface
     */
    public function addSubscriber($channel_name, callable $callback);

    /**
     * Remove a callback from a particular subscription channel.
     *
     * @param  string $channel_name
     * @return PubSubDriverInterface
     */
    public function removeSubscriber($channel_name);

    /**
     * Start listening to subscribed channels of the database (if PubSub is supported)
     * @return void
     */
    public function listenToPubSub();
}