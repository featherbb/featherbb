<?php
namespace FeatherBB\Core\Interfaces;


class Cache extends SlimSugar
{
    /**
     * Store data in the cache
     *
     * @param string $key
     * @param mixed $data
     * @param integer [optional] $expires
     * @return self
     */
    public static function store($key, $data, $expires = 0)
    {
        return static::$slim->getContainer()['cache']->store($key, $data, $expires);
    }

    /**
     * Retrieve cached data by key
     *
     * @param string $key
     * @param boolean [optional] $timestamp
     * @return string
     */
    public static function retrieve($key)
    {
        return static::$slim->getContainer()['cache']->retrieve($key);
    }

    /**
     * Check whether data is associated with a key
     *
     * @param string $key
     * @return boolean
     */
    public static function isCached($key)
    {
        return static::$slim->getContainer()['cache']->isCached($key);
    }

    /**
     * Flush all cached entries
     * @return object
     */
    public static function flush()
    {
        return static::$slim->getContainer()['cache']->flush();
    }
}