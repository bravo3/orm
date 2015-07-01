<?php
namespace Bravo3\Orm\Tests\Entities;

use Bravo3\Orm\Annotations\Column;
use Bravo3\Orm\Annotations\Condition;
use Bravo3\Orm\Annotations\Entity;
use Bravo3\Orm\Annotations\Id;
use Bravo3\Orm\Annotations\Sortable;

/**
 * @Entity(sortable_by={
 *      @Sortable(name="name_active", column="name", conditions={
 *          @Condition(column="active", value=true)
 *      }),
 *      @Sortable(name="name_all", column="name")
 * })
 */
class SortedUser
{
    /**
     * @var int
     * @Column(type="int")
     * @Id()
     */
    protected $id;

    /**
     * @var string
     * @Column(type="string")
     */
    protected $name;

    /**
     * @var bool
     * @Column(type="bool")
     */
    protected $active;

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
     * Get Active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set Active
     *
     * @param boolean $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = (bool)$active;
        return $this;
    }
}
