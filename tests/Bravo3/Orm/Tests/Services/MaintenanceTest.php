<?php
namespace Bravo3\Orm\Tests\Services;

use Bravo3\Orm\Services\Maintenance;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Product;
use Bravo3\Orm\Tests\Entities\ProductLess;
use Bravo3\Orm\Tests\Entities\ProductMore;

class MaintenanceTest extends AbstractOrmTest
{
    public function testRebuild()
    {
        $em = $this->getEntityManager();

        $create_time = new \DateTime("2015-01-01 12:15:03+0000");

        $product = new Product();
        $product->setId(333)->setName('Test Product')->setDescription("lorem ipsum")->setActive(true)
                ->setCreateTime($create_time)->setPrice(12.45);

        $em->persist($product);
        $em->flush();

        // Rebuild and remove a property
        $maintenance = new Maintenance($em);
        $maintenance->rebuild(ProductLess::class);

        $p1 = $em->retrieve(ProductLess::class, '333', false);
        $this->assertEquals('Test Product', $p1->getName());

        $p2 = $em->retrieve(Product::class, '333', false);
        $this->assertEquals('Test Product', $p2->getName());
        $this->assertEmpty($p2->getDescription());

        // Rebuild and add a property
        $maintenance = new Maintenance($em);
        $maintenance->rebuild(ProductMore::class);

        $p3 = $em->retrieve(ProductMore::class, '333', false);
        $this->assertEquals('Test Product', $p3->getName());
        $this->assertEmpty($p3->getShortDescription());

        $p4 = $em->retrieve(Product::class, '333', false);
        $this->assertEquals('Test Product', $p4->getName());
    }
}
