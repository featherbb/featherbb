<?php

/**
* Copyright (C) 2015-2016 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace FeatherBB\Core;
use FeatherBB\Core\Database as DB;

class Permissions
{
    protected $permissions = array(),
              $parents = array(),
              $regexs = array();

    public function getParents($gid = null)
    {
        // Remove below line if we use again group inheritance later
        return false;
        $gid = (int) $gid;
        if ($gid > 0) {
            if (!isset($this->parents[$gid])) {
                $this->parents[$gid] = array();
                $group = DB::for_table('groups')->find_one($gid);

                if (!$group) {
                    throw new \ErrorException('Internal error : Unknown group ID', 500);
                }

                if (!empty($group['inherit'])) {
                    $data = unserialize($group['inherit']);
                    $this->parents[$gid] = $data;
                }
            }
            return $this->parents[$gid];
        }
        return false;
    }

    public function addParent($gid = null, $new_parents = null)
    {
        $gid = (int) $gid;
        $new_parent =  (array) $new_parents;

        $old_parents = ($this->getParents($gid)) ? $this->getParents($gid) : array();

        foreach ($new_parents as $id => $parent) {
            if ($gid == $parent) {
                throw new \ErrorException('Internal error : A group cannot be a parent of itself', 500);
            }
            if (!in_array($parent, $old_parents)) {
                $this->parents[$gid][] = $parent;
            }
        }

        $result = DB::for_table('groups')
                    ->find_one($gid)
                    ->set('inherit', serialize($this->parents[$gid]))
                    ->save();
        return $this;
    }

    public function delParent($gid = null, $parent = null)
    {
        $gid = (int) $gid;
        $parent =  (int) $parent;

        if ($gid == $parent) {
            throw new \ErrorException('Internal error : A group cannot be a parent of itself', 500);
        }

        $parents = ($this->getParents($gid)) ? $this->getParents($gid) : array();

        if(($key = array_search($parent, $this->parents[$gid])) !== false) {
            unset($this->parents[$gid][$key]);
            $result = DB::for_table('groups')
                        ->find_one($gid)
                        ->set('inherit', serialize($this->parents[$gid]))
                        ->save();
        } else {
            throw new \ErrorException('Internal error : Group '.$parent.' is not a parent of group '.$gid, 500);
        }
        return $this;
    }

    public function allowUser($user = null, $permission = null)
    {
        if (is_array($permission)) {
            foreach ($permission as $id => $perm) {
                $this->allowUser($user, $perm);
            }
            return $this;
        }

        list($uid, $gid) = $this->getInfosFromUser($user);
        $permission = (string) $permission;

        if (!isset($this->permissions[$gid][$uid])) {
            $this->getUserPermissions($uid);
        }

        if (!isset($this->permissions[$gid][$uid][$permission])) {
            $result = DB::for_table('permissions')
                        ->where('permission_name', $permission)
                        ->where('user', $uid)
                        ->where('deny', 1)
                        ->find_one();

            if ($result) {
                $result->delete();
            }
            $result = DB::for_table('permissions')
                        ->create()
                        ->set(array(
                            'permission_name' => $permission,
                            'user' => $uid,
                            'allow' => 1))
                        ->save();
            if ($result) {
                $this->permissions[$gid][$uid][$permission] = true;
                $this->buildRegex($uid, $gid);
            } else {
                throw new \ErrorException('Internal error : Unable to add new permission to user', 500);
            }
        }
        return $this;
    }

    public function denyUser($user = null, $permission = null)
    {
        if (is_array($permission)) {
            foreach ($permission as $id => $perm) {
                $this->denyUser($user, $perm);
            }
            return $this;
        }

        list($uid, $gid) = $this->getInfosFromUser($user);
        $permission = (string) $permission;

        if (!isset($this->permissions[$gid][$uid])) {
            $this->getUserPermissions($uid);
        }

        if (!isset($this->permissions[$gid][$uid][$permission])) {
            $result = DB::for_table('permissions')
                        ->where('permission_name', $permission)
                        ->where('user', $uid)
                        ->where('allow', 1)
                        ->find_one();
            if ($result) {
                $result->delete();
                unset($this->permissions[$gid][$uid][$permission]);
                $this->buildRegex($uid, $gid);
            }

            $result = DB::for_table('permissions')
                        ->create()
                        ->set(array(
                            'permission_name' => $permission,
                            'deny' => 1,
                            'user' => $uid
                        ))
                        ->save();
        }
        return $this;
    }

    public function allowGroup($gid = null, $permission = null)
    {
        $gid = (int) $gid;
        if (is_array($permission)) {
            foreach ($permission as $id => $perm) {
                $this->allowGroup($gid, $perm);
            }
            return $this;
        }

        if ($gid > 0) {
            $group = DB::for_table('groups')->find_one($gid);
            if (!$group) {
                throw new \ErrorException('Internal error : Unknown group ID', 500);
            }
        }

        $result = DB::for_table('permissions')
                    ->where('permission_name', $permission)
                    ->where('group', $gid)
                    ->where('deny', 1)
                    ->find_one();
        if ($result) {
            $result->delete();
        }

        // If group or one of his parents have the permission, remove it
        if (!array_key_exists($permission, $this->getGroupPermissions($gid)) || $this->getGroupPermissions($gid)[$permission] == false) {
            DB::for_table('permissions')
                ->create()
                ->set(array(
                    'permission_name' => $permission,
                    'group' => $gid,
                    'allow' => 1,
                    'deny'  => null
                ))
                ->save();
        }

        $this->permissions[$gid] = null; // Harsh, but still the fastest way to do
        return $this;
    }

    public function denyGroup($gid = null, $permission = null)
    {
        $gid = (int) $gid;
        if (is_array($permission)) {
            foreach ($permission as $id => $perm) {
                $this->denyGroup($gid, $perm);
            }
            return $this;
        }

        if ($gid > 0) {
            $group = DB::for_table('groups')->find_one($gid);
            if (!$group) {
                throw new \ErrorException('Internal error : Unknown user ID', 500);
            }
        }

        // Remove group permission from DB if exists
        $result = DB::for_table('permissions')
                    ->where('permission_name', $permission)
                    ->where('group', $gid)
                    ->where('allow', 1)
                    ->find_one();
        if ($result) {
            $result->delete();
        }

        // Check if one of his parents have the permission, and force denied permission if needed
        if (array_key_exists($permission, $this->getGroupPermissions($gid)) && $this->getGroupPermissions($gid)[$permission] == true) {
            DB::for_table('permissions')
                ->create()
                ->set(array(
                    'permission_name' => $permission,
                    'group' => $gid,
                    'deny'  => 1,
                    'allow' => null
                ))
                ->save();
        }

        $this->permissions[$gid] = null; // Harsh, but still the fastest way to do
        return $this;
    }

    public function can($user = null, $permission = null)
    {
        list($uid, $gid) = $this->getInfosFromUser($user);
        if (!isset($this->regexs[$gid][$uid])) {
            if (!isset($this->permissions[$gid][$uid])) {
                $this->getUserPermissions($user);
            }
            $this->buildRegex($uid, $gid);
        }
        return (bool) preg_match($this->regexs[$gid][$uid], $permission);
    }

    public function dump()
    {
        return $this->permissions;
    }

    protected function buildRegex($uid = null, $gid = null)
    {
        $perms = array_map(function ($value) {
            $value = str_replace('.', '\.', $value);
            return str_replace('*', '.*', $value);
        }, array_keys($this->permissions[$gid][$uid]));
        $this->regexs[$gid][$uid] = '/^(?:'.implode('|', $perms).')$/';
    }

    public function getUserPermissions($user = null)
    {
        list($uid, $gid) = $this->getInfosFromUser($user);

        $where = array(
            ['p.user' => $uid],
            ['p.group' => $gid]);

        if ($parents = $this->getParents($gid)) {
            foreach ($parents as $parent_id) {
                $where[] = ['p.group' => (int) $parent_id];
            }
        }

        $result = DB::for_table('permissions')
            ->table_alias('p')
            ->select_many('p.permission_name', 'p.allow', 'p.deny')
            ->inner_join('users', array('u.id', '=', $uid), 'u', true)
            ->where_any_is($where)
            ->order_by_desc('p.group') // Read groups first to allow user override
            ->find_array();

        $this->permissions[$gid][$uid] = array();
        foreach ($result as $perm) {
            if (!isset($this->permissions[$gid][$uid][$perm['permission_name']])) {
                if ((bool) $perm['allow']) {
                    $this->permissions[$gid][$uid][$perm['permission_name']] = true;
                }
            } else {
                if ((bool) $perm['deny']) {
                    unset($this->permissions[$gid][$uid][$perm['permission_name']]);
                }
            }
        }

        $this->buildRegex($uid, $gid);
        return $this->permissions;
    }

    protected function getInfosFromUser($user = null)
    {
        if (is_object($user)) {
            $uid = $user->id;
            $gid = $user->group_id;
        } elseif ((int) $user > 0) {
            $data = User::get((int) $user);
            if (!$data) {
                throw new \ErrorException('Internal error : Unknown user ID', 500);
            }
            $uid = $data['id'];
            $gid = $data['group_id'];
        } else {
            throw new \ErrorException('Internal error : wrong user object type', 500);
        }
        return array((int) $uid, (int) $gid);
    }

    public function getGroupPreferences(int $group_id)
    {
        $result = DB::for_table('preferences')
            ->select_many('preference_name', 'preference_value')
            ->where_in('preference_name', array('post.min_interval', 'search.min_interval', 'email.min_interval', 'report.min_interval'))
            ->where_any_is(array(
                array('group' => $group_id),
                array('default' => 1),
            ))
            ->order_by_desc('default')
            ->find_array();

        $group_preferences = array();
        foreach ($result as $pref) {
            $group_preferences[$pref['preference_name']] = $pref['preference_value'];
        }

        return (array) $group_preferences;
    }

    public function getGroupPermissions(int $group_id)
    {
        $where = array(['group' => $group_id]);

        if ($parents = $this->getParents($group_id)) {
            foreach ($parents as $parent_id) {
                $where[] = ['group' => (int) $parent_id];
            }
        }

        $result = DB::for_table('permissions')
            ->select_many('permission_name', 'allow', 'deny', 'group')
            ->where_any_is($where)
            ->order_by_desc('group')
            ->find_array();

        $group_data = $group_permissions = array();

        foreach ($result as $perm) {
            $group_data[$perm['group']][$perm['permission_name']] = (bool) $perm['allow'];
        }
        // Set default permissions
        $default_perms = array('mod.is_mod','mod.edit_users','mod.rename_users','mod.change_passwords','mod.promote_users','mod.ban_users','board.read','topic.reply','topic.post','topic.delete','post.edit','post.delete','post.links','users.view','user.set_title','search.topics','search.users','email.send');
        foreach ($default_perms as $perm) {
            // Init all perms to false
            if (!isset($group_data[$group_id][$perm])) {
                $group_permissions[$perm] = false;
            }
            // Check if parent groups have perm
            if ($parents) {
                foreach ($parents as $parent_id) {
                    if (isset($group_data[$parent_id][$perm])) {
                        $group_permissions[$perm] = $group_data[$parent_id][$perm];
                    }
                }
            }
            // Always override perm if group specific exists
            if (isset($group_data[$group_id][$perm])) {
                $group_permissions[$perm] = $group_data[$group_id][$perm];
            }
        }

        return (array) $group_permissions;
    }
}
