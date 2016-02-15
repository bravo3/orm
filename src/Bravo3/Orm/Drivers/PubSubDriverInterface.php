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
}