<?php
namespace Bravo3\Orm\Tests\Entities;

use Bravo3\Orm\Annotations as Orm;

/**
 * @Orm\Entity(table="products")
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
}
