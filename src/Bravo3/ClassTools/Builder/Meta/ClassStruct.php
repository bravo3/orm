<?php
namespace Bravo3\ClassTools\Builder\Meta;

class ClassStruct
{
    /**
     * @var string
     */
    protected $class_name;

    /**
     * @var string
     */
    protected $extends;

    /**
     * @var string[]
     */
    protected $implements;

    /**
     * @var string[]
     */
    protected $traits;

    /**
     * @var Property[]
     */
    protected $properties;

    /**
     * @var Method[]
     */
    protected $methods;

    /**
     * @var int
     */
    protected $class_type = ClassType::STANDARD;

    /**
     * @var bool
     */
    protected $abstract = false;

    /**
     * @var bool
     */
    protected $final = false;

    /**
     * @param string    $class_name
     * @param string    $extends
     * @param \string[] $implements
     */
    public function __construct(string $class_name, string $extends = '', array $implements = [])
    {
        $this->class_name = $class_name;
        $this->extends    = $extends;
        $this->implements = $implements;
    }

    /**
     * Get ClassName
     *
     * @return string
     */
    public function getClassName(): string
    {
        return $this->class_name;
    }

    /**
     * Set ClassName
     *
     * @param string $class_name
     * @return $this
     */
    public function setClassName(string $class_name): self
    {
        $this->class_name = $class_name;
        return $this;
    }

    /**
     * Get Extends
     *
     * @return string
     */
    public function getExtends(): self
    {
        return $this->extends;
    }

    /**
     * Set Extends
     *
     * @param string $extends
     * @return $this
     */
    public function setExtends(string $extends): self
    {
        $this->extends = $extends;
        return $this;
    }

    /**
     * Get Implements
     *
     * @return string[]
     */
    public function getImplements(): array
    {
        return $this->implements;
    }

    /**
     * Set Implements
     *
     * @param string[] $implements
     * @return $this
     */
    public function setImplements(array $implements): self
    {
        $this->implements = $implements;
        return $this;
    }

    /**
     * Get Traits
     *
     * @return string[]
     */
    public function getTraits(): array
    {
        return $this->traits;
    }

    /**
     * Set Traits
     *
     * @param string[] $traits
     * @return $this
     */
    public function setTraits(array $traits): self
    {
        $this->traits = $traits;
        return $this;
    }

    /**
     * Get Properties
     *
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Set Properties
     *
     * @param Property[] $properties
     * @return $this
     */
    public function setProperties(array $properties): self
    {
        $this->properties = [];
        foreach ($properties as $property) {
            $this->addProperty($property);
        }
        return $this;
    }

    /**
     * Get Methods
     *
     * @return Method[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Set Methods
     *
     * @param Method[] $methods
     * @return $this
     */
    public function setMethods(array $methods): self
    {
        $this->methods = [];
        foreach ($methods as $method) {
            $this->addMethod($method);
        }
        return $this;
    }

    /**
     * Add a property, overriding any existing property with the same name
     *
     * @param Property $property
     * @return $this
     */
    public function addProperty(Property $property): self
    {
        $this->properties[$property->getName()] = $property;
        return $this;
    }

    /**
     * Add a method, overriding any existing method with the same name
     *
     * @param Method $method
     * @return ClassStruct
     */
    public function addMethod(Method $method): self
    {
        $this->methods[$method->getName()] = $method;
        return $this;
    }

    /**
     * Get ClassType
     *
     * @return int
     */
    public function getClassType(): int
    {
        return $this->class_type;
    }

    /**
     * Set ClassType
     *
     * @param int $class_type
     * @return $this
     */
    public function setClassType(int $class_type): self
    {
        $this->class_type = $class_type;
        return $this;
    }

    /**
     * Get Abstract
     *
     * @return boolean
     */
    public function isAbstract(): bool
    {
        return $this->abstract;
    }

    /**
     * Set Abstract
     *
     * @param boolean $abstract
     * @return $this
     */
    public function setAbstract(bool $abstract): self
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * Get Final
     *
     * @return boolean
     */
    public function getFinal(): bool
    {
        return $this->final;
    }

    /**
     * Set Final
     *
     * @param boolean $final
     * @return $this
     */
    public function setFinal(bool $final): self
    {
        $this->final = $final;
        return $this;
    }
}
