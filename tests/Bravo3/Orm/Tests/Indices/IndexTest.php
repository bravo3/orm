<?php
namespace Bravo3\Orm\Tests\Indices;

use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\Proxy\OrmProxyInterface;
use Bravo3\Orm\Services\EntityManager;
use Bravo3\Orm\Services\Io\Reader;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Indexed\IndexedEntity;
use Bravo3\Orm\Tests\Entities\Indexed\SluggedArticle;
use Bravo3\Orm\Tests\Entities\OneToOne\Address;
use Bravo3\Orm\Tests\Entities\OneToOne\User;

class IndexTest extends AbstractOrmTest
{
    const INDEXED_ENTITY = 'Bravo3\Orm\Tests\Entities\Indexed\IndexedEntity';

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testIndex(EntityManager $em)
    {
        $entity = new IndexedEntity();
        $entity->setId1(100)->setId2('id2');
        $entity->setAlpha('alpha')->setBravo('200')->setCharlie(true);

        $metadata = $em->getMapper()->getEntityMetadata($entity);
        $reader   = new Reader($metadata, $entity);

        $this->assertEquals('100.id2', $reader->getId());

        $indices = $metadata->getIndices();
        $this->assertCount(3, $indices);

        $ab = $metadata->getIndexByName('ab');
        $this->assertContains('alpha', $ab->getColumns());
        $this->assertContains('bravo', $ab->getColumns());
        $this->assertCount(2, $ab->getColumns());
        $this->assertEquals('alpha.200', $reader->getIndexValue($ab));

        $bc = $metadata->getIndexByName('bc');
        $this->assertContains('bravo', $bc->getColumns());
        $this->assertContains('getCharlie', $bc->getMethods());
        $this->assertCount(1, $bc->getColumns());
        $this->assertCount(1, $bc->getMethods());
        $this->assertEquals('200.1', $reader->getIndexValue($bc));

        $b = $metadata->getIndexByName('b');
        $this->assertContains('bravo', $b->getColumns());
        $this->assertCount(1, $b->getColumns());
        $this->assertEquals('200', $reader->getIndexValue($b));

        $em->persist($entity)->flush();

        /** @var IndexedEntity $retrieved */
        $retrieved = $em->retrieve(self::INDEXED_ENTITY, '100.id2');
        $retrieved->setAlpha('omega')->setId1(101);
        $em->persist($retrieved)->flush();

        try {
            $em->retrieveByIndex(self::INDEXED_ENTITY, 'ab', 'alpha.200');
            $this->fail("Former index was found");
        } catch (NotFoundException $e) {
        }

        /** @var IndexedEntity $retrieved_by_index */
        $retrieved_by_index = $em->retrieveByIndex(self::INDEXED_ENTITY, 'ab', 'omega.200');
        $this->assertEquals(101, $retrieved_by_index->getId1());
        $this->assertEquals('id2', $retrieved_by_index->getId2());
        $this->assertSame('omega', $retrieved_by_index->getAlpha());
        $this->assertSame(200, $retrieved_by_index->getBravo());
        $this->assertSame(true, $retrieved_by_index->getCharlie());
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testIndexDeletion(EntityManager $em)
    {
        $article = new SluggedArticle();
        $article->setId(94)->setName('Mr Article')->setSlug('mr-article');

        $em->persist($article)->flush();

        /** @var SluggedArticle $mr_article */
        $mr_article = $em->retrieveByIndex(SluggedArticle::class, 'slug', 'mr-article');
        $this->assertEquals('Mr Article', $mr_article->getName());

        $mr_article->setSlug('mrarticle');
        $em->persist($mr_article)->flush();

        // Index should no longer exist
        $this->setExpectedException(NotFoundException::class);
        $em->retrieveByIndex(SluggedArticle::class, 'slug', 'mr-article');
    }

    /**
     * @dataProvider entityManagerDataProvider
     * @param EntityManager $em
     */
    public function testRelatedIndexDeletion(EntityManager $em)
    {
        $home = new Address();
        $home->setId(44)->setStreet('Oxford St');

        $work = new Address();
        $work->setId(45)->setStreet('George St');

        $user = new User();
        $user->setId(23)->setName('Barry')->setAddress($home);

        $em->persist($user)->persist($home)->persist($work)->flush();

        /** @var User|OrmProxyInterface $user_home */
        $user_home = $em->retrieveByIndex(User::class, 'slug', $user->getId().'.'.$home->getId());
        $this->assertEquals(23, $user_home->getId());
        $this->assertEquals('Oxford St', $user_home->getAddress()->getStreet());

        $slug = $user_home->getIndexOriginalValue('slug');
        $this->assertEquals('23.44', $slug);

        $user_home->setName('Other Barry');
        $user_home->setAddress($work);
        $em->persist($user_home)->flush();

        /** @var User $user_work */
        $user_work = $em->retrieveByIndex(User::class, 'slug', $user->getId().'.'.$work->getId());
        $this->assertEquals(23, $user_work->getId());
        $this->assertEquals('George St', $user_work->getAddress()->getStreet());

        $this->setExpectedException(NotFoundException::class);
        $em->retrieveByIndex(User::class, 'slug', $user->getId().'.'.$home->getId());
    }
}
