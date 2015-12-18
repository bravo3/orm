<?php
namespace Bravo3\Orm\Drivers\Common;

/**
 * Generic entity ID validator that checks for alpha-numeric IDs
 */
class StandardIdValidator
{
    /**
     * @var string[]
     */
    protected $allowed_id_chars;

    /**
     * @param string[] $allowed_id_chars
     */
    public function __construct(array $allowed_id_chars = ['-', '+', '@', '.', ',', '_', '~', '/', '\\'])
    {
        $this->allowed_id_chars = $allowed_id_chars;
    }

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
        if ($id === null || strlen($id) == 0) {
            $errors[] = 'You cannot have a blank or null ID';
        }

        $errors = [];
        $id     = str_replace($this->allowed_id_chars, '', $id);

        if ($id && !ctype_alnum($id)) {
            $errors[] = 'ID must contain only alpha-numeric characters and '.implode($this->allowed_id_chars, ', ');
        }

        return $errors;
    }
}
