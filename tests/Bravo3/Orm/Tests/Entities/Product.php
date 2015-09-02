<?php
namespace Bravo3\Orm\Tests\Entities;

use Bravo3\Orm\Annotations as Orm;
use Bravo3\Orm\Tests\Resources\Enum;
use Doctrine\Common\Annotations\Annotation\IgnoreAnnotation;

/**
 * @IgnoreAnnotation("Foo\NotAnAnnotation")
 * @Orm\Entity(table="products")
 * @Foo\NotAnAnnotation
 */
class Product
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
     * @var string
     * @Orm\Column(type="string")
     */
    protected $description;

    /**
     * @var float
     * @Orm\Column(type="decimal")
     */
    protected $price;

    /**
     * @var bool
     * @Orm\Column(type="bool")
     */
    protected $active;

    /**
     * @var \DateTime
     * @Orm\Column(type="datetime")
     */
    protected $create_time;

    /**
     * @var Enum
     * @Orm\Column(type="object", class_name="Bravo3\Orm\Tests\Resources\Enum")
     */
    protected $enum;

    /**
     * @var array
     * @Orm\Column(type="set")
     */
    protected $list = [];

    public function __construct()
    {
        $this->enum = Enum::ALPHA();
    }

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
     * Get Description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set Description
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get Price
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set Price
     *
     * @param float $price
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;
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
        $this->active = $active;
        return $this;
    }

    /**
     * Get CreateTime
     *
     * @return \DateTime
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set CreateTime
     *
     * @param \DateTime $create_time
     * @return $this
     */
    public function setCreateTime(\DateTime $create_time = null)
    {
        $this->create_time = $create_time;
        return $this;
    }

    /**
     * Get Enum
     *
     * @return Enum
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * Set Enum
     *
     * @param Enum $enum
     * @return $this
     */
    public function setEnum(Enum $enum = null)
    {
        $this->enum = $enum;
        return $this;
    }

    /**
     * Get List
     *
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * Set List
     *
     * @param array $list
     * @return $this
     */
    public function setList(array $list)
    {
        $this->list = $list;
        return $this;
    }
}
