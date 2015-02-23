<?php
namespace Bravo3\Orm\Tests\Entities\Maintenance;

use Bravo3\Orm\Annotations\Column;
use Bravo3\Orm\Annotations\Entity;
use Bravo3\Orm\Annotations\Id;
use Bravo3\Orm\Annotations\OneToOne;

/**
 * @Entity(table="bravo")
 */
class BravoRevised
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
     * @var AlphaRevised
     * @OneToOne(target="Bravo3\Orm\Tests\Entities\Maintenance\AlphaRevised", inversed_by="bravo")
     */
    protected $alpha;

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
     * Get Alpha
     *
     * @return AlphaRevised
     */
    public function getAlpha()
    {
        return $this->alpha;
    }

    /**
     * Set Alpha
     *
     * @param AlphaRevised $alpha
     * @return $this
     */
    public function setAlpha(AlphaRevised $alpha)
    {
        $this->alpha = $alpha;
        return $this;
    }
}