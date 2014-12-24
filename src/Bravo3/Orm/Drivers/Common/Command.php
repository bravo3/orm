<?php
namespace Bravo3\Orm\Drivers\Common;

class Command
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string[]
     */
    protected $arguments;

    public function __construct($name, array $arguments = [])
    {
        $this->name      = $name;
        $this->arguments = $arguments;
    }

    /**
     * Get command name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get command arguments
     *
     * @return string[]
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
