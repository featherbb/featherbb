<?php
namespace FeatherBB\Core\Interfaces;

class ForumSettings extends SlimSugar
{
    public static function get($key)
	{
		return static::$slim->getContainer()['forum_settings'][$key];
	}

	public static function toArray()
	{
		return static::$slim->getContainer()['forum_settings'];
	}

	public static function set($key, $value)
	{
		return static::$slim->getContainer()['forum_settings'][$key] = $value;
	}
}
