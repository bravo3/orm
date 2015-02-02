<?php
namespace Bravo3\Orm\Services\Aspect;

use Bravo3\Orm\Enum\Event;
use Bravo3\Orm\Events\DeleteEvent;
use Bravo3\Orm\Events\FlushEvent;
use Bravo3\Orm\Events\PersistEvent;
use Bravo3\Orm\Events\RetrieveEvent;
use Bravo3\Orm\Services\EntityManager;

class EntityManagerInterceptorFactory implements InterceptorFactoryInterface
{
    /**
     * Get all prefix interceptors
     *
     * @return \Closure[]
     */
    public function getPrefixInterceptors()
    {
        return [
            'persist'  => $this->getPrePersist(),
            'retrieve' => $this->getPreRetrieve(),
            'delete'   => $this->getPreDelete(),
            'flush'    => $this->getPreFlush(),
        ];
    }

    /**
     * Get all suffix interceptors
     *
     * @return \Closure[]
     */
    public function getSuffixInterceptors()
    {
        return [
            'persist'  => $this->getPostPersist(),
            'retrieve' => $this->getPostRetrieve(),
            'delete'   => $this->getPostDelete(),
            'flush'    => $this->getPostFlush(),
        ];
    }

    /**
     * @return \Closure
     */
    protected function getPrePersist()
    {
        return function ($proxy, $instance, $method, $params, &$returnEarly) {
            /** @var EntityManager $instance */
            $event = new PersistEvent($instance, $params['entity']);
            $event->setReturnValue($instance);
            $instance->getDispatcher()->dispatch(Event::PRE_PERSIST, $event);
            if ($event->getAbort()) {
                $returnEarly = true;
                return $event->getReturnValue();
            }
            return null;
        };
    }

    /**
     * @return \Closure
     */
    protected function getPreRetrieve()
    {
        return function ($proxy, $instance, $method, $params, &$returnEarly) {
            /** @var EntityManager $instance */
            $event = new RetrieveEvent($instance, $params['class_name'], $params['id']);
            $instance->getDispatcher()->dispatch(Event::PRE_RETRIEVE, $event);
            if ($event->getAbort()) {
                $returnEarly = true;
                return $event->getReturnValue();
            }
            return null;
        };
    }

    /**
     * @return \Closure
     */
    protected function getPreDelete()
    {
        return function ($proxy, $instance, $method, $params, &$returnEarly) {
            /** @var EntityManager $instance */
            $event = new DeleteEvent($instance, $params['entity']);
            $event->setReturnValue($instance);
            $instance->getDispatcher()->dispatch(Event::PRE_DELETE, $event);
            if ($event->getAbort()) {
                $returnEarly = true;
                return $event->getReturnValue();
            }
            return null;
        };
    }

    /**
     * @return \Closure
     */
    protected function getPreFlush()
    {
        return function ($proxy, $instance, $method, $params, &$returnEarly) {
            /** @var EntityManager $instance */
            $event = new FlushEvent($instance);
            $event->setReturnValue($instance);
            $instance->getDispatcher()->dispatch(Event::PRE_FLUSH, $event);
            if ($event->getAbort()) {
                $returnEarly = true;
                return $event->getReturnValue();
            }
            return null;
        };
    }

    /**
     * @return \Closure
     */
    protected function getPostPersist()
    {
        return function ($proxy, $instance, $method, $params, $returnValue, &$returnEarly) {
            /** @var EntityManager $instance */
            $event = new PersistEvent($instance, $params['entity']);
            $event->setReturnValue($instance);
            $instance->getDispatcher()->dispatch(Event::POST_PERSIST, $event);
            if ($event->getAbort()) {
                $returnEarly = true;
                return $event->getReturnValue();
            }
            return $returnValue;
        };
    }

    /**
     * @return \Closure
     */
    protected function getPostRetrieve()
    {
        return function ($proxy, $instance, $method, $params, $returnValue, &$returnEarly) {
            /** @var EntityManager $instance */
            $event = new RetrieveEvent($instance, $params['class_name'], $params['id'], $returnValue);
            $instance->getDispatcher()->dispatch(Event::POST_RETRIEVE, $event);
            if ($event->getAbort()) {
                $returnEarly = true;
                return $event->getReturnValue();
            }
            return $returnValue;
        };
    }


    /**
     * @return \Closure
     */
    protected function getPostDelete()
    {
        return function ($proxy, $instance, $method, $params, $returnValue, &$returnEarly) {
            /** @var EntityManager $instance */
            $event = new DeleteEvent($instance, $params['entity']);
            $event->setReturnValue($instance);
            $instance->getDispatcher()->dispatch(Event::POST_DELETE, $event);
            if ($event->getAbort()) {
                $returnEarly = true;
                return $event->getReturnValue();
            }
            return $returnValue;
        };
    }

    /**
     * @return \Closure
     */
    protected function getPostFlush()
    {
        return function ($proxy, $instance, $method, $params, $returnValue, &$returnEarly) {
            /** @var EntityManager $instance */
            $event = new FlushEvent($instance);
            $event->setReturnValue($instance);
            $instance->getDispatcher()->dispatch(Event::POST_FLUSH, $event);
            if ($event->getAbort()) {
                $returnEarly = true;
                return $event->getReturnValue();
            }
            return $returnValue;
        };
    }
}
