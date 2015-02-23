<?php
namespace Bravo3\Orm\Tests\Entities\Maintenance;

use Bravo3\Orm\Annotations\Column;
use Bravo3\Orm\Annotations\Entity;
use Bravo3\Orm\Annotations\Id;
use Bravo3\Orm\Annotations\ManyToMany;

/**
 * @Entity(table="delta")
 */
class DeltaRevised
{
    /**
     * @var string
     * @Column(type="string")
     * @Id()
     */
    protected $id;

    /**
     * @var string
     * @Column(type="string")
     */
    protected $name;

    /**
     * @var CharlieRevised[]
     * @ManyToMany(target="Bravo3\Orm\Tests\Entities\Maintenance\CharlieRevised", inversed_by="delta")
     */
    protected $charlie;

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
     * Get Charlie
     *
     * @return CharlieRevised[]
     */
    public function getCharlie()
    {
        return $this->charlie;
    }

    /**
     * Set Charlie
     *
     * @param CharlieRevised[] $charlie
     * @return $this
     */
    public function setCharlie(array $charlie)
    {
        $this->charlie = $charlie;
        return $this;
    }
}