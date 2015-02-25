<?php
namespace Bravo3\Orm\Tests\Entities;

use Bravo3\Orm\Annotations as Orm;

/**
 * This entity has an illegal table name
 *
 * @Orm\Entity(table="very-bad-entity");
 */
class VeryBadEntity
{
    /**
     * @var string
     * @Orm\Id()
     * @Orm\Column(type="string")
     */
    protected $id;

    /**
     * Get Id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set Id
     *
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
}
