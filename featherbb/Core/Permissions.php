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

    public function addParent($gid = null, $new_parent = null)
    {
        $gid = (int) $gid;
        $new_parent =  (int) $new_parent;

        if ($gid == $new_parent) {
            throw new \ErrorException('Internal error : A group cannot be a parent of itself', 500);
        }

        $parents = ($this->getParents($gid)) ? $this->getParents($gid) : array();

        if (!in_array($new_parent, $parents)) {
            $this->parents[$gid][] = $new_parent;
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
                throw new \ErrorException('Internal error : Unknown user ID', 500);
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
        $result = DB::for_table('permissions')
                    ->create()
                    ->set(array(
                        'permission_name' => $permission,
                        'group' => $gid,
                        'allow' => 1))
                    ->save();
        if ($result) {
            $this->permissions[$gid] = null; // Harsh, but still the fastest way to do
        }
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

        $result = DB::for_table('permissions')
                    ->where('permission_name', $permission)
                    ->where('group', $gid)
                    ->where('allow', 1)
                    ->find_one();
        if ($result) {
            $result->delete();
            $this->permissions[$gid] = null; // Harsh, but still the fastest way to do
        }
        $result = DB::for_table('permissions')
                    ->create()
                    ->set(array(
                        'permission_name' => $permission,
                        'group' => $gid,
                        'deny' => 1))
                    ->save();
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
        $this->regexs[$gid][$uid] = '/^(?:'.implode(' | ', $perms).')$/';
    }

    protected function getUserPermissions($user = null)
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
}
