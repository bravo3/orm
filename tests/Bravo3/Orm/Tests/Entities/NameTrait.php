<?php
namespace Bravo3\Orm\Tests\Entities;

trait NameTrait
{
    /**
     * @var string
     * @Orm\Column(type="string")
     */
    protected $name;

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
}
