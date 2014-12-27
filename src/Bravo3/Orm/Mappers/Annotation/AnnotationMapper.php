<?php
namespace Bravo3\Orm\Mappers\Annotation;

use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class AnnotationMapper implements MapperInterface
{
    /**
     * @var AnnotationReader
     */
    protected $annotion_reader;

    /**
     * @var object
     */
    protected $entity;

    /**
     * @var \ReflectionClass
     */
    protected $reflection_obj;

    public function __construct($paths = [])
    {
        if (!$paths) {
            $paths[] = ['Bravo3\Orm\Annotations', __DIR__.'/../../../../'];
        }

        foreach ($paths as $path) {
            AnnotationRegistry::registerAutoloadNamespace($path[0], $path[1]);
        }
    }

    /**
     * Get the metadata for an entity, including column information
     *
     * @param string $entity Class name of the entity
     * @return Entity
     */
    public function getEntityMetadata($entity)
    {
        $parser = new AnnotationMetadataParser($entity);
        return $parser->getEntityMetdata();
    }
}
