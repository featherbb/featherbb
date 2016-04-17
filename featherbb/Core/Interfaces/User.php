<?php
namespace FeatherBB\Core\Interfaces;

use FeatherBB\Model\Auth as AuthModel;

class User extends \Statical\BaseProxy
{
    /**
     * Load a user infos
     * @param  int    $id The id of the user. If null (default), will return currently logged user
     * @return object     User infos taken from database
     */
    public static function get($id = null)
    {
        if (!$id) {
            // Get current user by default
            return Container::get('user');
        } else {
            // Load user from Db based on $id
            return AuthModel::load_user($id);
        }
    }

    /**
     * Get a user preference value
     * @param  string $pref The name of preference to get
     * @param  int     $id  Optionnal user id. If not provided, will return pref for currently logged user
     * @return string       Value of the pref returned by Core/Preferences class
     */
    public static function getPref(string $pref = null, int $id = null)
    {
        $user = self::get($id);
        return Container::get('prefs')->get($user, $pref);
    }

    /**
     * Check if the given user has the required permissions
     * @param  string $permission  The name of the action to check
     * @param  int     $id         Optionnal user id
     * @return boolval             True if user is allowed to do this
     */
    public static function can(string $permission = null, int $id = null)
    {
        $user = self::get($id);
        return Container::get('perms')->can($user, $permission);
    }

    /**
     * Check if user is in admin group
     * @param  int     $id Optionnal user id
     * @return boolean     Is user in admin group ?
     */
    public static function isAdmin(int $id = null)
    {
        return self::get($id)->g_id == ForumEnv::get('FEATHER_ADMIN');
    }

    /**
     * Check if user is admin or modo
     * @param  int     $id Optionnal user id
     * @return boolean     Is user in admin/mod group ?
     */
    public static function isAdminMod(int $id = null)
    {
        $user = self::get($id);
        return $user->g_id == ForumEnv::get('FEATHER_ADMIN') || Container::get('perms')->can($user, 'mod.is_mod');
    }
}
