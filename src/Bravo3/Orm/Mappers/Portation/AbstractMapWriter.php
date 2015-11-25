<?php
namespace Bravo3\Orm\Mappers\Portation;

use Bravo3\Orm\Services\EntityManager;

abstract class AbstractMapWriter implements MapWriterInterface
{
    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * Set the input entity managed used for reading entities
     *
     * @param EntityManager $manager
     * @return void
     */
    public function setInputManager(EntityManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Throws an exception if the entity manager is not set
     */
    protected function managerMustExist()
    {
        if (!$this->manager) {
            throw new \LogicException("You cannot write mapping data without a manager being defined");
        }
    }
}
