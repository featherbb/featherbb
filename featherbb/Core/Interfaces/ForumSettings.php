<?php
namespace FeatherBB\Core\Interfaces;

class ForumSettings extends SlimSugar
{
    public static function get($key)
	{
        if (isset(static::$slim->getContainer()['forum_settings'][$key])) {
            return static::$slim->getContainer()['forum_settings'][$key];
        }
		return false;
	}

	public static function set($key, $value)
	{
		return static::$slim->getContainer()['forum_settings'][$key] = $value;
	}
}
