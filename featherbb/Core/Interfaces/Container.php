<?php
namespace FeatherBB\Core\Interfaces;

class Container extends SlimSugar
{
    public static function get($key)
	{
		return static::$slim->getContainer()[$key];
	}

	public static function set($key, $value)
	{
		return static::$slim->getContainer()[$key] = $value;
	}
}
