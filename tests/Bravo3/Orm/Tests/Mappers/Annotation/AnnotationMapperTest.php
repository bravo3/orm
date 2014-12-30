<?php
namespace Bravo3\Orm\Tests\Mappers\Annotation;

use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Services\Io\Reader;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\BadEntity;
use Bravo3\Orm\Tests\Entities\Product;

class AnnotationMapperTest extends AbstractOrmTest
{
    public function testStuff()
    {
        $mapper = new AnnotationMapper();

        $product      = new Product();
        $product_meta = $mapper->getEntityMetadata(Reader::getEntityClassName($product));
        $this->assertEquals("products", $product_meta->getTableName());

        $bad      = new BadEntity();
        $bad_meta = $mapper->getEntityMetadata(Reader::getEntityClassName($bad));
        $this->assertEquals("bad_entity", $bad_meta->getTableName());
    }
}
