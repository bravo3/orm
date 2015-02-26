<?php
namespace Bravo3\Orm\Tests\Entities\Refs;

use Bravo3\Orm\Annotations\Column;
use Bravo3\Orm\Annotations\Entity;
use Bravo3\Orm\Annotations\Id;

/**
 * @Entity()
 */
class Leaf
{
    /**
     * @var string
     * @Id()
     * @Column(type="string")
     */
    protected $id;

    /**
     * @var bool
     * @Column(type="bool")
     */
    protected $published = true;

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

    /**
     * Get Published
     *
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * Set Published
     *
     * @param boolean $published
     * @return $this
     */
    public function setPublished($published)
    {
        $this->published = (bool)$published;
        return $this;
    }
}
