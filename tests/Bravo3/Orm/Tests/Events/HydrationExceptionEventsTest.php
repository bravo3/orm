<?php

namespace Nlm\KidspotBundle\Tests\Service;

use Bravo3\Orm\Query\SortedQuery;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;
use Prophecy\Argument;
use Bravo3\Orm\Enum\Event;
use Bravo3\Orm\Events\HydrationExceptionEvent;

class HydrationExceptionEventsTest extends AbstractOrmTest
{
    /**
     * @expectedException Bravo3\Orm\Exceptions\NotFoundException
     */
    public function testSortedQueryThrowsNotFoundException()
    {
        $em = $this->getEntityManager();

        $category = (new Category())->setId(5000);

        $article1 = (new Article())->setId(5001)->setTitle('A');
        $article2 = (new Article())->setId(5002)->setTitle('B');
        $article3 = (new Article())->setId(5003)->setTitle('C');

        $category->addArticle($article1);
        $category->addArticle($article2);
        $category->addArticle($article3);

        $em
            ->persist($article1)
            ->persist($article2)
            ->persist($article3)
            ->persist($category)
            ->flush();

        /**
         * Forcefully break the relationship within the ORM by manually
         * removing a Recipe entity.
         */
        $this->getRawRedisClient()->del('doc:article:5001');

        $category = $em->retrieve(Category::class, 5000, false);

        $results = $em->sortedQuery(
            new SortedQuery($category, 'articles', 'sort_date')
        );

        /**
         * Iterationg through these results should trigger an exception
         */
        foreach ($results as $result) {}
    }

    public function testSortedQueryCanTriggerHydrationExceptionEvent()
    {
        $em = $this->getEntityManager();
        $em->getConfig()->setHydrationExceptionsAsEvents(true);

        /**
         * Setup our event listener and a result object that we can assert
         * changes state.
         */
        $event_result = new \StdClass;
        $event_result->result = false;
        $em->getDispatcher()->addListener(Event::HYDRATION_EXCEPTION, function(HydrationExceptionEvent $event) use ($event_result) {
            $event_result->result = true;
        });

        $category = (new Category())->setId(5000);

        $article1 = (new Article())->setId(5001)->setTitle('A');
        $article2 = (new Article())->setId(5002)->setTitle('B');
        $article3 = (new Article())->setId(5003)->setTitle('C');

        $category->addArticle($article1);
        $category->addArticle($article2);
        $category->addArticle($article3);

        $em
            ->persist($article1)
            ->persist($article2)
            ->persist($article3)
            ->persist($category)
            ->flush();

        /**
         * Forcefully break the relationship within the ORM by manually
         * removing a Recipe entity.
         */
        $this->getRawRedisClient()->del('doc:article:5002');

        $category = $em->retrieve(Category::class, 5000, false);

        $results = $em->sortedQuery(
            new SortedQuery($category, 'articles', 'sort_date')
        );

        /**
         * A count here still reveals 3 results
         */
        $this->assertCount(3, $results);

        $titles = '';
        foreach ($results as $result) {
            $titles .= $result->getTitle();
        }

        /**
         * Note that 'B' is missing from this result
         */
        $this->assertEquals('AC', $titles);

        /**
         * $event_result::result should now be true
         */
        $this->assertTrue($event_result->result);

        $em->getConfig()->setHydrationExceptionsAsEvents(false);
    }

    /**
     * @expectedException Bravo3\Orm\Exceptions\NotFoundException
     */
    public function testWriterCanThrowNotFoundException()
    {
        $em = $this->getEntityManager();

        $category = (new Category())->setId(5000);

        $article1 = (new Article())->setId(5001)->setTitle('A');
        $article2 = (new Article())->setId(5002)->setTitle('B');
        $article3 = (new Article())->setId(5003)->setTitle('C');

        $category->addArticle($article1);
        $category->addArticle($article2);
        $category->addArticle($article3);

        $em
            ->persist($article1)
            ->persist($article2)
            ->persist($article3)
            ->persist($category)
            ->flush();

        /**
         * Forcefully break the relationship within the ORM by manually
         * removing a Recipe entity.
         */
        $this->getRawRedisClient()->del('doc:article:5002');

        $category = $em->retrieve(Category::class, 5000, false);

        /**
         * A call to getArticles() is enough to trigger the exception
         */
        $results = $category->getArticles();
    }

    public function testWriterCanTriggerHydrationExceptionEvent()
    {
        $em = $this->getEntityManager();
        $em->getConfig()->setHydrationExceptionsAsEvents(true);

        /**
         * Setup our event listener and a result object that we can assert
         * changes state.
         */
        $event_result = new \StdClass;
        $event_result->result = false;
        $em->getDispatcher()->addListener(Event::HYDRATION_EXCEPTION, function(HydrationExceptionEvent $event) use ($event_result) {
            $event_result->result = true;
        });

        $category = (new Category())->setId(5000);

        $article1 = (new Article())->setId(5001)->setTitle('A');
        $article2 = (new Article())->setId(5002)->setTitle('B');
        $article3 = (new Article())->setId(5003)->setTitle('C');

        $category->addArticle($article1);
        $category->addArticle($article2);
        $category->addArticle($article3);

        $em
            ->persist($article1)
            ->persist($article2)
            ->persist($article3)
            ->persist($category)
            ->flush();

        /**
         * Forcefully break the relationship within the ORM by manually
         * removing a Recipe entity.
         */
        $this->getRawRedisClient()->del('doc:article:5002');

        $category = $em->retrieve(Category::class, 5000, false);

        $results = $category->getArticles();

        /**
         * A count here reveals only 2 results
         */
        $this->assertCount(2, $results);

        $titles = '';
        foreach ($results as $result) {
            $titles .= $result->getTitle();
        }

        /**
         * Note that 'B' is missing from this result
         */
        $this->assertEquals('AC', $titles);

        /**
         * $event_result::result should now be true
         */
        $this->assertTrue($event_result->result);

        $em->getConfig()->setHydrationExceptionsAsEvents(false);
    }
}
