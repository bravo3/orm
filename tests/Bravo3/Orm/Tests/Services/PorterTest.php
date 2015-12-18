<?php
namespace Bravo3\Orm\Tests\Services;

use Bravo3\Orm\Enum\Direction;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Query\KeyScan;
use Bravo3\Orm\Query\SortedQuery;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Services\Porter;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Porter\Article;
use Bravo3\Orm\Tests\Entities\Porter\Category;

class PorterTest extends AbstractOrmTest
{
    public function testPortation()
    {
        $mapper = new AnnotationMapper();
        $src    = EntityManager::build($this->getRedisDriver(), $mapper);
        $dest   = EntityManager::build($this->getFsDriver('porter-db'), $mapper);

        $porter = new Porter();
        $porter->registerManager('redis', $src);
        $porter->registerManager('tar', $dest);

        // Create some dummy data
        $category = new Category();
        $category->setId(600);
        $src->persist($category);

        for ($i = 0; $i < 15; $i++) {
            $article = new Article();
            $article->setId(601 + $i);
            $article->setTitle('Art '.(601 + $i));
            $time = new \DateTime();
            $time->modify('+'.($i + 1).' minutes');
            $article->setSortDate($time);
            $article->setCanonicalCategory($category);
            $src->persist($article);
        }
        $src->flush();

        // Port the database
        $porter->portTable(Article::class, 'redis', 'tar');
        $porter->portTable(Category::class, 'redis', 'tar');

        // Check entity counts
        $articles = $dest->keyScan(new KeyScan(Article::class, ['@id' => '*']), false);
        $this->assertCount(15, $articles);

        $categories = $dest->keyScan(new KeyScan(Category::class, ['@id' => '*']), false);
        $this->assertCount(1, $categories);

        // Test indices were preserved
        /** @var Article $article */
        // Date sorting -
        $results = $dest->sortedQuery(new SortedQuery($category, 'articles', 'sort_date'));
        $this->assertCount(15, $results);
        $article = $results[0];
        $this->assertEquals('Art 601', $article->getTitle());

        $results = $dest->sortedQuery(new SortedQuery($category, 'articles', 'sort_date', Direction::DESC()));
        $this->assertCount(15, $results);
        $this->assertEquals(15, $results->getFullSize());
        $article = $results[0];
        $this->assertEquals('Art 615', $article->getTitle());

        $results = $dest->sortedQuery(
            new SortedQuery($category, 'articles', 'sort_date', Direction::DESC(), 5, -6),
            true
        );
        $this->assertCount(5, $results);
        $this->assertEquals(15, $results->getFullSize());
        $article = $results[0];
        $this->assertEquals('Art 610', $article->getTitle());
        $article = $results[4];
        $this->assertEquals('Art 606', $article->getTitle());

        $results = $dest->sortedQuery(new SortedQuery($category, 'articles', 'sort_date', Direction::ASC(), 2, 5));
        $this->assertCount(4, $results);
        $this->assertNull($results->getFullSize());
        $article = $results[0];
        $this->assertEquals('Art 603', $article->getTitle());

        $results = $dest->sortedQuery(new SortedQuery($category, 'articles', 'sort_date', Direction::ASC(), 20, 29));
        $this->assertCount(0, $results);

        $results = $dest->sortedQuery(new SortedQuery($category, 'articles', 'title'));
        $this->assertCount(15, $results);
        $article = $results[0];
        $this->assertEquals('Art 601', $article->getTitle());

        // Lexicographic sorting -
        $results = $dest->sortedQuery(new SortedQuery($category, 'articles', 'title'));
        $this->assertCount(15, $results);
        $article = $results[0];
        $this->assertEquals('Art 601', $article->getTitle());

        $results = $dest->sortedQuery(new SortedQuery($category, 'articles', 'title', Direction::DESC()));
        $this->assertCount(15, $results);
        $article = $results[0];
        $this->assertEquals('Art 615', $article->getTitle());
    }
}
