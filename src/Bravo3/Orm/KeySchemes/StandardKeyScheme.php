<?php
namespace Bravo3\Orm\KeySchemes;

/**
 * Stores all keys with a configurable section delimiter which defaults to a colon (:), ideal for databases that do not
 * have any concept of folders
 */
class StandardKeyScheme implements KeySchemeInterface
{
    const DEFAULT_DELIMITER = ':';
    const ENTITY_NAMESPACE  = 'entity';

    /**
     * @var string
     */
    protected $delimiter;

    function __construct($delimiter = null)
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
     * @param string $table_name
     * @param string $id
     * @return string
     */
    public function getEntityKey($table_name, $id)
    {
        return static::ENTITY_NAMESPACE.':'.$table_name.':'.$id;
    }
}
