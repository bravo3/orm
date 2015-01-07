<?php
namespace Bravo3\Orm\Enum;

use Bravo3\Orm\Traits\SerialisableInterface;
use Eloquent\Enumeration\AbstractEnumeration;

abstract class OrmEnum extends AbstractEnumeration implements SerialisableInterface
{
    /**
     * Serialise self and return a string representation
     *
     * @return string
     */
    public function serialise()
    {
        return $this->key();
    }

    /**
     * Create a new instance of self from the given string representation
     *
     * @param string $value
     * @return $this
     */
    public static function deserialise($value)
    {
        return call_user_func(get_called_class().'::'.$value);
    }
}
