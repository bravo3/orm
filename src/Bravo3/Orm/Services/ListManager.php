<?php
namespace Bravo3\Orm\Services;

use Doctrine\Common\Inflector\Inflector;

/**
 * This is a helper class that will add and remove entities from a list and call the getter and setter functions while
 * doing so. This is important for entity hydration and change detection. Not calling the getter/setter functions when
 * adding and removing from a list may yield unexpected results when persisting.
 */
class ListManager
{
    /**
     * Add an item to a list
     *
     * @param object $entity
     * @param string $property Property name to add to, not needed if providing both a getter and setter
     * @param object $value    Value to add
     * @param string $getter   Custom getter name
     * @param string $setter   Custom setter name
     */
    public static function add($entity, $property, $value, $getter = null, $setter = null)
    {
        list($getter, $setter) = self::resolveFunctionNames($property, $getter, $setter);

        $arr = $entity->$getter();
        if (!$arr) {
            $arr = [];
        }

        $arr[] = $value;
        $entity->$setter($arr);
    }

    /**
     * Remove an item from a list
     *
     * @param object $entity
     * @param string $property Property name to remove from, not needed if providing both a getter and setter
     * @param object $value    Value to remove
     * @param array  $fns      Array of getter functions which must have equal value to consider a match
     * @param string $getter   Custom getter name
     * @param string $setter   Custom setter name
     */
    public static function remove($entity, $property, $value, array $fns, $getter = null, $setter = null)
    {
        list($getter, $setter) = self::resolveFunctionNames($property, $getter, $setter);

        $arr = $entity->$getter();
        if (!$arr) {
            return;
        }

        $local_values = [];
        foreach ($fns as $fn_index => $fn) {
            $local_values[$fn_index] = $value->$fn();
        }

        foreach ($arr as $item_index => $item) {
            foreach ($fns as $fn_index => $fn) {
                if ($local_values[$fn_index] !== $item->$fn()) {
                    continue 2;
                }
            }

            unset($arr[$item_index]);
            $entity->$setter(array_values($arr));
        }
    }

    /**
     * Resolve default setter/getter names
     *
     * @param string $property
     * @param string $getter
     * @param string $setter
     * @return array
     */
    protected static function resolveFunctionNames($property, $getter = null, $setter = null)
    {
        if (!$getter) {
            $getter = 'get'.Inflector::classify($property);
        }

        if (!$setter) {
            $setter = 'set'.Inflector::classify($property);
        }

        return [$getter, $setter];
    }
}
