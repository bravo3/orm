<?php
namespace Bravo3\Orm\Tests\Relationships;

use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;

class OneToManyTest extends \PHPUnit_Framework_TestCase
{
    public function testOneToMany()
    {
        $article1 = new Article();
        $article1->setId(101)->setTitle('Article 101');

        $article2 = new Article();
        $article2->setId(102)->setTitle('Article 102');

        $category1 = new Category();
        $category1->setId(101)->setName('Category 101');

        $category1->addArticle($article1)->addArticle($article2);

        $em = $this->getEntityManager();
        $em->persist($category1)->persist($article1)->persist($article2)->flush();
    }

    /**
     * Testing race conditions of new entities, with a flush after persisting the first entity
     * @see: docs/RaceConditions.md
     */
    public function testOneToManyRaceFlush()
    {
        $article1 = new Article();
        $article1->setId(201)->setTitle('Article 201');

        $category1 = new Category();
        $category1->setId(201)->setName('Category 201');

        $category1->addArticle($article1);

        $em = $this->getEntityManager();
        $em->persist($category1)->flush()->persist($article1)->flush();

        /** @var Article|OrmProxyInterface $r_article */
        $r_article = $em->retrieve('Bravo3\Orm\Tests\Entities\OneToMany\Article', 201);
        $this->assertEquals('Article 201', $r_article->getTitle());

        // Should make DB query here
        $r_category = $r_article->getCanonicalCategory();
        //$this->assertNull($r_category);
        $this->assertTrue($r_category instanceof Category);

        // Check inverse side too -
        /** @var Category|OrmProxyInterface $ir_category */
        $ir_category = $em->retrieve('Bravo3\Orm\Tests\Entities\OneToMany\Category', 201);
        $this->assertEquals('Category 201', $ir_category->getName());

        // Should make DB query here
        $ir_articles = $ir_category->getArticles();
        //$this->assertCount(0, $ir_articles);
        $this->assertCount(1, $ir_articles);
        $ir_article = $ir_articles[0];
        $this->assertTrue($ir_article instanceof Article);
    }

    /**
     * Testing race conditions of new entities, without a flush between persist calls
     * @see: docs/RaceConditions.md
     */
    public function testOneToManyRaceNoFlush()
    {
        $article1 = new Article();
        $article1->setId(201)->setTitle('Article 201');

        $category1 = new Category();
        $category1->setId(201)->setName('Category 201');

        $category1->addArticle($article1);

        $em = $this->getEntityManager();
        $em->persist($category1)->persist($article1)->flush();

        /** @var Article|OrmProxyInterface $r_article */
        $r_article = $em->retrieve('Bravo3\Orm\Tests\Entities\OneToMany\Article', 201);
        $this->assertEquals('Article 201', $r_article->getTitle());

        // Should make DB query here
        $r_category = $r_article->getCanonicalCategory();
        $this->assertTrue($r_category instanceof Category);
        //$this->assertNull($r_category);

        // Check inverse side too -
        /** @var Category|OrmProxyInterface $ir_category */
        $ir_category = $em->retrieve('Bravo3\Orm\Tests\Entities\OneToMany\Category', 201);
        $this->assertEquals('Category 201', $ir_category->getName());

        // Should make DB query here
        $ir_articles = $ir_category->getArticles();
        //$this->assertCount(0, $ir_articles);
        $this->assertCount(1, $ir_articles);
        $ir_article = $ir_articles[0];
        $this->assertTrue($ir_article instanceof Article);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        $driver = $this->getDriver();
        $mapper = new AnnotationMapper();
        return new EntityManager($driver, $mapper);
    }

    protected function getDriver()
    {
        return new RedisDriver(['host' => 'localhost', 'database' => 2]);
    }
}
