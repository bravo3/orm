<?php
namespace Bravo3\Orm\KeySchemes;

use Bravo3\Orm\Mappers\Metadata\Index;
use Bravo3\Orm\Mappers\Metadata\Relationship;

interface KeySchemeInterface
{
    /**
     * Return the key for an entity document
     *
     * @param string $table_name
     * @param string $id
     * @return string
     */
    public function getEntityKey($table_name, $id);

    /**
     * Get the key for a foreign relationship
     *
     * @param Relationship $relationship Relationship
     * @param string       $id           Source entity ID
     * @return string
     */
    public function getRelationshipKey(Relationship $relationship, $id);

    /**
     * Get the key for an standard index
     *
     * @param Index  $index Index belonging to entity
     * @param string $key   Index key
     * @return string
     */
    public function getIndexKey(Index $index, $key);

    /**
     * Get the key for a sort index on a relationship
     *
     * @param Relationship $relationship Relationship
     * @param string       $sort_key     Property name on the inverse entity
     * @param string       $id           Local ID
     * @return string
     */
    public function getSortIndexKey(Relationship $relationship, $sort_key, $id);
}
