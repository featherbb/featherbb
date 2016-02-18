<?php
namespace FeatherBB\Core\Interfaces;

class Router extends SlimSugar
{
    public static function pathFor($name, array $data = [], array $queryParams = [])
    {
        return static::$slim->getContainer()['router']->pathFor($name, $data, $queryParams);
    }

    public static function redirect($uri, $message = null, $status = 301)
    {
        if (is_string($message))
            $message = array('info', $message);
        // Add a flash message if needed
        if (is_array($message))
            Container::get('flash')->addMessage($message[0], $message[1]);

        return Response::withStatus($status)->withHeader('Location', $uri);
    }
}
