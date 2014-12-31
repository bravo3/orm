<?php
namespace Bravo3\Orm\Tests\Relationships;

use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;

class OneToManyTest extends AbstractOrmTest
{
    public function testOneToMany()
    {
        $time = new \DateTime();

        $article1 = new Article();
        $article1->setId(101)->setTitle('Article 101')->setTimeCreated($time)->setLastModified($time);

        $article2 = new Article();
        $article2->setId(102)->setTitle('Article 102')->setTimeCreated($time)->setLastModified($time);

        $category1 = new Category();
        $category1->setId(101)->setName('Category 101');

        $category1->addArticle($article1)->addArticle($article2);

        $em = $this->getEntityManager();
        $em->persist($category1)->persist($article1)->persist($article2)->flush();
    }

    /**
     * Testing race conditions of new entities, with a flush after persisting the first entity
     *
     * @see: docs/RaceConditions.md
     */
    public function testOneToManyRaceFlush()
    {
        $time = new \DateTime();

        $article1 = new Article();
        $article1->setId(201)->setTitle('Article 201')->setTimeCreated($time)->setLastModified($time);

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
        $this->assertTrue($r_category instanceof Category);

        // Check inverse side too -
        /** @var Category|OrmProxyInterface $ir_category */
        $ir_category = $em->retrieve('Bravo3\Orm\Tests\Entities\OneToMany\Category', 201);
        $this->assertEquals('Category 201', $ir_category->getName());

        // Should make DB query here
        $ir_articles = $ir_category->getArticles();
        $this->assertCount(1, $ir_articles);
        $ir_article = $ir_articles[0];
        $this->assertTrue($ir_article instanceof Article);
    }

    /**
     * Testing race conditions of new entities, without a flush between persist calls
     *
     * @see: docs/RaceConditions.md
     */
    public function testOneToManyRaceNoFlush()
    {
        $time = new \DateTime();

        $article1 = new Article();
        $article1->setId(201)->setTitle('Article 201')->setTimeCreated($time)->setLastModified($time);

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

        // Check inverse side too -
        /** @var Category|OrmProxyInterface $ir_category */
        $ir_category = $em->retrieve('Bravo3\Orm\Tests\Entities\OneToMany\Category', 201);
        $this->assertEquals('Category 201', $ir_category->getName());

        // Should make DB query here
        $ir_articles = $ir_category->getArticles();
        $this->assertCount(1, $ir_articles);
        $ir_article = $ir_articles[0];
        $this->assertTrue($ir_article instanceof Article);
    }
}
