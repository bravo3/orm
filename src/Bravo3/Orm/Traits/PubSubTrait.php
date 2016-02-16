<?php
namespace Bravo3\Orm\Traits;

trait PubSubTrait
{
    /**
     * @var string
     */
    protected $pubsub_channel_prefix = self::SUBSCRIPTION_PATTERN;

    /**
     * Sets the PubSub messaging channel prefix used in the underlying database driver.
     *
     * @param string $prefix
     *
     * @return RedisDriver
     */
    public function setChannelPrefix($prefix)
    {
        $this->pubsub_channel_prefix = $prefix;
    }

    /**
     * Return the PubSub messaging channel prefix used in the underlying database driver.
     *
     * @return string
     */
    public function getChannelPrefix()
    {
        return $this->pubsub_channel_prefix;
    }

}
