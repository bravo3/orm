<?php
namespace Bravo3\Orm\Tests\Indices;

use Bravo3\Orm\Query\SortedTableQuery;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\SortedUser;

class SortedTableTest extends AbstractOrmTest
{
    public function testTableSorting()
    {
        $em = $this->getEntityManager();

        $user1 = new SortedUser();
        $user1->setId(1)->setName('User 1')->setActive(true);

        $user2 = new SortedUser();
        $user2->setId(2)->setName('User 2')->setActive(false);

        $user3 = new SortedUser();
        $user3->setId(3)->setName('User 3')->setActive(true);

        $em->persist($user1)->persist($user2)->persist($user3)->flush();

        $query = $em->sortedQuery(new SortedTableQuery(SortedUser::class, 'name_all'));
        $this->assertEquals(3, $query->count());
        $this->assertEquals('User 1', $query[0]->getName());
        $this->assertEquals('User 2', $query[1]->getName());
        $this->assertEquals('User 3', $query[2]->getName());

        $query = $em->sortedQuery(new SortedTableQuery(SortedUser::class, 'name_active'));
        $this->assertEquals(2, $query->count());
        $this->assertEquals('User 1', $query[0]->getName());
        $this->assertEquals('User 3', $query[1]->getName());
    }
}