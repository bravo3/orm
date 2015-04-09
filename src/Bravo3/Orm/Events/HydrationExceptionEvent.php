<?php
namespace Bravo3\Orm\Events;

use Symfony\Component\EventDispatcher\Event;
use Bravo3\Orm\Exceptions\NotFoundException;

class HydrationExceptionEvent extends Event
{
    /**
     * @var NotFoundException
     */
    protected $exception;

    /**
     * __construct
     *
     * @param NotFoundException $exception
     */
    public function __construct(NotFoundException $exception)
    {
        $this->exception = $exception;
    }

    /**
     * getException
     *
     * @return NotFoundException
     */
    public function getException()
    {
        return $this->exception;
    }
}
