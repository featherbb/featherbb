<?php

/*
 * This file is part of the Statical package.
 *
 * (c) John Stevenson <john-stevenson@blueyonder.co.uk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Statical;

/**
* Class that must be extended by all static proxies.
*/
abstract class BaseProxy
{
    /**
    * The class instance to resolve proxy targets.
    *
    * @var object
    */
    protected static $resolver;

    /**
    * Sets the statical resolver for child proxies.
    *
    * @param Manager $resolver
    * @return void
    */
    public static function setResolver($resolver)
    {
        static::$resolver = $resolver;
    }

    /**
    * Returns the proxy target instance
    *
    * @return mixed
    */
    public static function getInstance()
    {
        if (!is_object(static::$resolver)) {
            throw new \RuntimeException('Resolver not set');
        }

        return static::$resolver->getProxyTarget(get_called_class());
    }

    /**
    * Built-in magic method through which child class calls pass.
    *
    * @param string $name
    * @param array $args
    * @return mixed
    */
    public static function __callStatic($name, $args)
    {
        return call_user_func_array(array(static::getInstance(), $name), $args);
    }
}
