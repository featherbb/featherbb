<?php
namespace FeatherBB\Core\Interfaces;

use FeatherBB\Core\Utils;

class Input extends \Statical\BaseProxy
{
    public static function file($name)
    {
        return isset($_fILES[$name]) && $_fILES[$name]['size'] ? $_fILES[$name] : null;
    }
    public static function getParam($key, $default = null, $sanitize = true)
    {
        $result = Request::getParam($key, $default);
        if ($sanitize) {
            $result = htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
            $result = Utils::trim($result);
        }
        return $result;
    }
    public static function post($key, $default = null, $sanitize = false)
    {
        $result = Request::getParsedBodyParam($key, $default);
        if ($sanitize) {
            $result = htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
            $result = Utils::trim($result);
        }
        return $result;
    }
    public static function query($key, $default = null, $sanitize = true)
    {
        $result = Request::getQueryParam($key, $default);
        if ($sanitize) {
            $result = htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
            $result = Utils::trim($result);
        }
        return $result;
    }
}
