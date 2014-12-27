<?php
namespace Bravo3\Orm\Tests\Mappers\Annotation;

use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Tests\Entities\Article;
use Bravo3\Orm\Tests\Entities\BadEntity;
use Bravo3\Orm\Tests\Entities\NotAnEntity;

class AnnotationMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testPrimitives()
    {
        $mapper = new AnnotationMapper();

        $article      = new Article();
        $article_meta = $mapper->getEntityMetadata($article);
        $this->assertEquals("articles", $article_meta->getTableName());
    }

    /**
     * @expectedException \Bravo3\Orm\Exceptions\InvalidEntityException
     */
    public function testBadEntity()
    {
        $mapper = new AnnotationMapper();
        $entity = new BadEntity();
        $mapper->getEntityMetadata($entity);
    }

    /**
     * @expectedException \Bravo3\Orm\Exceptions\InvalidEntityException
     */
    public function testNotAnEntity()
    {
        $mapper = new AnnotationMapper();
        $entity = new NotAnEntity();
        $mapper->getEntityMetadata($entity);
    }
}
