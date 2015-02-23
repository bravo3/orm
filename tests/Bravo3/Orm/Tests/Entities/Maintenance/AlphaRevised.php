<?php
namespace Bravo3\Orm\Tests\Entities\Maintenance;

use Bravo3\Orm\Annotations\Column;
use Bravo3\Orm\Annotations\Entity;
use Bravo3\Orm\Annotations\Id;
use Bravo3\Orm\Annotations\OneToOne;

/**
 * @Entity(table="alpha")
 */
class AlphaRevised
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
     * NB: Not inversed
     *
     * @var BravoRevised
     * @OneToOne(target="Bravo3\Orm\Tests\Entities\Maintenance\BravoRevised", inversed_by="alpha")
     */
    protected $bravo;

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
     * Get Bravo
     *
     * @return BravoRevised
     */
    public function getBravo()
    {
        return $this->bravo;
    }

    /**
     * Set Bravo
     *
     * @param BravoRevised $bravo
     * @return $this
     */
    public function setBravo(BravoRevised $bravo)
    {
        $this->bravo = $bravo;
        return $this;
    }
}
