<?php
namespace Bravo3\Orm\Tests\Services;

use Bravo3\Orm\Services\Cache\EntityCachingInterface;
use Bravo3\Orm\Services\Cache\EphemeralEntityCache;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Product;

class CachingStrategyTest extends AbstractOrmTest
{
    /**
     * @dataProvider getCacheServices
     * @param EntityCachingInterface $service
     */
    public function testStrategy(EntityCachingInterface $service)
    {
        $product = new Product();
        $product->setId(1234);

        $this->assertFalse($service->exists(Product::class, 1234));
        $service->store(Product::class, '1234', $product);
        $this->assertTrue($service->exists(Product::class, 1234));
        $r = $service->retrieve(Product::class, '1234');
        $service->purge(Product::class, '1234');
        $this->assertFalse($service->exists(Product::class, 1234));

        $this->assertEquals(1234, $r->getId());
    }

    /**
     * @dataProvider getCacheServices
     * @expectedException \Bravo3\Orm\Exceptions\NotFoundException
     * @param EntityCachingInterface $service
     */
    public function testStrategyException(EntityCachingInterface $service)
    {
        $service->retrieve(Product::class, '12345');
    }

    /**
     * Get an array of caching services
     *
     * @return array
     */
    public function getCacheServices()
    {
        return [
            [new EphemeralEntityCache()]
        ];
    }
}
