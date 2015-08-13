<?php
namespace Bravo3\Orm\Tests\Mappers\Annotation;

use Bravo3\Orm\Enum\RelationshipType;
use Bravo3\Orm\Mappers\Annotation\AnnotationMapper;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\NotAnEntity;
use Bravo3\Orm\Tests\Entities\OneToOne\Address;
use Bravo3\Orm\Tests\Entities\OneToOne\User;
use Bravo3\Orm\Tests\Entities\Product;
use Bravo3\Orm\Tests\Entities\VeryBadEntity;

class AnnotationMetadataTest extends AbstractOrmTest
{
    public function testPrimitives()
    {
        $mapper = new AnnotationMapper();

        $article      = new Product();
        $article_meta = $mapper->getEntityMetadata($article);
        $this->assertEquals("products", $article_meta->getTableName());

        $id_cols = $article_meta->getIdColumns();
        $this->assertCount(1, $id_cols);

        $id_column = $id_cols[0];
        $this->assertEquals('id', $id_column->getProperty());
        $this->assertEquals('id', $id_column->getName());
        $this->assertEquals('getId', $id_column->getGetter());
        $this->assertEquals('setId', $id_column->getSetter());
    }

    public function testOtoRelationship()
    {
        $mapper = new AnnotationMapper();

        $user      = new User();
        $user_meta = $mapper->getEntityMetadata($user);

        $relationships = $user_meta->getRelationships();
        $this->assertCount(1, $relationships);

        $address_relationship = $user_meta->getRelationshipByName('address');
        $this->assertEquals(User::class, $address_relationship->getSource());
        $this->assertEquals(Address::class, $address_relationship->getTarget());
        $this->assertEquals('users', $address_relationship->getSourceTable());
        $this->assertEquals('address', $address_relationship->getTargetTable());
        $this->assertEquals('user', $address_relationship->getInversedBy());
        $this->assertEquals(RelationshipType::ONETOONE(), $address_relationship->getRelationshipType());
    }

    /**
     * @expectedException \Bravo3\Orm\Exceptions\InvalidEntityException
     */
    public function testNotAnEntity()
    {
        $mapper = new AnnotationMapper();
        $entity = new NotAnEntity();
        $mapper->getEntityMetadata($entity);
    }

    /**
     * @expectedException \Bravo3\Orm\Exceptions\InvalidEntityException
     */
    public function testIllegalEntity()
    {
        $mapper = new AnnotationMapper();
        $entity = new VeryBadEntity();
        $mapper->getEntityMetadata($entity);
    }
}
