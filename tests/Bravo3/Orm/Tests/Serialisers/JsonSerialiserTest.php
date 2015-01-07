<?php
namespace Bravo3\Orm\Tests\Serialisers;

use Bravo3\Orm\Serialisers\JsonSerialiser;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Product;

class JsonSerialiserTest extends AbstractOrmTest
{
    const DATE_FORMAT = 'Y-m-d H:i:s';

    public function testComplexSerialisation()
    {
        $em         = $this->getEntityManager();
        $serialiser = new JsonSerialiser();
        $time       = new \DateTime();

        $product = new Product();
        $product->setCreateTime($time);

        $metadata = $em->getMapper()->getEntityMetadata($product);

        $data = $serialiser->serialise($metadata, $product);

        $new_product = new Product();
        $serialiser->deserialise($metadata, $data, $new_product);

        $this->assertEquals($time->format(self::DATE_FORMAT), $new_product->getCreateTime()->format(self::DATE_FORMAT));
    }
}
