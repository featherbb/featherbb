<?php
namespace FeatherBB\Core\Interfaces;

class Config extends SlimSugar
{
    public static function get($key)
    {
        return static::$slim->getContainer()['settings'][$key];
    }

    public static function set($key, $value)
    {
        return static::$slim->getContainer()['settings'][$key] = $value;
    }
}
