<?php
namespace Bravo3\Orm\KeySchemes;

use Bravo3\Orm\Mappers\Metadata\Relationship;

/**
 * Stores all keys with a configurable section delimiter which defaults to a colon (:)
 */
class StandardKeyScheme implements KeySchemeInterface
{
    const DEFAULT_DELIMITER = ':';
    const ENTITY_NAMESPACE  = 'doc';

    /**
     * @var string
     */
    protected $delimiter;

    public function __construct($delimiter = null)
    {
        $this->delimiter = $delimiter ?: static::DEFAULT_DELIMITER;
    }

    /**
     * Get the section delimiter
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * Set the section delimiter
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
     * Return the key for an entity document
     *
     * @param string $table_name Table name
     * @param string $id         Entity ID
     * @return string
     */
    public function getEntityKey($table_name, $id)
    {
        // doc:article:54624
        return static::ENTITY_NAMESPACE.$this->delimiter.$table_name.$this->delimiter.$id;
    }

    /**
     * Get the key for a foreign relationship
     *
     * @param Relationship $relationship Relationship
     * @param string       $id           Source entity ID
     * @return string
     */
    public function getRelationshipKey(Relationship $relationship, $id)
    {
        // otm:user-address:89726:home_address
        return (string)$relationship->getRelationshipType()->value().$this->delimiter.
               $relationship->getSource().'-'.$relationship->getTarget().$this->delimiter.
               $id.$this->delimiter.$relationship->getName();
    }
}
