<?php
namespace Bravo3\Orm\Tests\Indices;

use Bravo3\Orm\Mappers\Metadata\Index;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Indexed\SluggedArticle;
use Bravo3\Orm\Tests\Entities\Indexed\SluggedArticleNewIndex;

class NewIndexTest extends AbstractOrmTest
{
    const INDEXED_ENTITY = 'Bravo3\Orm\Tests\Entities\Indexed\IndexedEntity';

    /**
     * @dataProvider entityManagerDataProvider
     *
     * @param EntityManager $em
     */
    public function testIndex(EntityManager $em)
    {
        $article = new SluggedArticle();
        $article->setId(1)->setName('Article Name')->setSlug('article-name');

        /*$article_metadata = $em->getMapper()->getEntityMetadata($article);
        $article_indices = $article_metadata->getIndices();
        print_r($article_indices);*/

        $em->persist($article)->flush();

        $article_new_index = new SluggedArticleNewIndex();
        $article_new_index->setId(1)->setName('Article Name')->setSlug('article-name')->setCategory('category');

        /*$article_new_index_metadata = $em->getMapper()->getEntityMetadata($article_new_index);
        $article_new_index_indices = $article_new_index_metadata->getIndices();
        print_r($article_new_index_indices);die;*/

        $em->persist($article_new_index)->flush();

        $em->retrieveByIndex(SluggedArticle::class, 'slug', 'article-name');

        $em->retrieveByIndex(SluggedArticleNewIndex::class, 'category__slug', 'category.article-name');
    }

    /**
     * @dataProvider entityManagerDataProvider
     *
     * @param EntityManager $em
     */
    public function testSameEntityIndex(EntityManager $em)
    {
        $article = new SluggedArticle();
        $article->setId(1)->setName('Article Name')->setSlug('article-name')->setCategory('category');

        $em->persist($article)->flush();

        $em->retrieveByIndex(SluggedArticle::class, 'slug', 'article-name');

        //print_r($article_metadata->getIndices());

        /* $driver = $em->getDriver();

        $driver->setSingleValueIndex($em->getKeyScheme()->getIndexKey
            (new Index('slugged_article', 'slug'), 'article-name'),
            'foo'
        );

        $driver->flush();*/

        $slug_index = new Index('slugged_article', 'category__slug');
        $slug_index->addColumn('category');
        $slug_index->addColumn('slug');

        $name_index = new Index('slugged_article', 'category__name');
        $name_index->addColumn('category');
        $name_index->addColumn('name');

        $article_metadata = $em->getMapper()->getEntityMetadata($article);
        $article_metadata->setIndices([$slug_index, $name_index]);

        //print_r($article_metadata->getIndices());die;

        $em->persist($article)->flush();


        $em->retrieveByIndex(SluggedArticle::class, 'category__slug', 'category.article-name', false);
    }
}
