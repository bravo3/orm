<?php
namespace Bravo3\Orm\Mappers\Annotation;

use Bravo3\Orm\Mappers\MapperInterface;
use Bravo3\Orm\Mappers\Metadata\Entity;
use Bravo3\Orm\Services\Io\Reader;
use Doctrine\Common\Annotations\AnnotationRegistry;

class AnnotationMapper implements MapperInterface
{
    /**
     * @var Entity[]
     */
    protected $metadata_cache = [];

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
        $class_name = is_object($entity) ? Reader::getEntityClassName($entity) : $entity;

        if (!isset($this->metadata_cache[$class_name])) {
            $parser                            = new AnnotationMetadataParser($class_name);
            $this->metadata_cache[$class_name] = $parser->getEntityMetadata();
        }

        return $this->metadata_cache[$class_name];
    }
}
