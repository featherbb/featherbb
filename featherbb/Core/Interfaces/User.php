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
     * @param  int    $id The id of the user. If null (default), will return currently logged user
     * @return object     User id and group id
     */
    public static function getBasic($id = null)
    {
        if (!$id || $id == Container::get('user')->id) {
            // Get current user by default
            return Container::get('user');
        } else {
            // Load user from DB based on $id
            return DB::for_table('users')
                ->table_alias('u')
                ->inner_join('groups', array('u.group_id', '=', 'g.g_id'), 'g')
                ->where('u.id', $id)
                ->select_many('u.id', 'u.group_id', 'g.g_moderator')
                ->find_one();
        }
    }

    /**
     * Get a user preference value
     * @param  string $pref The name of preference to get
     * @param  int     $id  Optionnal user id. If not provided, will return pref for currently logged user
     * @return string       Value of the pref returned by Core/Preferences class
     */
    public static function getPref($pref = null, $id = null)
    {
        $user = self::getBasic($id);
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
        return $user->group_id == ForumEnv::get('FEATHER_ADMIN') || $user->g_moderator == '1';
    }
}
