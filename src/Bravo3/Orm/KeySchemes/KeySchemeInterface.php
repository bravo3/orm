<?php
namespace Bravo3\Orm\KeySchemes;

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
}
