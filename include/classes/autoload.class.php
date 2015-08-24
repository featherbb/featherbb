<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB;

class Loader
{
    protected static $paths = array('include/classes/');

    public static function autoload($class)
    {
        foreach (self::$paths as $path) {
            $class_file = $path.strtolower(str_replace(__NAMESPACE__.'\\', '', $class)).'.class.php';
            if (is_file($class_file)) {
                require $class_file;
            }
        }
    }

    public static function registerAutoloader()
    {
        spl_autoload_register(__NAMESPACE__ . '\Loader::autoload');
    }
}
