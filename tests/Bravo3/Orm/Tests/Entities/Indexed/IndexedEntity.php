<?php
namespace Bravo3\Orm\Tests\Entities\Indexed;

use Bravo3\Orm\Annotations\Column;
use Bravo3\Orm\Annotations\Entity;
use Bravo3\Orm\Annotations\Id;
use Bravo3\Orm\Annotations\Index;

/**
 * @Entity(indices={
 *      @Index(name="ab", columns={"alpha", "bravo"}),
 *      @Index(name="bc", columns={"bravo"}, methods={"getCharlie"}),
 *      @Index(name="b", columns={"bravo"})
 * })
 */
class IndexedEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="int")
     */
    protected $id1;

    /**
     * @var string
     * @Id
     * @Column(type="string")
     */
    protected $id2;

    /**
     * @var string
     * @Column(type="string")
     */
    protected $alpha;

    /**
     * @var int
     * @Column(type="int")
     */
    protected $bravo;

    /**
     * @var bool
     * @Column(type="bool")
     */
    protected $charlie;

    /**
     * Get Id1
     *
     * @return int
     */
    public function getId1()
    {
        return $this->id1;
    }

    /**
     * Set Id1
     *
     * @param int $id1
     * @return $this
     */
    public function setId1($id1)
    {
        $this->id1 = (int)$id1;
        return $this;
    }

    /**
     * Get Id2
     *
     * @return string
     */
    public function getId2()
    {
        return $this->id2;
    }

    /**
     * Set Id2
     *
     * @param string $id2
     * @return $this
     */
    public function setId2($id2)
    {
        $this->id2 = (string)$id2;
        return $this;
    }

    /**
     * Get Alpha
     *
     * @return string
     */
    public function getAlpha()
    {
        return $this->alpha;
    }

    /**
     * Set Alpha
     *
     * @param string $alpha
     * @return $this
     */
    public function setAlpha($alpha)
    {
        $this->alpha = (string)$alpha;
        return $this;
    }

    /**
     * Get Bravo
     *
     * @return int
     */
    public function getBravo()
    {
        return $this->bravo;
    }

    /**
     * Set Bravo
     *
     * @param int $bravo
     * @return $this
     */
    public function setBravo($bravo)
    {
        $this->bravo = (int)$bravo;
        return $this;
    }

    /**
     * Get Charlie
     *
     * @return boolean
     */
    public function getCharlie()
    {
        return $this->charlie;
    }

    /**
     * Set Charlie
     *
     * @param boolean $charlie
     * @return $this
     */
    public function setCharlie($charlie)
    {
        $this->charlie = (bool)$charlie;
        return $this;
    }
}
