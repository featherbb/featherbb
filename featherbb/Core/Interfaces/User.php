<?php
namespace FeatherBB\Core\Interfaces;

use FeatherBB\Model\Auth as AuthModel;

class User extends \Statical\BaseProxy
{
    /**
     * Load a user infos
     * @param  integer $id The id of the user. If null (default), will return currently logged user
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
     * @param  integer $id  Optionnal user id. If not provided, will return pref for currently logged user
     * @return string       Value of the pref returned by Core/Preferences class
     */
    public static function getPref(string $pref = null, int $id = null)
    {
        $user = self::get($id);
        return Container::get('prefs')->get($user, $pref);
    }
}
