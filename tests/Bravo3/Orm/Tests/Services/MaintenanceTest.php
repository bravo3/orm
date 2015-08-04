<?php
namespace Bravo3\Orm\Tests\Services;

use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Services\Maintenance;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Maintenance\Alpha;
use Bravo3\Orm\Tests\Entities\Maintenance\AlphaRevised;
use Bravo3\Orm\Tests\Entities\Maintenance\Bravo;
use Bravo3\Orm\Tests\Entities\Maintenance\BravoRevised;
use Bravo3\Orm\Tests\Entities\Maintenance\Charlie;
use Bravo3\Orm\Tests\Entities\Maintenance\CharlieRevised;
use Bravo3\Orm\Tests\Entities\Maintenance\Delta;
use Bravo3\Orm\Tests\Entities\Maintenance\DeltaRevised;
use Bravo3\Orm\Tests\Entities\Product;
use Bravo3\Orm\Tests\Entities\ProductLess;
use Bravo3\Orm\Tests\Entities\ProductMore;

class MaintenanceTest extends AbstractOrmTest
{
    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testRebuildSchema(EntityManager $em)
    {
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

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testRebuildIndicesOneToOne(EntityManager $em)
    {
        $alpha = new Alpha();
        $alpha->setId('alpha')->setName('Alpha');

        $bravo = new Bravo();
        $bravo->setId('bravo')->setName('Bravo');

        $alpha->setBravo($bravo);

        $em->persist($alpha)->persist($bravo)->flush();

        $a = $em->retrieve(Alpha::class, 'alpha');
        $this->assertEquals('Alpha', $a->getName());
        $this->assertEquals('Bravo', $a->getBravo()->getName());

        $maintenance = new Maintenance($em);
        $maintenance->rebuild(AlphaRevised::class);

        $b = $em->retrieve(BravoRevised::class, 'bravo');
        $this->assertEquals('Bravo', $b->getName());
        $this->assertEquals('Alpha', $b->getAlpha()->getName());
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testRebuildIndicesManyToMany(EntityManager $em)
    {
        $charlie = new Charlie();
        $charlie->setId('charlie')->setName('Charlie');

        $delta = new Delta();
        $delta->setId('delta')->setName('Delta');

        $charlie->setDelta([$delta]);

        $em->persist($charlie)->persist($delta)->flush();

        $c = $em->retrieve(Charlie::class, 'charlie');
        $this->assertEquals('Charlie', $c->getName());
        $this->assertCount(1, $c->getDelta());
        /** @var Delta $d */
        $d = $c->getDelta()[0];
        $this->assertEquals('Delta', $d->getName());

        $maintenance = new Maintenance($em);
        $maintenance->rebuild(CharlieRevised::class);

        /** @var DeltaRevised $d */
        $d = $em->retrieve(DeltaRevised::class, 'delta');
        $this->assertEquals('Delta', $d->getName());
        $this->assertCount(1, $d->getCharlie());
        /** @var CharlieRevised $c */
        $c = $d->getCharlie()[0];
        $this->assertEquals('Charlie', $c->getName());
    }
}
