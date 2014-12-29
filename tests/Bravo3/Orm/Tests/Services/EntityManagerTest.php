<?php
namespace Bravo3\Orm\Tests;

use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Tests\Entities\BadEntity;
use Bravo3\Orm\Tests\Entities\OneToOne\Address;
use Bravo3\Orm\Tests\Entities\OneToOne\User;
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

    public function testOneToOne()
    {
        $user = new User();
        $user->setId(100)->setName('Clem');

        $address = new Address();
        $address->setId(500)->setStreet('Fandango St');

        $user->setAddress($address);

        $em = $this->getEntityManager();
        $em->persist($address)->persist($user)->flush();

        /** @var User|OrmProxyInterface $r_user */
        $r_user = $em->retrieve('Bravo3\Orm\Tests\Entities\OneToOne\User', 100);
        $this->assertFalse($r_user->isProxyInitialized());
        $this->assertEquals(100, $r_user->getId());
        $this->assertEquals('Clem', $r_user->getName());

        // Because each relationship is independently lazy-loaded, the proxy is never considered "initialised"
        $this->assertFalse($r_user->isProxyInitialized());

        // Should make DB query here
        $r_address = $r_user->getAddress();
        $this->assertTrue($r_address instanceof Address);
        $this->assertTrue($r_address instanceof OrmProxyInterface);

        $this->assertEquals(500, $r_address->getId());
        $this->assertEquals('Fandango St', $r_address->getStreet());
    }

    /**
     * By setting the relationship on the user only, then persisting the address last, the second persist call will
     * clear the relationship
     */
    public function testOneToOneRaceFail()
    {
        $user = new User();
        $user->setId(101)->setName('Steven');

        $address = new Address();
        $address->setId(501)->setStreet('Toast St');

        $user->setAddress($address);

        $em = $this->getEntityManager();
        $em->persist($user)->flush()->persist($address)->flush();

        /** @var User|OrmProxyInterface $r_user */
        $r_user = $em->retrieve('Bravo3\Orm\Tests\Entities\OneToOne\User', 101);
        $this->assertEquals('Steven', $r_user->getName());

        // Should make DB query here
        $r_address = $r_user->getAddress();
        $this->assertNull($r_address);
    }

    /**
     * As above, but when we persist both the user and the address at the same time, the address side of the
     * relationship is new and not re-written
     */
    public function testOneToOneRaceSuccess()
    {
        $user = new User();
        $user->setId(102)->setName('Ray');

        $address = new Address();
        $address->setId(502)->setStreet('Purchase St');

        $user->setAddress($address);

        $em = $this->getEntityManager();
        $em->persist($user)->persist($address)->flush();

        /** @var User|OrmProxyInterface $r_user */
        $r_user = $em->retrieve('Bravo3\Orm\Tests\Entities\OneToOne\User', 102);
        $this->assertEquals('Ray', $r_user->getName());

        // Should make DB query here
        $r_address = $r_user->getAddress();

        // Should still retrieve, since we persisted user & address at the same time, there was no change to the
        // address FK
        $this->assertTrue($r_address instanceof Address);
        $this->assertTrue($r_address instanceof OrmProxyInterface);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        $driver = $this->getDriver();
        $mapper = new AnnotationMapper();
        return new EntityManager($driver, $mapper);
    }

    protected function getDriver()
    {
        return new RedisDriver(['host' => 'localhost', 'database' => 2]);
    }
}
