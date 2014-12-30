<?php
namespace Bravo3\Orm\Traits;

use Bravo3\Orm\Drivers\DriverInterface;
use Bravo3\Orm\KeySchemes\KeySchemeInterface;
use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Services\EntityManager;

trait EntityManagerAwareTrait
{
    /**
     * @var EntityManager
     */
    protected $entity_manager;

    public function __construct(EntityManager $entity_manager)
    {
        $this->entity_manager = $entity_manager;
    }

    /**
     * Get the driver belonging to the entity manager
     *
     * @return DriverInterface
     */
    protected function getDriver()
    {
        return $this->entity_manager->getDriver();
    }

    /**
     * Get the key scheme belonging to the entity manager
     *
     * @return KeySchemeInterface
     */
    protected function getKeyScheme()
    {
        return $this->entity_manager->getKeyScheme();
    }

    /**
     * Get the mapper belonging to the entity manager
     *
     * @return MapperInterface
     */
    protected function getMapper()
    {
        return $this->entity_manager->getMapper();
    }
}