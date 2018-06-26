<?php
namespace Gorankrgovic\Larablog;


use InvalidArgumentException;
use Illuminate\Support\Facades\Config;

/**
 * Class LarablogHelper
 *
 * @package Propeller
 */
class LarablogHelper
{
    /**
     * Gets the it from an array, object, string or integer.
     *
     * @param $object
     * @param $type
     * @return mixed|null
     */
    public static function getIdFor($object, $type)
    {
        if ( is_null($object) )
        {
            return null;
        } elseif (is_object($object)) {
            return $object->getKey();
        } elseif (is_array($object)) {
            return $object['id'];
        } elseif (is_numeric($object)) {
            return $object;
        } elseif (is_string($object)) {
            return call_user_func_array([
                Config::get("larablog.models.{$type}"), 'where'
            ], ['name', $object])->firstOrFail()->getKey();
        }

        throw new InvalidArgumentException(
            'getIdFor function only accepts an integer, a Model object or an array with an "id" key'
        );
    }



    /**
     * @param $relationship
     * @return bool
     */
    public static function isValidRelationship($relationship)
    {
        return in_array($relationship, [
            'categories',
            'users'
        ]);
    }
}