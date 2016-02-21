<?php
namespace FeatherBB\Core\Interfaces;

class Container extends SlimSugar
{
    public static function get($key)
	{
        if (isset(static::$slim->getContainer()[$key])) {
            return static::$slim->getContainer()[$key];
        }
		return false;
	}

	public static function set($key, $value)
	{
		return static::$slim->getContainer()[$key] = $value;
	}
}
