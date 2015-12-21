<?php
namespace Bravo3\Orm\KeySchemes;

use Bravo3\Orm\Mappers\Metadata\UniqueIndex;
use Bravo3\Orm\Mappers\Metadata\Relationship;

/**
 * Stores all keys with a configurable section delimiter which defaults to a colon (:).
 */
class StandardKeyScheme implements KeySchemeInterface
{
    const DEFAULT_DELIMITER = ':';
    const ENTITY_NAMESPACE  = 'doc';
    const REF_NAMESPACE     = 'ref';
    const INDEX_NAMESPACE   = 'idx';
    const SORT_NAMESPACE    = 'srt';

    /**
     * @var string
     */
    protected $delimiter;

    public function __construct($delimiter = null)
    {
        $this->delimiter = $delimiter ?: static::DEFAULT_DELIMITER;
    }

    /**
     * Get the section delimiter.
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Set the section delimiter.
     *
     * @param string $delimiter
     * @return $this
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * Return the key for an entity document.
     *
     * @param string $table_name Table name
     * @param string $id         Entity ID
     * @return string
     */
    public function getEntityKey($table_name, $id)
    {
        // doc:article:54624
        return static::ENTITY_NAMESPACE.$this->delimiter.
               $table_name.$this->delimiter.
               $id;
    }

    /**
     * Return the key for an entity ref table.
     *
     * @param string $table_name Table name
     * @param string $id         Entity ID
     * @return string
     */
    public function getEntityRefKey($table_name, $id)
    {
        // ref:article:54624
        return static::REF_NAMESPACE.$this->delimiter.
               $table_name.$this->delimiter.
               $id;
    }

    /**
     * Get the key for a foreign relationship.
     *
     * @param Relationship $relationship Relationship
     * @param string       $src_table    Source table name
     * @param string       $target_table Target table name
     * @param string       $id           Source entity ID
     * @return string
     */
    public function getRelationshipKey(Relationship $relationship, $src_table, $target_table, $id)
    {
        // otm:user-address:89726:home_address
        return (string)$relationship->getRelationshipType()->value().$this->delimiter.
               $src_table.'-'.$target_table.$this->delimiter.
               $id.$this->delimiter.
               $relationship->getName();
    }

    /**
     * Get the key for an standard index.
     *
     * @param UniqueIndex $index Index belonging to entity
     * @param string      $key   Index key
     * @return string
     */
    public function getUniqueIndexKey(UniqueIndex $index, $key)
    {
        // idx:article:slug:some-slug
        return static::INDEX_NAMESPACE.$this->delimiter.
               $index->getTableName().$this->delimiter.
               $index->getName().$this->delimiter.
               $key;
    }

    /**
     * Get the key for a sort index on a relationship.
     *
     * @param Relationship $relationship Relationship
     * @param string       $src_table    Source table name
     * @param string       $target_table Target table name
     * @param string       $sort_field   Property name on the inverse entity
     * @param string       $id           Local ID
     * @return string
     */
    public function getSortedRelationshipKey(Relationship $relationship, $src_table, $target_table, $sort_field, $id)
    {
        // srt:category-article:89726:title
        return static::SORT_NAMESPACE.$this->delimiter.
               $src_table.'-'.$target_table.$this->delimiter.
               $id.$this->delimiter.
               $relationship->getName().$this->delimiter.
               $sort_field;
    }

    /**
     * Get the key for a sort index on a table.
     *
     * @param string $table_name Name of table containing sorted entity list
     * @param string $sort_field Sortable field on the table
     * @return string
     */
    public function getSortedTableKey($table_name, $sort_field)
    {
        // srt:category:title
        return static::SORT_NAMESPACE.$this->delimiter.
               $table_name.$this->delimiter.
               $sort_field;
    }
}
