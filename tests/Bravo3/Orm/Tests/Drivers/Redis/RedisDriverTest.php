<?php
namespace Bravo3\Orm\Tests\Drivers\Redis;

use Bravo3\Orm\Drivers\Common\SerialisedData;
use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\Query\SortedQuery;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Indexed\SluggedArticle;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;
use Bravo3\Orm\Tests\Entities\Refs\Article as RefArticle;
use Bravo3\Orm\Tests\Entities\Refs\Category as RefCategory;
use Bravo3\Orm\Tests\Entities\Refs\Leaf;
use Bravo3\Orm\Tests\Entities\Refs\Owner;
use Prophecy\Argument;


class RedisDriverTest extends AbstractOrmTest
{
    public function testMultiSet()
    {
        $driver = $this->getRedisDriver();
        $key    = 'test:multi:'.rand(10000, 99999);

        $driver->persist($key, new SerialisedData('xxxx', 'foo'));
        $driver->persist($key, new SerialisedData('xxxx', 'bar'));
        $driver->flush();
        $this->assertEquals('bar', $driver->retrieve($key)->getData());
    }

    public function testSingleSet()
    {
        $driver = $this->getRedisDriver();
        $key    = 'test:single:'.rand(10000, 99999);

        $driver->persist($key, new SerialisedData('xxxx', 'bar'));
        $driver->flush();
        $this->assertEquals('bar', $driver->retrieve($key)->getData());
        $this->assertEquals('xxxx', $driver->retrieve($key)->getSerialisationCode());
    }

    public function testDelete()
    {
        $driver = $this->getRedisDriver();
        $key    = 'test:delete:'.rand(10000, 99999);

        $driver->persist($key, new SerialisedData('xxxx', 'bar'));
        $driver->flush();
        $this->assertEquals('bar', $driver->retrieve($key)->getData());
        $driver->delete($key);
        $this->assertEquals('bar', $driver->retrieve($key)->getData());
        $driver->flush();

        try {
            $driver->retrieve($key);
            $this->fail("Retrieved a deleted key");
        } catch (NotFoundException $e) {
            $this->assertContains($key, $e->getMessage());
        }
    }

    /**
     * @expectedException \Exception
     */
    public function testClientConnectionFailure()
    {
        $client = $this->prophesize(DummyClient::class);
        $driver = new RedisDriver(null, null, null, $client->reveal());

        $client->exists('doc:article:1')->shouldBeCalledTimes(1)->willReturn(true);
        $client->get('doc:article:1')->shouldBeCalledTimes(3)->willThrow(new \Exception);

        $driver->retrieve('doc:article:1');
    }

    public function testClientConnectionSuccessOnFirstIteration()
    {
        $client = $this->prophesize(DummyClient::class);
        $driver = new RedisDriver(null, null, null, $client->reveal());

        $client->exists('doc:article:1')->shouldBeCalledTimes(1)->willReturn(true);
        $client->get('doc:article:1')->shouldBeCalledTimes(1)->willReturn('Article');

        $driver->retrieve('doc:article:1');
    }

    public function testClientConnectionSucceedOnSecondIteration()
    {
        $client = $this->prophesize(DummyClient::class);
        $driver = new RedisDriver(null, null, null, $client->reveal());

        $client->exists('doc:article:1')->shouldBeCalledTimes(1)->willReturn(true);
        $client->get('doc:article:1')->shouldBeCalledTimes(1)->willThrow(new \Exception);
        $client->get('doc:article:1')->shouldBeCalledTimes(1)->willReturn('Article');

        $driver->retrieve('doc:article:1');
    }

    public function testRetryDelay()
    {
        $driver = $this->getRedisDriver();

        $driver->setRetryDelayCoefficient(1.5);
        $driver->setInitialRetryDelay(200);

        // 1 iteration
        $delay = $driver->calculateRetryDelay(0);
        $this->assertEquals(0, $delay);

        // 2 iteration (300 ms)
        $delay = $driver->calculateRetryDelay(1);
        $this->assertEquals(300000, $delay);

        // 3 iteration (600 ms)
        $delay = $driver->calculateRetryDelay(2);
        $this->assertEquals(600000, $delay);
    }

    public function testRefs()
    {
        $em = $this->getEntityManager();

        $client  = $this->getRawRedisClient();
        $members = $client->smembers('ref:leaf:leaf1');
        $this->assertCount(0, $members);

        $leaf  = (new Leaf())->setId('leaf1');
        $owner = (new Owner())->setId('owner1')->setLeaf([$leaf]);

        $em->persist($leaf)->persist($owner)->flush();

        $client  = $this->getRawRedisClient();
        $members = $client->smembers('ref:leaf:leaf1');

        $this->assertCount(1, $members);
        $this->assertEquals('Bravo3\Orm\Tests\Entities\Refs\Owner:owner1:leaf', $members[0]);

        $leaves = $em->sortedQuery(new SortedQuery($owner, 'leaf', 'id'));
        $this->assertCount(1, $leaves);

        $em->refresh($owner);
        $em->refresh($leaf);

        $leaf->setPublished(false);
        $em->persist($leaf)->flush();

        $leaves = $em->sortedQuery(new SortedQuery($owner, 'leaf', 'id'));
        $this->assertCount(0, $leaves);

        $leaf->setPublished(true);
        $em->persist($leaf)->flush();

        $leaves = $em->sortedQuery(new SortedQuery($owner, 'leaf', 'id'));
        $this->assertCount(1, $leaves);

        $em->delete($leaf)->flush();

        $leaves = $em->sortedQuery(new SortedQuery($owner, 'leaf', 'id'));
        $this->assertCount(0, $leaves);
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
        $article = $em->retrieve(Article::class, 301);
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
     * Use ref's to delete non-inversed relationships
     */
    public function testDeleteIndicesAndRelationships()
    {
        $em     = $this->getEntityManager();
        $client = $this->getRawRedisClient();

        $article1 = new RefArticle();
        $article1->setId(501)->setTitle('Ref Article 501');

        $category1 = new RefCategory();
        $category1->setId(532)->setName('Ref Category 532');

        $article1->setCanonicalCategory($category1);

        $em->persist($category1)->persist($article1)->flush();

        $this->assertTrue($client->exists('doc:article:501'));
        $this->assertEquals('532', $client->get('mto:article-category:501:canonical_category'));

        // Not inversed:
        $this->assertFalse(in_array('501', $client->smembers('otm:category-article:532:articles')));

        // Ref exists:
        $this->assertTrue($client->exists('ref:category:532'));
        $this->assertTrue(
            in_array(
                'Bravo3\Orm\Tests\Entities\Refs\Article:501:canonical_category',
                $client->smembers('ref:category:532')
            )
        );

        /** @var RefArticle $article */
        $article = $em->retrieve(RefArticle::class, 501);
        $em->delete($article)->flush();

        $this->assertFalse($client->exists('doc:article:501'));
        $this->assertFalse($client->exists('mto:article-category:501:canonical_category'));
        $this->assertFalse(in_array('501', $client->smembers('otm:category-article:532:articles')));

        // Ref no longer needed:
        $this->assertFalse(
            in_array(
                'Bravo3\Orm\Tests\Entities\Refs\Article:501:canonical_category',
                $client->smembers('ref:category:532')
            )
        );
    }

    /**
     * Same as above test, except we'll delete the category
     */
    public function testDeleteIndicesAndRelationshipsAlt()
    {
        $em     = $this->getEntityManager();
        $client = $this->getRawRedisClient();

        $article1 = new RefArticle();
        $article1->setId(502)->setTitle('Ref Article 502');

        $category1 = new RefCategory();
        $category1->setId(533)->setName('Ref Category 533');

        $article1->setCanonicalCategory($category1);

        $em->persist($category1)->persist($article1)->flush();

        $this->assertTrue($client->exists('doc:article:502'));
        $this->assertEquals('533', $client->get('mto:article-category:502:canonical_category'));

        // Not inversed:
        $this->assertFalse(in_array('502', $client->smembers('otm:category-article:533:articles')));

        // Ref exists:
        $this->assertTrue($client->exists('ref:category:533'));
        $this->assertTrue(
            in_array(
                'Bravo3\Orm\Tests\Entities\Refs\Article:502:canonical_category',
                $client->smembers('ref:category:533')
            )
        );

        /** @var RefCategory $category */
        $category = $em->retrieve(RefCategory::class, 533);
        $em->delete($category)->flush();

        $this->assertFalse($client->exists('doc:category:533'));
        $this->assertFalse($client->exists('mto:article-category:502:canonical_category'));
        $this->assertFalse(in_array('502', $client->smembers('otm:category-article:533:articles')));

        // Ref no longer needed:
        $this->assertFalse(
            in_array(
                'Bravo3\Orm\Tests\Entities\Refs\Article:502:canonical_category',
                $client->smembers('ref:category:533')
            )
        );
    }
}
