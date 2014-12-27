<?php
namespace Bravo3\Orm\Tests\Mappers\Annotation;

use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Tests\Entities\Article;
use Bravo3\Orm\Tests\Entities\BadEntity;
use Bravo3\Orm\Tests\Entities\Product;
use Bravo3\Orm\Tests\Entities\UserGroup;

class AnnotationMapperTest extends \PHPUnit_Framework_TestCase
{
    public function testStuff()
    {
        $mapper = new AnnotationMapper();

        $product      = new Product();
        $product_meta = $mapper->getEntityMetadata($product);
        $this->assertEquals("products", $product_meta->getTableName());

        $bad  = new BadEntity();
        $bad_meta = $mapper->getEntityMetadata($bad);
        $this->assertEquals("bad_entity", $bad_meta->getTableName());
    }
}
