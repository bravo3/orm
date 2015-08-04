<?php
namespace Bravo3\Orm\Tests;

use Bravo3\Orm\Drivers\Common\Ref;
use Bravo3\Orm\Enum\RelationshipType;
use Bravo3\Orm\Query\SortedQuery;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Tests\Entities\Indexed\SluggedArticle;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;
use Bravo3\Orm\Tests\Entities\Refs\Article as RefArticle;
use Bravo3\Orm\Tests\Entities\Refs\Category as RefCategory;
use Bravo3\Orm\Tests\Entities\Refs\Leaf;
use Bravo3\Orm\Tests\Entities\Refs\Owner;

class RefTest extends AbstractOrmTest
{
    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testRefs(EntityManager $em)
    {
        $members = $em->getDriver()->getMultiValueIndex($em->getKeyScheme()->getEntityRefKey('leaf', 'leaf1'));
        $this->assertCount(0, $members);

        $leaf  = (new Leaf())->setId('leaf1');
        $owner = (new Owner())->setId('owner1')->setLeaf([$leaf]);

        $em->persist($leaf)->persist($owner)->flush();

        $members = $em->getDriver()->getMultiValueIndex($em->getKeyScheme()->getEntityRefKey('leaf', 'leaf1'));

        $this->assertCount(1, $members);
        $ref = new Ref(Owner::class, 'owner1', 'leaf');
        $this->assertEquals((string)$ref, $members[0]);

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

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testDeleteRelationships(EntityManager $em)
    {
        $article1 = new Article();
        $article1->setId(301)->setTitle('Article 301');

        $article2 = new Article();
        $article2->setId(302)->setTitle('Article 302');

        $category1 = new Category();
        $category1->setId(351)->setName('Category 351');

        $category1->addArticle($article1)->addArticle($article2);

        $em->persist($category1)->persist($article1)->persist($article2)->flush();

        $this->assertTrue($this->exists($em, 'article', '301'));
        $key = $this->getRelKey($em, 'article', 'category', '301', 'canonical_category', RelationshipType::MANYTOONE());
        $this->assertEquals('351', $em->getDriver()->getSingleValueIndex($key));

        /** @var Article $article */
        $article = $em->retrieve(Article::class, 301);
        $article->setId(399);

        $em->delete($article)->flush();

        $this->assertFalse($this->exists($em, 'article', '301'));
        $this->assertNull($em->getDriver()->retrieve($key));
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testDeleteIndices(EntityManager $em)
    {
        $article = new SluggedArticle();
        $article->setId(401)->setName('slugged article')->setSlug('some-slug');

        $em->persist($article)->flush();
        $this->assertTrue($this->exists($em, 'slugged_article', '401'));
        $this->assertEquals(
            '401',
            $em->getDriver()->getSingleValueIndex($this->getIndexKey($em, 'slugged_article', 'slug', 'some-slug'))
        );
        $this->assertEquals(
            '401',
            $em->getDriver()->getSingleValueIndex($this->getIndexKey($em, 'slugged_article', 'name', 'slugged article'))
        );

        $em->delete($article)->flush();
        $this->assertFalse($this->exists($em, 'slugged_article', '401'));

        $key = $this->getIndexKey($em, 'slugged_article', 'slug', 'some-slug');
        $this->assertNull($em->getDriver()->getSingleValueIndex($key));

        $key = $this->getIndexKey($em, 'slugged_article', 'name', 'slugged article');
        $this->assertNull($em->getDriver()->getSingleValueIndex($key));
    }

    /**
     * Use ref's to delete non-inversed relationships
     *
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testDeleteIndicesAndRelationships(EntityManager $em)
    {
        $article1 = new RefArticle();
        $article1->setId(501)->setTitle('Ref Article 501');

        $category1 = new RefCategory();
        $category1->setId(532)->setName('Ref Category 532');

        $article1->setCanonicalCategory($category1);

        $em->persist($category1)->persist($article1)->flush();

        $this->assertTrue($this->exists($em, 'article', '501'));
        $key = $this->getRelKey($em, 'article', 'category', '501', 'canonical_category', RelationshipType::MANYTOONE());
        $this->assertEquals('532', $em->getDriver()->getSingleValueIndex($key));

        // Not inversed:
        $ikey = $this->getRelKey($em, 'category', 'article', '532', 'articles', RelationshipType::ONETOMANY());
        $this->assertFalse(in_array('501', $em->getDriver()->getMultiValueIndex($ikey)));

        // Ref exists:
        $refs = $em->getDriver()->getRefs($this->getEntityRefKey($em, 'category', '532'));
        $ref  = new Ref(RefArticle::class, '501', 'canonical_category');
        $this->assertContains($ref, $refs);

        /** @var RefArticle $article */
        $article = $em->retrieve(RefArticle::class, 501);
        $em->delete($article)->flush();

        $this->assertFalse($this->exists($em, 'article', '501'));
        $mto = $this->getRelKey($em, 'article', 'category', '501', 'canonical_category', RelationshipType::MANYTOONE());
        $otm = $this->getRelKey($em, 'category', 'article', '532', 'articles', RelationshipType::ONETOMANY());
        $this->assertNull($em->getDriver()->getSingleValueIndex($mto));
        $this->assertNotContains('501', $em->getDriver()->getMultiValueIndex($otm));

        // Ref no longer needed:
        $refs = $em->getDriver()->getRefs($this->getEntityRefKey($em, 'category', '532'));
        $this->assertNotContains($ref, $refs);
    }

    /**
     * Same as above test, except we'll delete the category
     *
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testDeleteIndicesAndRelationshipsAlt(EntityManager $em)
    {
        $article1 = new RefArticle();
        $article1->setId(502)->setTitle('Ref Article 502');

        $category1 = new RefCategory();
        $category1->setId(533)->setName('Ref Category 533');

        $article1->setCanonicalCategory($category1);

        $em->persist($category1)->persist($article1)->flush();

        $this->assertTrue($this->exists($em, 'article', '502'));
        $mto = $this->getRelKey($em, 'article', 'category', '502', 'canonical_category', RelationshipType::MANYTOONE());
        $this->assertEquals('533', $em->getDriver()->getSingleValueIndex($mto));

        // Not inversed:
        $otm = $this->getRelKey($em, 'category', 'article', '533', 'articles', RelationshipType::ONETOMANY());
        $this->assertNotContains('502', $em->getDriver()->getMultiValueIndex($otm));


        // Ref exists:
        $refs = $em->getDriver()->getRefs($this->getEntityRefKey($em, 'category', '534'));
        $ref  = new Ref(RefArticle::class, '502', 'canonical_category');
        $this->assertContains($ref, $refs);

        /** @var RefCategory $category */
        $category = $em->retrieve(RefCategory::class, 533);
        $em->delete($category)->flush();

        $this->assertFalse($this->exists($em, 'category', '533'));
        $this->assertNull($em->getDriver()->getSingleValueIndex($mto));
        $this->assertNotContains('502', $em->getDriver()->getMultiValueIndex($otm));

        // Ref no longer needed:
        $refs = $em->getDriver()->getRefs($this->getEntityRefKey($em, 'category', '533'));
        $this->assertNotContains($ref, $refs);
    }
}
