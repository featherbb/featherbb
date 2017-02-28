<?php
namespace FeatherBB\Core\Interfaces;


class Prefs extends SlimSugar
{
    public static function setUser($user = null, $prefs, $gid = null)
    {
        return static::$slim->getContainer()['prefs']->setUser($user, $prefs, $gid);
    }

    public static function setGroup($gid = null, $prefs)
    {
        return static::$slim->getContainer()['prefs']->setGroup($gid, $prefs);
    }

    public static function set(array $prefs) // Default
    {
        return static::$slim->getContainer()['prefs']->set($prefs);
    }

    public static function delUser($user = null, $prefs = null)
    {
        return static::$slim->getContainer()['prefs']->delUser($user, $prefs);
    }

    public static function delGroup($gid = null, $prefs = null)
    {
        return static::$slim->getContainer()['prefs']->delGroup($gid, $prefs);
    }

    public static function del($prefs = null) // Default
    {
        return static::$slim->getContainer()['prefs']->del($prefs);
    }

    public static function get($user = null, $pref = null)
    {
        return static::$slim->getContainer()['prefs']->get($user, $pref);
    }

    public static function loadPrefs($user = null)
    {
        return static::$slim->getContainer()['prefs']->loadPrefs($user);
    }

    protected function getInfosFromUser($user = null)
    {
        return static::$slim->getContainer()['prefs']->getInfosFromUser($user);
    }

    public static function getGroupPreferences($groupId = null, $preference = null)
    {
        return static::$slim->getContainer()['prefs']->getGroupPreferences($groupId, $preference);
    }
}