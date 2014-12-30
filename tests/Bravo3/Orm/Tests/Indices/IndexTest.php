<?php
namespace Bravo3\Orm\Tests\Indices;

use Bravo3\Orm\Exceptions\NotFoundException;
use Bravo3\Orm\Services\Io\Reader;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\Indexed\IndexedEntity;

class IndexTest extends AbstractOrmTest
{
    const INDEXED_ENTITY = 'Bravo3\Orm\Tests\Entities\Indexed\IndexedEntity';

    public function testIndex()
    {
        $em = $this->getEntityManager();

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
        $this->assertContains('charlie', $bc->getColumns());
        $this->assertCount(2, $bc->getColumns());
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
}
