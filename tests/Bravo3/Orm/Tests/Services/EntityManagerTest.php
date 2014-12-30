<?php
namespace Bravo3\Orm\Tests;

use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\Enum\Event;
use Bravo3\Orm\Events\PersistEvent;
use Bravo3\Orm\Events\RetrieveEvent;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Tests\Entities\BadEntity;
use Bravo3\Orm\Tests\Entities\ModifiedEntity;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\Product;

class EntityManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testIo()
    {
        $em = $this->getEntityManager();

        $create_time = new \DateTime("2015-01-01 12:15:03+0000");

        $product = new Product();
        $product->setId(123)->setName('Test Product')->setDescription("lorem ipsum")->setActive(true)
                ->setCreateTime($create_time)->setPrice(12.45);

        $em->persist($product);
        $em->flush();

        /** @var Product|OrmProxyInterface $retrieved */
        $retrieved = $em->retrieve('Bravo3\Orm\Tests\Entities\Product', 123);
        $this->validateProxyInterface($retrieved);

        $this->assertEquals($product->getId(), $retrieved->getId());
        $this->assertEquals($product->getName(), $retrieved->getName());
        $this->assertEquals($product->getDescription(), $retrieved->getDescription());
        $this->assertEquals('01/01/2015 12:15:03', $retrieved->getCreateTime()->format('d/m/Y H:i:s'));
        $this->assertSame(12.45, $retrieved->getPrice());
        $this->assertTrue($retrieved->getActive());
    }

    public function testIntercepts()
    {
        $em = $this->getEntityManager();

        $em->getDispatcher()->addListener(
            Event::PRE_RETRIEVE,
            function (RetrieveEvent $event) {
                // Return something completely different instead
                $event->setReturnValue(new Article());
                $event->setAbort(true);
            }
        );

        $em->getDispatcher()->addListener(
            Event::POST_PERSIST,
            function (PersistEvent $event) {
                /** @var Product $entity */
                $entity = $event->getEntity();
                $this->assertTrue($entity instanceof Product);
                $entity->setName('Persisted Product');
            }
        );

        $product = new Product();
        $product->setId(111)->setName('New Product')->setCreateTime(new \DateTime());

        $em->persist($product);
        $em->flush();

        $this->assertEquals('Persisted Product', $product->getName());

        $retrieved = $em->retrieve('Bravo3\Orm\Tests\Entities\Product', 111);
        $this->assertTrue($retrieved instanceof Article);
    }

    public function testCreateModified()
    {
        $em = $this->getEntityManager();

        $entity = new ModifiedEntity();
        $entity->setId(111)->setName('Create/Modify Entity');

        $this->assertNull($entity->getLastModified());
        $this->assertNull($entity->getTimeCreated());

        $em->persist($entity);

        $this->assertTrue($entity->getLastModified() instanceof \DateTime);
        $this->assertTrue($entity->getTimeCreated() instanceof \DateTime);
        $this->assertEquals(date('Y-m-d'), $entity->getLastModified()->format('Y-m-d'));
        $this->assertEquals(date('Y-m-d'), $entity->getTimeCreated()->format('Y-m-d'));
    }

    private function validateProxyInterface(OrmProxyInterface $proxy)
    {
        $this->assertFalse($proxy->isRelativeModified('foo'));
        $proxy->setRelativeModified('foo');
        $this->assertTrue($proxy->isRelativeModified('foo'));
    }

    /**
     * @expectedException \Bravo3\Orm\Exceptions\InvalidEntityException
     */
    public function testBadEntity()
    {
        $em         = $this->getEntityManager();
        $bad_entity = new BadEntity();
        $em->persist($bad_entity);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        $driver = $this->getDriver();
        $mapper = new AnnotationMapper();
        return EntityManager::build($driver, $mapper);
    }

    protected function getDriver()
    {
        return new RedisDriver(['host' => 'localhost', 'database' => 2]);
    }
}
