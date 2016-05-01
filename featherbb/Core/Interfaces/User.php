<?php
namespace FeatherBB\Core\Interfaces;

use FeatherBB\Model\Auth as AuthModel;
use FeatherBB\Core\Database as DB;

class User extends \Statical\BaseProxy
{
    /**
     * Load a user infos
     * @param  int    $id The id of the user. If null (default), will return currently logged user
     * @return object     User infos taken from database
     */
    public static function get($id = null)
    {
        if (!$id || $id == Container::get('user')->id) {
            // Get current user by default
            return Container::get('user');
        } else {
            // Load user from Db based on $id
            return AuthModel::load_user($id);
        }
    }

    /**
     * Load a user minimal infos, e.g for permissions and preferences
     * @param  mixed    $user Either a user id or user object.
     * @return object     User id and group id
     */
    public static function getBasic($user = null)
    {
        if (is_object($user) && isset($user->id) && isset($user->group_id)) {
            return $user;
        } elseif (!$user || (is_int($user) && intval($user) == Container::get('user')->id)) {
            // Get current user by default
            return Container::get('user');
        } else {
            // Load user from DB based on ID
            return DB::for_table('users')->select('id', 'group_id')->find_one($user);
        }
    }

    /**
     * Get a user preference value
     * @param  string $pref The name of preference to get
     * @param  int     $user  Either a user id or user object.
     * @return string       Value of the pref returned by Core/Preferences class
     */
    public static function getPref($pref = null, $user = null)
    {
        $user = self::getBasic($user);
        return Container::get('prefs')->get($user, $pref);
    }

    /**
     * Check if the given user has the required permissions
     * @param  string $permission  The name of the action to check
     * @param  int     $id         Optionnal user id
     * @return boolval             True if user is allowed to do this
     */
    public static function can($permission = null, $id = null)
    {
        $user = self::getBasic($id);
        return Container::get('perms')->can($user, $permission);
    }

    /**
     * Check if user is in admin group
     * @param  int     $id Optionnal user id
     * @return boolean     Is user in admin group ?
     */
    public static function isAdmin($id = null)
    {
        return self::getBasic($id)->group_id == ForumEnv::get('FEATHER_ADMIN');
    }

    /**
     * Check if user is admin or modo
     * @param  int     $id Optionnal user id
     * @return boolean     Is user in admin/mod group ?
     */
    public static function isAdminMod($id = null)
    {
        $user = self::getBasic($id);
        return $user->group_id == ForumEnv::get('FEATHER_ADMIN') || Container::get('perms')->getGroupPermissions($user->group_id, 'mod.is_mod');
    }
}
