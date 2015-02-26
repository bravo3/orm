<?php
namespace Bravo3\Orm\Drivers\Common;

use Bravo3\Orm\Exceptions\UnexpectedValueException;

/**
 * The Ref class represents a reference to an entity, from another entity's uni-directional relationship
 */
class Ref
{
    const REF_DELIMITER = ':';

    /**
     * @var string
     */
    protected $source_class;

    /**
     * @var string
     */
    protected $entity_id;

    /**
     * @var string
     */
    protected $relationship_name;

    /**
     * Create an inverse reference to an entity
     *
     * @param string $source_class
     * @param string $entity_id
     * @param string $relationship_name
     */
    public function __construct($source_class, $entity_id, $relationship_name)
    {
        $this->source_class      = $source_class;
        $this->entity_id         = $entity_id;
        $this->relationship_name = $relationship_name;
    }

    /**
     * Create a ref from a string interpretation
     *
     * @param string $ref
     * @return Ref
     */
    public static function fromString($ref)
    {
        $parts = explode(self::REF_DELIMITER, $ref, 3);
        if (count($parts) !== 3) {
            throw new UnexpectedValueException("Invalid ref string: ".$ref);
        }

        return new self($parts[0], $parts[1], $parts[2]);
    }

    /**
     * Get SourceClass
     *
     * @return string
     */
    public function getSourceClass()
    {
        return $this->source_class;
    }

    /**
     * Set SourceClass
     *
     * @param string $source_class
     * @return $this
     */
    public function setSourceClass($source_class)
    {
        $this->source_class = $source_class;
        return $this;
    }

    /**
     * Get RelationshipName
     *
     * @return string
     */
    public function getRelationshipName()
    {
        return $this->relationship_name;
    }

    /**
     * Set RelationshipName
     *
     * @param string $relationship_name
     * @return $this
     */
    public function setRelationshipName($relationship_name)
    {
        $this->relationship_name = $relationship_name;
        return $this;
    }

    /**
     * Get EntityId
     *
     * @return string
     */
    public function getEntityId()
    {
        return $this->entity_id;
    }

    /**
     * Set EntityId
     *
     * @param string $entity_id
     * @return $this
     */
    public function setEntityId($entity_id)
    {
        $this->entity_id = $entity_id;
        return $this;
    }

    /**
     * Get the ref in string form
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getSourceClass().self::REF_DELIMITER.
               $this->getEntityId().self::REF_DELIMITER.
               $this->getRelationshipName();
    }
}
