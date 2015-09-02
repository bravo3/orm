<?php
namespace Bravo3\Orm\Drivers\Common;

trait StandardIdValidatorTrait
{
    /**
     * @var StandardIdValidator
     */
    private $validator = null;

    /**
     * Checks if a given entity ID is safe for the driver and returns an array of violation messages
     *
     * The ID should be considered valid if the returned array is empty.
     *
     * @param string $id
     * @return string[]
     */
    public function validateId($id)
    {
        if ($this->validator === null) {
            $this->validator = new StandardIdValidator();
        }

        return $this->validator->validateId($id);
    }
}
