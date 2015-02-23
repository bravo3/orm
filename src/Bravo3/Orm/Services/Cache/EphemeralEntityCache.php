<?php
namespace Bravo3\Orm\Services\Cache;

use Bravo3\Orm\Exceptions\NotFoundException;

/**
 * In-memory soft-caching
 */
class EphemeralEntityCache implements EntityCachingInterface
{
    /**
     * @var array<string, object>
     */
    private $storage = [];

    /**
     * Get an entity storage key
     *
     * @param string $class_name
     * @param string $id
     * @return string
     */
    private function getEntityKey($class_name, $id)
    {
        return $class_name.':'.$id;
    }

    /**
     * Store an entity in cache
     *
     * @param string $class_name
     * @param string $id
     * @param object $entity
     * @return void
     */
    public function store($class_name, $id, $entity)
    {
        $this->storage[$this->getEntityKey($class_name, $id)] = $entity;
    }

    /**
     * Check if an entity exists in cache
     *
     * @param string $class_name
     * @param string $id
     * @return bool
     */
    public function exists($class_name, $id)
    {
        return array_key_exists($this->getEntityKey($class_name, $id), $this->storage);
    }

    /**
     * Retrieve an entity from the cache
     *
     * If an entity does not exist, a NotFoundException will be thrown.
     *
     * @param string $class_name
     * @param string $id
     * @return object
     */
    public function retrieve($class_name, $id)
    {
        if (!$this->exists($class_name, $id)) {
            throw new NotFoundException('Entity '.$class_name.':'.$id.' does not exist in cache');
        }

        return $this->storage[$this->getEntityKey($class_name, $id)];
    }

    /**
     * Purge an entity from cache
     *
     * If the entity does not exist, nothing will happen.
     *
     * @param string $class_name
     * @param string $id
     * @return void
     */
    public function purge($class_name, $id)
    {
        unset($this->storage[$this->getEntityKey($class_name, $id)]);
    }
}
