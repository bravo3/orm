<?php
namespace Bravo3\Orm\Tests\Relationships;

use Bravo3\Orm\Drivers\Redis\RedisDriver;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Tests\Entities\OneToOne\Address;
use Bravo3\Orm\Tests\Entities\OneToOne\User;

class OneToOneTest extends \PHPUnit_Framework_TestCase
{
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
     * Testing race conditions of new entities, with a flush after persisting the first entity
     * @see: docs/RaceConditions.md
     */
    public function testOneToOneRaceFlush()
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
        //$this->assertNull($r_address);
        $this->assertTrue($r_address instanceof Address);
        $this->assertTrue($r_address instanceof OrmProxyInterface);
    }

    /**
     * Testing race conditions of new entities, without a flush between persist calls
     * @see: docs/RaceConditions.md
     */
    public function testOneToOneRaceNoFlush()
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
        //$this->assertNull($r_address);
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
