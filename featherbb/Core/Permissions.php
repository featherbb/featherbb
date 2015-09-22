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

    protected function getInfosFromUser($user = null)
    {
        if (is_object($user)) {
            $uid = $user->id;
            $gid = $user->group_id;
        } elseif ((int) $user > 0) {
            $data = DB::for_table('users')->find_one((int) $user);
            if (!$data) {
                throw new Error('Unknown user ID');
            }
            $uid = $data['id'];
            $gid = $data['group_id'];
        } else {
            throw new \ErrorException('Internal error : wrong user object type');
        }
        return array((int) $uid, (int) $gid);
    }

    public function getUserPermissions($user = null)
    {
        list($uid, $gid) = $this->getInfosFromUser($user);

        $where = array(
            ['p.user' => (int) $uid],
            ['p.group' => (int) $gid]);

        if ($parents = $this->getParents($gid)) {
            foreach ($parents as $parent_id) {
                $where[] = ['p.group' => (int) $parent_id];
            }
        }

        $result = DB::for_table('permissions')
            ->table_alias('p')
            ->select('permission_name')
            ->inner_join('users', array('u.id', '=', $uid), 'u', true)
            ->where_any_is($where)
            ->where_equal('p.allow', 1)
            ->find_array();

        $this->permissions[$uid] = array();

        foreach ($result as $perm) {
            if (!isset($this->permissions[$uid][$perm['permission_name']])) {
                $this->permissions[$uid][$perm['permission_name']] = true;
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

    public function allowUser($user = null, $permission = null)
    {
        list($uid, $gid) = $this->getInfosFromUser($user);
        $permission = (string) $permission;

        if (!isset($this->permissions[$uid])) {
            $this->getUserPermissions($uid);
        }

        if (!in_array($permission, array_keys($this->permissions[$uid]))) {
            $result = DB::for_table('permissions')
                        ->create()
                        ->set(array(
                            'permission_name' => $permission,
                            'user' => $uid,
                            'allow' => 1))
                        ->save();
            if ($result) {
                $this->permissions[(int) $uid][(string) $permission] = true;
            } else {
                throw new \ErrorException('Unable to add new permission to user');
            }
        }
        return $this;
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

    public function can($user = null, $permission = null)
    {
        list($uid, $gid) = $this->getInfosFromUser($user);
        if (!isset($this->permissions[$uid])) {
            $this->getPermissions();
        }
        return (bool) isset($this->permissions[$uid][(string) $permission]);
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

    public function getPermissions()
    {
        return var_dump($this->permissions);
    }
}
