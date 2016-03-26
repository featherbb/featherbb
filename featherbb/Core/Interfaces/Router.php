<?php
namespace FeatherBB\Core\Interfaces;

use FeatherBB\Core\Url;

class Router extends SlimSugar
{
    public static function pathFor($name, array $data = [], array $queryParams = [])
    {
        $base_url = Url::base_static();
        // if (!(function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()) && is_file(getcwd().'/.htaccess'))) { // If we have Apache's mod_rewrite enabled
        //     $base_url .= '/index.php';
        // }
        return $base_url . static::$slim->getContainer()['router']->pathFor($name, $data, $queryParams);
    }

    public static function redirect($uri, $message = null, $status = 302)
    {
        if (is_string($message))
            $message = array('info', $message);
        // Add a flash message if needed
        if (is_array($message))
            Container::get('flash')->addMessage($message[0], $message[1]);

        return Response::withStatus($status)->withHeader('Location', $uri);
    }
}
