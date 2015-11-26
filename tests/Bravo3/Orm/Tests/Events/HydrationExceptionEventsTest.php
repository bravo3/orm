<?php
namespace Bravo3\Orm\Tests\Events;

use Bravo3\Orm\Query\SortedQuery;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;
use Prophecy\Argument;

class HydrationExceptionEventsTest extends AbstractOrmTest
{
    /**
     * @xxdataProvider entityManagerDataProvider
     * @param EntityManager $em
     * @expectedException \Bravo3\Orm\Exceptions\CorruptedEntityException
     */
    public function testSortedQueryThrowsNotFoundException()
    {
        // EntityManager $em
        $ems = $this->entityManagerDataProvider();
        array_pop($ems);
        //array_pop($ems);

        /** @var EntityManager $em */
        $em = array_pop($ems)[0];

        $category = (new Category())->setId(5000);

        $article1 = (new Article())->setId(5001)->setTitle('A');
        $article2 = (new Article())->setId(5002)->setTitle('B');
        $article3 = (new Article())->setId(5003)->setTitle('C');

        $category->addArticle($article1);
        $category->addArticle($article2);
        $category->addArticle($article3);

        $em->persist($article1)
           ->persist($article2)
           ->persist($article3)
           ->persist($category)
           ->flush();

        // Forcefully break the relationship via the driver manually
        $em->getDriver()->delete($em->getKeyScheme()->getEntityKey('article', '5001'));
        $em->getDriver()->flush();

        $category = $em->retrieve(Category::class, 5000, false);

//        $md = $em->getMapper()->getEntityMetadata(Category::class);
//        var_dump($md);

        $results = $em->sortedQuery(
            new SortedQuery($category, 'articles', 'sort_date')
        );

        echo "Result size: ".count($results)."\n";

        // Iterating through these results should trigger an exception
        foreach ($results as $result) {
            /** @var Article $result */
            echo $result->getId()." - ".$result->getTitle()."\n";
        }

        echo "foo\n";
        die();
    }
}
