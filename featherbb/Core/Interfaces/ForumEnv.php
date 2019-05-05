<?php
namespace FeatherBB\Core\Interfaces;

class ForumEnv extends SlimSugar
{
    public static function get($key)
    {
        if (isset(static::$slim->getContainer()['forum_env'][$key])) {
            return static::$slim->getContainer()['forum_env'][$key];
        }
        return false;
    }

    public static function set($key, $value)
    {
        return static::$slim->getContainer()['forum_env'][$key] = $value;
    }
}
