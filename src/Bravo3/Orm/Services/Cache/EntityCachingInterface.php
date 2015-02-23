<?php
namespace Bravo3\Orm\Services\Cache;

interface EntityCachingInterface
{
    /**
     * Store an entity in cache
     *
     * @param string $class_name
     * @param string $id
     * @param object $entity
     * @return void
     */
    public function store($class_name, $id, $entity);

    /**
     * Check if an entity exists in cache
     *
     * @param string $class_name
     * @param string $id
     * @return bool
     */
    public function exists($class_name, $id);

    /**
     * Retrieve an entity from the cache
     *
     * If an entity does not exist, a NotFoundException will be thrown.
     *
     * @param string $class_name
     * @param string $id
     * @return object
     */
    public function retrieve($class_name, $id);

    /**
     * Purge an entity from cache
     *
     * If the entity does not exist, nothing will happen.
     *
     * @param string $class_name
     * @param string $id
     * @return void
     */
    public function purge($class_name, $id);
}
