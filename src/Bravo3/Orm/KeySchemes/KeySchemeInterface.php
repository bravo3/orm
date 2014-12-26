<?php
namespace Bravo3\Orm\KeySchemes;

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
}
