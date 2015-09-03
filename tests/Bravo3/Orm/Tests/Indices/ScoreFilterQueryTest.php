<?php
namespace Bravo3\Orm\Tests\Indices;

use Bravo3\Orm\Enum\Direction;
use Bravo3\Orm\Query\ScoreFilterQuery;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;

class ScoreFilterQueryTest extends AbstractOrmTest
{
    public function testScoreFiltering()
    {
        $em = $this->getEntityManager();

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
        $time = new \DateTime();
        $time->modify('+5 minutes');
        $start_time = $time->getTimestamp();

        $time = new \DateTime();
        $time->modify('+10 minutes');
        $end_time = $time->getTimestamp();

        // Check if you only get partial result set filtered by the date range
        $results = $em->scoreFilterQuery(new ScoreFilterQuery($category, 'articles', 'sort_date', Direction::ASC(), $start_time, $end_time));
        $this->assertCount(6, $results);
    }
}
