<?php
namespace Bravo3\Orm\Mappers\Metadata;

use Bravo3\Orm\Enum\RelationshipType;
use Doctrine\Common\Inflector\Inflector;

class Relationship
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var RelationshipType
     */
    protected $relationship_type;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var string
     */
    protected $target;

    /**
     * @var string
     */
    protected $inversed_by;

    /**
     * @var string
     */
    protected $getter;

    /**
     * @var string
     */
    protected $setter;

    /**
     * @var Sortable[]
     */
    protected $sortable_by = [];

    /**
     * @param string           $name
     * @param RelationshipType $relationship_type
     */
    public function __construct($name, RelationshipType $relationship_type)
    {
        $this->name              = $name;
        $this->relationship_type = $relationship_type;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get relationship type
     *
     * @return RelationshipType
     */
    public function getRelationshipType()
    {
        return $this->relationship_type;
    }

    /**
     * Set relationship type
     *
     * @param RelationshipType $relationship_type
     * @return $this
     */
    public function setRelationshipType(RelationshipType $relationship_type)
    {
        $this->relationship_type = $relationship_type;
        return $this;
    }

    /**
     * Get source class name
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set source class name
     *
     * @param string $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Get target class name
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Set target class name
     *
     * @param string $target
     * @return $this
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Get inversed by field name
     *
     * @return string
     */
    public function getInversedBy()
    {
        return $this->inversed_by;
    }

    /**
     * Set inversed by field name
     *
     * @param string $inversed_by
     * @return $this
     */
    public function setInversedBy($inversed_by)
    {
        $this->inversed_by = $inversed_by;
        return $this;
    }

    /**
     * Get Getter
     *
     * @return string
     */
    public function getGetter()
    {
        return $this->getter ?: 'get' . Inflector::classify($this->getName());
    }

    /**
     * Set Getter
     *
     * @param string $getter
     * @return $this
     */
    public function setGetter($getter)
    {
        $this->getter = $getter;
        return $this;
    }

    /**
     * Get Setter
     *
     * @return string
     */
    public function getSetter()
    {
        return $this->setter ?: 'set' . Inflector::classify($this->getName());
    }

    /**
     * Set Setter
     *
     * @param string $setter
     * @return $this
     */
    public function setSetter($setter)
    {
        $this->setter = $setter;
        return $this;
    }

    /**
     * Get list of relative properties that this relationship can be sorted by
     *
     * @return Sortable[]
     */
    public function getSortableBy()
    {
        return $this->sortable_by;
    }

    /**
     * Set list of relative properties that this relationship can be sorted by
     *
     * @param Sortable[] $sortable_by
     * @return $this
     */
    public function setSortableBy(array $sortable_by)
    {
        $this->sortable_by = $sortable_by;
        return $this;
    }

    /**
     * Add a property to be sortable by
     *
     * @param Sortable $property_name
     * @return $this
     */
    public function addSortableBy(Sortable $property_name)
    {
        $this->sortable_by[] = $property_name;
        return $this;
    }
}
