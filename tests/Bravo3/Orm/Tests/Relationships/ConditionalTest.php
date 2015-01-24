<?php
namespace Bravo3\Orm\Tests\Relationships;

use Bravo3\Orm\Query\SortedQuery;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Conditional\Article;
use Bravo3\Orm\Tests\Entities\Conditional\Category;

class ConditionalTest extends AbstractOrmTest
{
    public function testForwardConditionalRelationship()
    {
        $em = $this->getEntityManager();

        $category = new Category();
        $category->setId(1000)->setName('Conditional Category');

        $em->persist($category)->flush();

        for ($i = 45; $i < 56; $i++)
        {
            $article = new Article();
            $article->setId($i)->setTitle('Conditional Article #'.$i);
            if ($i == 53) {
                $article->setPublished(false);
            } else {
                $article->setPublished(true);
            }

            $category->addArticle($article);
            $em->persist($article);
        }

        $em->persist($category)->flush();

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'last_modified'));
        $this->assertCount(4, $articles);

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'id'));
        $this->assertCount(11, $articles);

        // Update an entity to make it fail the condition -
        $article = $em->retrieve('Bravo3\Orm\Tests\Entities\Conditional\Article', 54);
        $article->setPublished(false);
        $em->persist($article)->flush();

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'last_modified'));
        $this->assertCount(3, $articles);
    }

    public function testReverseConditionalRelationship()
    {
        $em = $this->getEntityManager();

        $category = new Category();
        $category->setId(2000)->setName('Conditional Category');

        $em->persist($category)->flush();

        for ($i = 65; $i < 76; $i++)
        {
            $article = new Article();
            $article->setId($i)->setTitle('Conditional Article #'.$i);
            if ($i == 73) {
                $article->setPublished(false);
            } else {
                $article->setPublished(true);
            }

            $article->setCategory($category);
            $em->persist($article);
        }

        $em->flush();

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'last_modified'));
        $this->assertCount(10, $articles);

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'id'));
        $this->assertCount(11, $articles);
    }
}
