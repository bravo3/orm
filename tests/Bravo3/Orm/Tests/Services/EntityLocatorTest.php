<?php
namespace Bravo3\Orm\Tests\Services;

use Bravo3\Orm\Services\EntityLocator;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\BadEntity;
use Bravo3\Orm\Tests\Entities\Conditional\Category;
use Bravo3\Orm\Tests\Entities\Indexed\IndexedEntity;
use Bravo3\Orm\Tests\Entities\Indexed\SluggedArticle;
use Bravo3\Orm\Tests\Resources\Enum;

class EntityLocatorTest extends AbstractOrmTest
{
    public function testLocation()
    {
        $locator  = new EntityLocator($this->entityManagerDataProvider()[0][0]);
        $entities = $locator->locateEntities(__DIR__.'/../Entities', 'Bravo3\Orm\Tests\Entities');

        $this->assertTrue(count($entities) >= 29);
        $this->assertContains(IndexedEntity::class, $entities);
        $this->assertContains(SluggedArticle::class, $entities);
        $this->assertContains(BadEntity::class, $entities);
        $this->assertContains(Category::class, $entities);
        $this->assertNotContains(Enum::class, $entities);
    }
}
