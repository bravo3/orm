<?php
namespace Bravo3\Orm\Tests\Entities;

use Bravo3\Orm\Annotations as Orm;
use Bravo3\Orm\Traits\CreateModifyInterface;

/**
 * @Orm\Entity
 */
class ModifiedEntity implements CreateModifyInterface
{
    /**
     * @var int
     * @Orm\Id
     * @Orm\Column(type="int")
     */
    protected $id;

    /**
     * @var string
     * @Orm\Column(type="string")
     */
    protected $name;

    /**
     * Get Id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @var \DateTime
     * @Orm\Column(type="datetime")
     */
    private $time_created;

    /**
     * @var \DateTime
     * @Orm\Column(type="datetime")
     */
    private $last_modified;

    /**
     * Get the time the entity was created
     *
     * @return \DateTime
     */
    public function getTimeCreated()
    {
        return $this->time_created;
    }

    /**
     * Set the time the entity was created
     *
     * @param \DateTime $time_created
     * @return $this
     */
    public function setTimeCreated(\DateTime $time_created)
    {
        $this->time_created = $time_created;
        return $this;
    }

    /**
     * Get the time the entity was last modified
     *
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->last_modified;
    }

    /**
     * Set the time the entity was last modified
     *
     * @param \DateTime $last_modified
     * @return $this
     */
    public function setLastModified(\DateTime $last_modified)
    {
        $this->last_modified = $last_modified;
        return $this;
    }
}
