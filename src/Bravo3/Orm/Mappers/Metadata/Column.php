<?php
namespace Bravo3\Orm\Mappers\Metadata;

use Bravo3\Orm\Enum\FieldType;
use Doctrine\Common\Inflector\Inflector;

class Column
{
    /**
     * @var string
     */
    protected $property;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var FieldType
     */
    protected $type;

    /**
     * @var string
     */
    protected $classname;

    /**
     * @var string
     */
    protected $getter;

    /**
     * @var string
     */
    protected $setter;

    /**
     * @var bool
     */
    protected $is_id = false;

    public function __construct($property)
    {
        $this->property = $property;
        $this->type     = FieldType::STRING();
    }

    /**
     * Get class property name
     *
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Set class property name
     *
     * @param string $property
     * @return $this
     */
    public function setProperty($property)
    {
        $this->property = $property;
        return $this;
    }

    /**
     * Get database field name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name ?: Inflector::tableize($this->property);
    }

    /**
     * Set database field name
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
     * Get field type
     *
     * @return FieldType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set field type
     *
     * @param FieldType $type
     * @return $this
     */
    public function setType(FieldType $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the related class name
     *
     * @return string
     */
    public function getClassname()
    {
        return $this->classname;
    }

    /**
     * Set the related class name, doing so will force the field type to ENTITY
     *
     * @param string $classname
     * @return $this
     */
    public function setClassname($classname)
    {
        $this->classname = $classname;
        $this->type      = FieldType::ENTITY();
        return $this;
    }

    /**
     * Get Getter
     *
     * @return string
     */
    public function getGetter()
    {
        return $this->getter ?: 'get'.Inflector::classify($this->getProperty());
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
        return $this->setter ?: 'set'.Inflector::classify($this->getProperty());
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
     * Check if the column is an ID column
     *
     * @return boolean
     */
    public function isId()
    {
        return $this->is_id;
    }

    /**
     * Define if the column is an ID column
     *
     * @param boolean $is_id
     * @return $this
     */
    public function setId($is_id)
    {
        $this->is_id = (bool)$is_id;
        return $this;
    }
}
