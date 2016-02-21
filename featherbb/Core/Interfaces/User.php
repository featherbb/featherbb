<?php
namespace FeatherBB\Core\Interfaces;

use FeatherBB\Model\Auth as AuthModel;

class User extends \Statical\BaseProxy
{
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
}
