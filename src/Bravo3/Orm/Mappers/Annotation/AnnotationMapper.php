<?php
namespace Bravo3\Orm\Mappers\Annotion;

use Bravo3\Orm\Mappers\Annotation\AnnotationMetadata;
use Bravo3\Orm\Services\MapperInterface;
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
            $paths[] = ['Bravo3\Orm\Annotations', __DIR__.'/../../../'];
        }

        foreach ($paths as $path) {
            AnnotationRegistry::registerAutoloadNamespace($path[0], $path[1]);
        }
    }

    /**
     * Get the metadata for an entity
     *
     * @param $entity
     * @return AnnotationMetadata
     */
    public function getEntityMetadata($entity)
    {
        return new AnnotationMetadata($entity);
    }
}
