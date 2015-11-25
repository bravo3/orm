<?php
namespace Bravo3\Orm\Mappers\Portation;

use Bravo3\Orm\Services\EntityManager;

interface MapWriterInterface
{
    /**
     * Set the input entity managed used for reading entities
     *
     * @param EntityManager $manager
     * @return void
     */
    public function setInputManager(EntityManager $manager);

    /**
     * Write metadata for a given entity, using the input EntityManager that must be preset
     *
     * @param object      $entity   Entity with existing mapping data
     * @param string|null $resource File name to write data, if null a default or common value will be used
     * @return void
     */
    public function compileMetadataForEntity($entity, $resource = null);

    /**
     * Compile and write all processed metadata
     *
     * @return void
     */
    public function flush();

    /**
     * Purge the current buffer of unwritten content
     *
     * @return void
     */
    public function purge();
}
