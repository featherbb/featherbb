<?php

/**
* Copyright (C) 2015 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace FeatherBB\Core;
use FeatherBB\Core\Error;
use FeatherBB\Core\DB;

class Permissions
{
    protected $permissions = array();

    public function __construct()
    {

    }

    public function getUserPermissions($uid = null)
    {
        $uid = (int) $uid;
        if ($uid > 0) {
            $user = DB::for_table('users')->find_one($uid);
            if (!$user) {
                throw new Error('Unknown user ID');
            }
        }

        $where = array(
            ['p.user' => (int) $user->id],
            ['p.group' => (int) $user->group_id]);

        if ($parents = $this->getParents($user->group_id)) {
            foreach ($parents as $parent_id) {
                $where[] = ['p.group' => (int) $parent_id];
            }
        }

        $result = DB::for_table('permissions')
            ->table_alias('p')
            ->select('permission_name')
            ->inner_join('users', array('u.id', '=', $user->id), 'u', true)
            ->where_any_is($where)
            ->where_equal('p.allow', 1)
            ->find_array();

        foreach ($result as $perm) {
            if (!isset($this->permissions[$user->id][$perm['permission_name']])) {
                $this->permissions[$user->id][$perm['permission_name']] = true;
            }
        }
        return $this->permissions;
    }

    public function getParents($gid = null)
    {
        $gid = (int) $gid;
        if ($gid > 0) {
            $group = DB::for_table('groups')->find_one($gid);
            if (!$group) {
                throw new Error('Unknown group ID');
            }
        }
        if (!empty($group['inherit'])) {
            return unserialize($group['inherit']);
        }
        return array();
    }

    public function addParent($gid = null, $new_parent = null)
    {
        $parents = $this->getParents($gid);
        if (!in_array($new_parent, $parents)) {
            $parents[] = $new_parent;
        }
        $result = DB::for_table('groups')
                    ->find_one($gid)
                    ->set('inherit', serialize($parents))
                    ->save();
        return (bool) $result;
    }

    public function allowUser($uid = null, $permission = null)
    {
        $uid = (int) $uid;
        $permission = (string) $permission;

        if ($uid > 0) {
            $user = DB::for_table('users')->find_one($uid);
            if (!$user) {
                throw new Error('Unknown user ID');
            }
        }

        if (!in_array($permission, $this->permissions[$uid])) {
            $result = DB::for_table('permissions')
                        ->create()
                        ->set(array(
                            'permission_name' => $permission,
                            'user' => $uid,
                            'allow' => 1))
                        ->save();
            if ($result) {
                if (empty($this->permissions[$uid])) {
                    $this->getUserPermissions($uid);
                }
                return $this->permissions[(int) $uid][] = (string) $permission;
            }
        }
        return true;
    }

    public function allowGroup($gid = null, $permission = null)
    {
        $gid = (int) $gid;
        $permission = (string) $permission;

        if ($gid > 0) {
            $group = DB::for_table('groups')->find_one($gid);
            if (!$group) {
                throw new Error('Unknown user ID');
            }
        }

        $result = DB::for_table('permissions')
                    ->create()
                    ->set(array(
                        'permission_name' => $permission,
                        'group' => $uid,
                        'allow' => 1))
                    ->save();
        if ($result) {
            $this->permissions = null;
        }
        return (bool) $result->id();
    }

    public function install()
    {
        $database_scheme = array(
            "permissions" => "CREATE TABLE `permissions` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `permission_name` varchar(255) DEFAULT NULL,
                `allow` tinyint(2) DEFAULT NULL,
                `deny` tinyint(2) DEFAULT NULL,
                `user` int(11) DEFAULT NULL,
                `group` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;"
        );
        // + create "inherit" TINYTEXT in groups TABLE

        $installer = new \FeatherBB\Model\Install();
        foreach ($database_scheme as $table => $sql) {
            $installer->create_table($this->feather->forum_settings['db_prefix'].$table, $sql);
        }
    }
}
