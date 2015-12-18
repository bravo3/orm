<?php
namespace Bravo3\ClassTools\Builder\Meta;

class Method
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $visibility;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var bool
     */
    protected $static;

    /**
     * @param string      $name
     * @param int         $visibility
     * @param string|null $type
     * @param bool        $static
     */
    public function __construct(
        string $name,
        int $visibility = Visibility::PUBLIC,
        string $type = '',
        bool $static = false
    ) {
        $this->name       = $name;
        $this->visibility = $visibility;
        $this->type       = $type;
        $this->static     = $static;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get Visibility
     *
     * @return int
     */
    public function getVisibility(): int
    {
        return $this->visibility;
    }

    /**
     * Set Visibility
     *
     * @param int $visibility
     * @return $this
     */
    public function setVisibility(int $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * Get Type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set Type
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get Static
     *
     * @return boolean
     */
    public function getStatic(): bool
    {
        return $this->static;
    }

    /**
     * Set Static
     *
     * @param boolean $static
     * @return $this
     */
    public function setStatic(bool $static): self
    {
        $this->static = $static;
        return $this;
    }
}
