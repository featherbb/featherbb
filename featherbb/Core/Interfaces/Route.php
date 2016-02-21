<?php
namespace FeatherBB\Core\Interfaces;

class Route extends SlimSugar
{
    public static function map()
    {
    	return call_user_func_array(array(static::$slim, 'map'), func_get_args());
    }

    public static function get()
    {
    	return call_user_func_array(array(static::$slim, 'get'), func_get_args());
    }

    public static function post()
    {
    	return call_user_func_array(array(static::$slim, 'post'), func_get_args());
    }

    public static function put()
    {
    	return call_user_func_array(array(static::$slim, 'put'), func_get_args());
    }

    public static function patch()
    {
    	return call_user_func_array(array(static::$slim, 'patch'), func_get_args());
    }

    public static function delete()
    {
    	return call_user_func_array(array(static::$slim, 'delete'), func_get_args());
    }

    public static function options()
    {
    	return call_user_func_array(array(static::$slim, 'options'), func_get_args());
    }

    public static function group()
    {
    	return call_user_func_array(array(static::$slim, 'group'), func_get_args());
    }

    public static function any()
    {
    	return call_user_func_array(array(static::$slim, 'any'), func_get_args());
    }
}
