<?php
namespace Bravo3\Orm\Tests\Relationships;

use Bravo3\Orm\Query\SortedQuery;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Conditional\Article;
use Bravo3\Orm\Tests\Entities\Conditional\Asset;
use Bravo3\Orm\Tests\Entities\Conditional\Category;

class ConditionalTest extends AbstractOrmTest
{
    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testForwardConditionalRelationship(EntityManager $em)
    {
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

        for ($i = 45; $i < 56; $i++)
        {
            $asset = new Asset();
            $asset->setId($i)->setTitle('Conditional Asset #'.$i);
            if ($i == 53) {
                $asset->setPublished(false);
            } else {
                $asset->setPublished(true);
            }

            $category->addAsset($asset);
            $em->persist($asset);
        }

        $em->persist($category)->flush();

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'last_modified'));
        $this->assertCount(4, $articles);

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'last_modified_all'));
        $this->assertCount(10, $articles);

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'id'));
        $this->assertCount(11, $articles);

        // Update an entity to make it fail the condition -
        $article = $em->retrieve(Article::class, 54);
        $article->setPublished(false);
        $em->persist($article)->flush();

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'last_modified'));
        $this->assertCount(3, $articles);

        /**
         * Assert against Assets
         */
        $assets = $em->sortedQuery(new SortedQuery($category, 'assets', 'last_modified'));
        $this->assertCount(4, $assets);

        $assets = $em->sortedQuery(new SortedQuery($category, 'assets', 'id'));
        $this->assertCount(11, $assets);

        // Update an entity to make it fail the condition -
        $asset = $em->retrieve(Asset::class, 54);
        $asset->setPublished(false);
        $em->persist($asset)->flush();

        $assets = $em->sortedQuery(new SortedQuery($category, 'assets', 'last_modified'));
        $this->assertCount(3, $assets);
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testReverseConditionalRelationship(EntityManager $em)
    {
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

        for ($i = 65; $i < 76; $i++)
        {
            $asset = new Asset();
            $asset->setId($i)->setTitle('Conditional Asset #'.$i);
            if ($i == 73) {
                $asset->setPublished(false);
            } else {
                $asset->setPublished(true);
            }

            $asset->setCategory($category);
            $em->persist($asset);
        }

        $em->flush();

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'last_modified'));
        $this->assertCount(10, $articles);

        $articles = $em->sortedQuery(new SortedQuery($category, 'articles', 'id'));
        $this->assertCount(11, $articles);

        $assets = $em->sortedQuery(new SortedQuery($category, 'assets', 'last_modified'));
        $this->assertCount(10, $assets);

        $assets = $em->sortedQuery(new SortedQuery($category, 'assets', 'id'));
        $this->assertCount(11, $assets);
    }
}
