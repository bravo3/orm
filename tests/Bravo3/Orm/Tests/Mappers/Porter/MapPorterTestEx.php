<?php
namespace Bravo3\Orm\Tests\Mappers\Porter;

use Bravo3\Orm\Mappers\Portation\MapWriterInterface;
use Bravo3\Orm\Mappers\Yaml\YamlMapWriter;
use Bravo3\Orm\Services\EntityLocator;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;

/**
 * This test will not actually run any PHPUnit tests, but rather generate metadata for each map porter. These outputs
 * will be tested by additional entity managers to ensure the output passes every normal test.
 */
class MapPorterTestEx extends AbstractOrmTest
{
    public function testPort()
    {
        $em = $this->entityManagerDataProvider()[0][0];

        $locator  = new EntityLocator($this->entityManagerDataProvider()[0][0]);
        $entities = $locator->locateEntities(__DIR__.'/../../Entities', 'Bravo3\Orm\Tests\Entities');

        foreach ($this->getWriters() as $porter) {
            $porter->setInputManager($em);

            // Should NOT be in output
            $porter->compileMetadataForEntity(Category::class);
            $porter->purge();

            // Should be in output
            foreach ($entities as $class_name) {
                $porter->compileMetadataForEntity($class_name);
            }
            $porter->flush();
        }
    }

    /**
     * @return MapWriterInterface[]
     */
    private function getWriters()
    {
        return [new YamlMapWriter(__DIR__.'/../../Resources/mappings.yml')];
    }
}
