<?php
namespace Bravo3\Orm\Exceptions;

class InvalidIdException extends InvalidArgumentException
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string[]
     */
    protected $violations;

    /**
     * @param string    $id
     * @param \string[] $violations
     */
    public function __construct($id, array $violations)
    {
        $this->id         = $id;
        $this->violations = $violations;
        parent::__construct("Entity ID '".$id."' is invalid");
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get Violations
     *
     * @return \string[]
     */
    public function getViolations()
    {
        return $this->violations;
    }
}
