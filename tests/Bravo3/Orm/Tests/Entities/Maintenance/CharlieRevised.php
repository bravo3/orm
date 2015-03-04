<?php
namespace Bravo3\Orm\Tests\Entities\Maintenance;

use Bravo3\Orm\Annotations\Column;
use Bravo3\Orm\Annotations\Entity;
use Bravo3\Orm\Annotations\Id;
use Bravo3\Orm\Annotations\ManyToMany;

/**
 * @Entity(table="charlie")
 */
class CharlieRevised
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
     * @var DeltaRevised[]
     * @ManyToMany(target="Bravo3\Orm\Tests\Entities\Maintenance\DeltaRevised", inversed_by="charlie")
     */
    protected $delta;

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
     * Get Delta
     *
     * @return DeltaRevised[]
     */
    public function getDelta()
    {
        return $this->delta;
    }

    /**
     * Set Delta
     *
     * @param DeltaRevised[] $delta
     * @return $this
     */
    public function setDelta(array $delta)
    {
        $this->delta = $delta;
        return $this;
    }
}
