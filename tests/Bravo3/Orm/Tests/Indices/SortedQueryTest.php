<?php
namespace Bravo3\Orm\Tests\Indices;

use Bravo3\Orm\Enum\Direction;
use Bravo3\Orm\Query\SortedQuery;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;

class SortedQueryTest extends AbstractOrmTest
{
    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testPersist(EntityManager $em)
    {
        // Persist a forward relationship
        $time1 = new \DateTime();
        $time1->modify('-1 Hour');

        $time2 = new \DateTime();

        $article1 = new Article();
        $article1->setId(201)->setTitle('Article 201')->setTimeCreated($time1)->setLastModified($time1);

        $article2 = new Article();
        $article2->setId(202)->setTitle('Article 202')->setTimeCreated($time2)->setLastModified($time2);

        $category1 = new Category();
        $category1->setId(1000)->setName('Category 1000');

        $category2 = new Category();
        $category2->setId(1001)->setName('Category 1001');

        $category3 = new Category();
        $category3->setId(1003)->setName('Category 1003');

        $category1->addArticle($article1)->addArticle($article2);

        $em->persist($category1)
           ->persist($category2)
           ->persist($category3)
           ->persist($article1)
           ->persist($article2)
           ->flush();

        // Update the category's article list by removing an entity on the inverse side
        /** @var Article $article */
        $article = $em->retrieve(Article::class, 201);
        $article->setCanonicalCategory($category2);
        $em->persist($article)->flush();

        // Break the relationship - this should force the $category1 -> $article1 relationship to be indirectly broken
        $category3->addArticle($article2);
        $em->persist($category3)->flush();
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testSortOrder(EntityManager $em)
    {
        $category = new Category();
        $category->setId(600);
        $em->persist($category);

        for ($i = 0; $i < 15; $i++) {
            $article = new Article();
            $article->setId(601 + $i);
            $article->setTitle('Art '.(601 + $i));
            $time = new \DateTime();
            $time->modify('+'.($i + 1).' minutes');
            $article->setSortDate($time);
            $article->setCanonicalCategory($category);
            $em->persist($article);
        }
        $em->flush();

        /** @var Article $article */
        // Date sorting -
        $results = $em->sortedQuery(new SortedQuery($category, 'articles', 'sort_date'));
        $this->assertCount(15, $results);
        $article = $results[0];
        $this->assertEquals('Art 601', $article->getTitle());

        $results = $em->sortedQuery(new SortedQuery($category, 'articles', 'sort_date', Direction::DESC()));
        $this->assertCount(15, $results);
        $this->assertEquals(15, $results->getFullSize());
        $article = $results[0];
        $this->assertEquals('Art 615', $article->getTitle());

        $results = $em->sortedQuery(
            new SortedQuery($category, 'articles', 'sort_date', Direction::DESC(), 5, -6),
            true
        );
        $this->assertCount(5, $results);
        $this->assertEquals(15, $results->getFullSize());
        $article = $results[0];
        $this->assertEquals('Art 610', $article->getTitle());

        $results = $em->sortedQuery(new SortedQuery($category, 'articles', 'sort_date', Direction::ASC(), 2, 5));
        $this->assertCount(4, $results);
        $this->assertNull($results->getFullSize());
        $article = $results[0];
        $this->assertEquals('Art 603', $article->getTitle());

        $results = $em->sortedQuery(new SortedQuery($category, 'articles', 'sort_date', Direction::ASC(), 20, 29));
        $this->assertCount(0, $results);

        $results = $em->sortedQuery(new SortedQuery($category, 'articles', 'title'));
        $this->assertCount(15, $results);
        $article = $results[0];
        $this->assertEquals('Art 601', $article->getTitle());

        // Lexicographic sorting -
        $results = $em->sortedQuery(new SortedQuery($category, 'articles', 'title'));
        $this->assertCount(15, $results);
        $article = $results[0];
        $this->assertEquals('Art 601', $article->getTitle());

        $results = $em->sortedQuery(new SortedQuery($category, 'articles', 'title', Direction::DESC()));
        $this->assertCount(15, $results);
        $article = $results[0];
        $this->assertEquals('Art 615', $article->getTitle());

        // Modify an entity's sort-by column
        $article = $em->retrieve(Article::class, 609);
        $time    = $article->getSortDate();
        $time->modify('+1 day');
        $article->setSortDate($time);
        $em->persist($article)->flush();

        $results = $em->sortedQuery(new SortedQuery($category, 'articles', 'sort_date', Direction::DESC()));
        $this->assertCount(15, $results);
        $article = $results[0];
        $this->assertEquals('Art 609', $article->getTitle());
    }
}
