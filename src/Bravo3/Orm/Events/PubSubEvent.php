<?php
namespace Bravo3\Orm\Events;

use Symfony\Component\EventDispatcher\Event;

class PubSubEvent extends Event
{
    /**
     * Message received via the Pub/Sub event
     * @var string
     */
    protected $payload;

    /**
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * Returns the received message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
