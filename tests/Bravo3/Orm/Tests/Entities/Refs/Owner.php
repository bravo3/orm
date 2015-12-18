<?php
namespace Bravo3\Orm\Tests\Entities\Refs;

use Bravo3\Orm\Annotations\Column;
use Bravo3\Orm\Annotations\Condition;
use Bravo3\Orm\Annotations\Entity;
use Bravo3\Orm\Annotations\Id;
use Bravo3\Orm\Annotations\OneToMany;
use Bravo3\Orm\Annotations\SortedIndex;

/**
 * @Entity()
 */
class Owner
{
    /**
     * @var string
     * @Id()
     * @Column(type="string")
     */
    protected $id;

    /**
     * Unreciprocated relationship
     *
     * @var Leaf[]
     * @OneToMany(target="Bravo3\Orm\Tests\Entities\Refs\Leaf",
     *      sorted_indices={
     *          @SortedIndex(column="id", conditions={
     *              @Condition(column="published", value=true)
     *          })
     *      })
     */
    protected $leaf;

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
     * Get Leaf
     *
     * @return Leaf[]
     */
    public function getLeaf()
    {
        return $this->leaf;
    }

    /**
     * Set Leaf
     *
     * @param Leaf[] $leaf
     * @return $this
     */
    public function setLeaf(array $leaf)
    {
        $this->leaf = $leaf;
        return $this;
    }
}
