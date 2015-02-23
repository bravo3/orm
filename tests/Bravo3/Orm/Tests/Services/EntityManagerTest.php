<?php
namespace Bravo3\Orm\Tests;

use Bravo3\Orm\Enum\Event;
use Bravo3\Orm\Events\PersistEvent;
use Bravo3\Orm\Events\RetrieveEvent;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Tests\Entities\BadEntity;
use Bravo3\Orm\Tests\Entities\Indexed\SluggedArticle;
use Bravo3\Orm\Tests\Entities\ModifiedEntity;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;
use Bravo3\Orm\Tests\Entities\Product;
use Bravo3\Orm\Tests\Resources\Enum;

class EntityManagerTest extends AbstractOrmTest
{
    const ENTITY_ARTICLE = 'Bravo3\Orm\Tests\Entities\OneToMany\Article';
    const ENTITY_PRODUCT = 'Bravo3\Orm\Tests\Entities\Product';

    public function testIo()
    {
        $em = $this->getEntityManager();

        $create_time = new \DateTime("2015-01-01 12:15:03+0000");

        $product = new Product();
        $product->setId(123)->setName('Test Product')->setDescription("lorem ipsum")->setActive(true)
                ->setCreateTime($create_time)->setPrice(12.45);

        $em->persist($product);
        $em->flush();

        /** @var Product|OrmProxyInterface $retrieved */
        $retrieved = $em->retrieve(self::ENTITY_PRODUCT, 123);
        $this->validateProxyInterface($retrieved);

        $this->assertEquals($product->getId(), $retrieved->getId());
        $this->assertEquals($product->getName(), $retrieved->getName());
        $this->assertEquals($product->getDescription(), $retrieved->getDescription());
        $this->assertEquals('01/01/2015 12:15:03', $retrieved->getCreateTime()->format('d/m/Y H:i:s'));
        $this->assertSame(12.45, $retrieved->getPrice());
        $this->assertTrue($retrieved->getActive());
    }

    public function testDeleteRelationships()
    {
        $em     = $this->getEntityManager();
        $client = $this->getRawRedisClient();

        $article1 = new Article();
        $article1->setId(301)->setTitle('Article 301');

        $article2 = new Article();
        $article2->setId(302)->setTitle('Article 302');

        $category1 = new Category();
        $category1->setId(351)->setName('Category 351');

        $category1->addArticle($article1)->addArticle($article2);

        $em->persist($category1)->persist($article1)->persist($article2)->flush();

        $this->assertTrue($client->exists('doc:article:301'));
        $this->assertEquals('351', $client->get('mto:article-category:301:canonical_category'));

        /** @var Article $article */
        $article = $em->retrieve(self::ENTITY_ARTICLE, 301);
        $article->setId(399);

        $em->delete($article)->flush();

        $this->assertFalse($client->exists('doc:article:301'));
        $this->assertFalse($client->exists('mto:article-category:301:canonical_category'));
    }

    public function testDeleteIndices()
    {
        $em     = $this->getEntityManager();
        $client = $this->getRawRedisClient();

        $article = new SluggedArticle();
        $article->setId(401)->setName('slugged article')->setSlug('some-slug');

        $em->persist($article)->flush();
        $this->assertTrue($client->exists('doc:slugged_article:401'));
        $this->assertEquals('401', $client->get('idx:slugged_article:slug:some-slug'));
        $this->assertEquals('401', $client->get('idx:slugged_article:name:slugged article'));

        $em->delete($article)->flush();
        $this->assertFalse($client->exists('doc:slugged_article:401'));
        $this->assertFalse($client->exists('idx:slugged_article:slug:some-slug'));
        $this->assertFalse($client->exists('idx:slugged_article:name:slugged article'));
    }

    /**
     * @group integration
     */
    public function testTtl()
    {
        $em     = $this->getEntityManager();

        $article = new Article();
        $article->setId(499)->setTitle('Cached Article');
        $em->persist($article, 2)->flush();

        $r_article = $em->retrieve(self::ENTITY_ARTICLE, '499');
        $this->assertEquals('Cached Article', $r_article->getTitle());

        sleep(3);

        try {
            $em->retrieve(self::ENTITY_ARTICLE, '499');
            $this->fail("Entity did not expire");
        } catch (NotFoundException $e) {
            // good
        }
    }

    public function testIntercepts()
    {
        $em = $this->getEntityManager();

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

        $retrieved = $em->retrieve(self::ENTITY_PRODUCT, 111);
        $this->assertTrue($retrieved instanceof Article);
    }

    public function testCreateModified()
    {
        $em = $this->getEntityManager();

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
     */
    public function testBadEntity()
    {
        $em         = $this->getEntityManager();
        $bad_entity = new BadEntity();
        $em->persist($bad_entity);
    }

    public function testSerialisation()
    {
        $em = $this->getEntityManager();

        $product = new Product();
        $product->setId(700)->setEnum(Enum::BRAVO())->setList(['a', 'b', 'c']);
        $em->persist($product)->flush();

        /** @var Product $r_product */
        $r_product = $em->retrieve(self::ENTITY_PRODUCT, '700');
        $this->assertEquals(Enum::BRAVO(), $r_product->getEnum());
        $this->assertCount(3, $r_product->getList());
        $this->assertEquals('a', $r_product->getList()[0]);
        $this->assertEquals('c', $r_product->getList()[2]);
    }

    public function testNullObjectSerialisation()
    {
        $em = $this->getEntityManager();

        $product = new Product();
        $product->setId(701)->setEnum(null);
        $em->persist($product)->flush();

        /** @var Product $r_product */
        $r_product = $em->retrieve(self::ENTITY_PRODUCT, '701');
        $this->assertNull($r_product->getEnum());
    }
}
