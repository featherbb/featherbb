<?php
namespace FeatherBB\Core\Interfaces;


class Hooks extends SlimSugar
{
    /**
     * Invoke hook
     * @param  string $name The hook name
     * @param  mixed  ...   (Optional) Argument(s) for hooked functions, can specify multiple arguments
     * @return mixed data
     */
    public static function fire($name)
    {
        return static::$slim->getContainer()['hooks']->fire($name);
    }

    /**
     * Invoke hook for DB
     * @param  string $name The hook name
     * @param  mixed  ...   Argument(s) for hooked functions, can specify multiple arguments
     * @return mixed
     */
    public static function fireDB($name, $name2)
    {
        return static::$slim->getContainer()['hooks']->fireDB($name, $name2);
    }

    /**
     * Assign hook
     * @param  string   $name       The hook name
     * @param  mixed    $callable   A callable object
     * @param  int      $priority   The hook priority; 0 = high, 10 = low
     */
    public function bind($name, $callable, $priority = 10)
    {
        return static::$slim->getContainer()['hooks']->bind($name, $callable, $priority);
    }
}