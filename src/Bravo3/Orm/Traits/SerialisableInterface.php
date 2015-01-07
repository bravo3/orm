<?php
namespace Bravo3\Orm\Traits;

interface SerialisableInterface
{
    /**
     * Serialise self and return a string representation
     *
     * @return string
     */
    public function serialise();

    /**
     * Create a new instance of self from the given string representation
     *
     * @param string $value
     * @return $this
     */
    public static function deserialise($value);
}
