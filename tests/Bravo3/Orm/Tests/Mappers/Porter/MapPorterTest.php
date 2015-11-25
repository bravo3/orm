<?php
namespace Bravo3\Orm\Tests\Mappers\Porter;

use Bravo3\Orm\Mappers\Portation\MapWriterInterface;
use Bravo3\Orm\Mappers\Yaml\YamlMapWriter;
use Bravo3\Orm\Tests\AbstractOrmTest;
use Bravo3\Orm\Tests\Entities\OneToMany\Article;
use Bravo3\Orm\Tests\Entities\OneToMany\Category;
use Bravo3\Orm\Tests\Entities\SortedUser;

/**
 * This test will not actually run any PHPUnit tests, but rather generate metadata for each map porter. These outputs
 * will be tested by additional entity managers to ensure the output passes every normal test.
 */
class MapPorterTest extends AbstractOrmTest
{
    public function testPort()
    {
        $em = $this->entityManagerDataProvider()[0][0];

        foreach ($this->getWriters() as $porter) {
            $porter->setInputManager($em);

            // Should NOT be in output
            $porter->compileMetadataForEntity(Category::class);
            $porter->purge();

            // Should be in output
            $porter->compileMetadataForEntity(Article::class);
            $porter->compileMetadataForEntity(SortedUser::class);
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
