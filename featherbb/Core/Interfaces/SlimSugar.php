<?php
namespace FeatherBB\Core\Interfaces;

abstract class SlimSugar extends \Statical\BaseProxy
{
    public static $slim;

    public static function __callStatic($name, $args)
    {
        // Enforce named class methods only
        throw new \BadMethodCallException($name.' method not available');
    }
}
