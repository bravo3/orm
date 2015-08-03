<?php
namespace Bravo3\Orm\Tests;

use Bravo3\Orm\Enum\Event;
use Bravo3\Orm\Events\PersistEvent;
use Bravo3\Orm\Events\RetrieveEvent;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Tests\Entities\BadEntity;
use Bravo3\Orm\Tests\Entities\Indexed\IndexedEntity;
use Bravo3\Orm\Tests\Entities\ModifiedEntity;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\Product;
use Bravo3\Orm\Tests\Resources\Enum;

class EntityManagerTest extends AbstractOrmTest
{
    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testIo(EntityManager $em)
    {
        $create_time = new \DateTime("2015-01-01 12:15:03+0000");

        $product = new Product();
        $product->setId(123)->setName('Test Product')->setDescription("lorem ipsum")->setActive(true)
                ->setCreateTime($create_time)->setPrice(12.45);

        $em->persist($product);
        $em->flush();

        /** @var Product|OrmProxyInterface $retrieved */
        $retrieved = $em->retrieve(Product::class, 123);
        $this->validateProxyInterface($retrieved);

        $this->assertEquals($product->getId(), $retrieved->getId());
        $this->assertEquals($product->getName(), $retrieved->getName());
        $this->assertEquals($product->getDescription(), $retrieved->getDescription());
        $this->assertEquals('01/01/2015 12:15:03', $retrieved->getCreateTime()->format('d/m/Y H:i:s'));
        $this->assertSame(12.45, $retrieved->getPrice());
        $this->assertTrue($retrieved->getActive());
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testRefresh(EntityManager $em)
    {
        $product = new Product();
        $product->setId(222)->setName('Test Product')->setDescription("lorem ipsum");
        $em->persist($product)->flush();

        $r = $em->retrieve(Product::class, '222');
        $r->setDescription('hello world');
        $em->persist($r)->flush();

        $this->assertEquals('lorem ipsum', $product->getDescription());
        $this->assertFalse($product instanceof OrmProxyInterface);
        $em->refresh($product);
        $this->assertEquals('hello world', $product->getDescription());
        $this->assertTrue($product instanceof OrmProxyInterface);
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testCache(EntityManager $em)
    {
        $product = new Product();
        $product->setId(212)->setName('Test Product')->setDescription("lorem ipsum");
        $em->persist($product)->flush();

        $r = $em->retrieve(Product::class, '212');
        $r->setDescription('hello world');

        $r1 = $em->retrieve(Product::class, '212');
        $this->assertEquals('hello world', $r1->getDescription());

        $r2 = $em->retrieve(Product::class, '212', false);
        $this->assertEquals('lorem ipsum', $r2->getDescription());

        $em->getCache()->purge(Product::class, '212');

        $r3 = $em->retrieve(Product::class, '212');
        $this->assertEquals('lorem ipsum', $r3->getDescription());
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testCacheIndex(EntityManager $em)
    {
        $product = new IndexedEntity();
        $product->setId1(212)->setId2('test')->setAlpha('index-test')->setBravo(888);
        $em->persist($product)->flush();

        $r = $em->retrieve(IndexedEntity::class, '212.test');
        $r->setBravo(999);

        $r1 = $em->retrieveByIndex(IndexedEntity::class, 'ab', 'index-test.888');
        $this->assertEquals(999, $r1->getBravo());

        $r2 = $em->retrieveByIndex(IndexedEntity::class, 'ab', 'index-test.888', false);
        $this->assertEquals(888, $r2->getBravo());

        $em->getCache()->purge(IndexedEntity::class, '212.test');

        $r3 = $em->retrieveByIndex(IndexedEntity::class, 'ab', 'index-test.888');
        $this->assertEquals(888, $r3->getBravo());
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @group        integration
     * @param EntityManager $em
     */
    public function testTtl(EntityManager $em)
    {
        $article = new Article();
        $article->setId(499)->setTitle('Cached Article');
        $em->persist($article, 2)->flush();

        $r_article = $em->retrieve(Article::class, '499', false);
        $this->assertEquals('Cached Article', $r_article->getTitle());

        sleep(3);

        $this->setExpectedException(NotFoundException::class);
        $em->retrieve(Article::class, '499', false);
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testIntercepts(EntityManager $em)
    {
        $em->getDispatcher()->addListener(
            Event::PRE_RETRIEVE,
            function (RetrieveEvent $event) {
                // Return something completely different instead
                $event->setReturnValue(new Article());
                $event->setAbort(true);
            }
        );

        $em->getDispatcher()->addListener(
            Event::POST_PERSIST,
            function (PersistEvent $event) {
                /** @var Product $entity */
                $entity = $event->getEntity();
                $this->assertTrue($entity instanceof Product);
                $entity->setName('Persisted Product');
            }
        );

        $product = new Product();
        $product->setId(111)->setName('New Product')->setCreateTime(new \DateTime());

        $em->persist($product);
        $em->flush();

        $this->assertEquals('Persisted Product', $product->getName());

        $retrieved = $em->retrieve(Product::class, 111);
        $this->assertTrue($retrieved instanceof Article);
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testCreateModified(EntityManager $em)
    {
        $entity = new ModifiedEntity();
        $entity->setId(111)->setName('Create/Modify Entity');

        $this->assertNull($entity->getLastModified());
        $this->assertNull($entity->getTimeCreated());

        $em->persist($entity);

        $this->assertTrue($entity->getLastModified() instanceof \DateTime);
        $this->assertTrue($entity->getTimeCreated() instanceof \DateTime);
        $this->assertEquals(date('Y-m-d'), $entity->getLastModified()->format('Y-m-d'));
        $this->assertEquals(date('Y-m-d'), $entity->getTimeCreated()->format('Y-m-d'));
    }

    private function validateProxyInterface(OrmProxyInterface $proxy)
    {
        $this->assertFalse($proxy->isRelativeModified('foo'));
        $proxy->setRelativeModified('foo');
        $this->assertTrue($proxy->isRelativeModified('foo'));
    }

    /**
     * @expectedException \Bravo3\Orm\Exceptions\InvalidEntityException
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testBadEntity(EntityManager $em)
    {
        $bad_entity = new BadEntity();
        $em->persist($bad_entity);
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testSerialisation(EntityManager $em)
    {
        $product = new Product();
        $product->setId(700)->setEnum(Enum::BRAVO())->setList(['a', 'b', 'c']);
        $em->persist($product)->flush();

        /** @var Product $r_product */
        $r_product = $em->retrieve(Product::class, '700');
        $this->assertEquals(Enum::BRAVO(), $r_product->getEnum());
        $this->assertCount(3, $r_product->getList());
        $this->assertEquals('a', $r_product->getList()[0]);
        $this->assertEquals('c', $r_product->getList()[2]);
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testNullObjectSerialisation(EntityManager $em)
    {
        $product = new Product();
        $product->setId(701)->setEnum(null);
        $em->persist($product)->flush();

        /** @var Product $r_product */
        $r_product = $em->retrieve(Product::class, '701');
        $this->assertNull($r_product->getEnum());
    }
}
