<?php

/**
* Copyright (C) 2015-2017 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace FeatherBB\Core;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Interfaces\Cache;
use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\User;

class Permissions
{
    protected $permissions = [];
    protected $parents = [];
    protected $regexs = [];

    public function getParents($gid = null)
    {
        // Remove below line if we use again group inheritance later
        return false;
        $gid = (int) $gid;
        if ($gid > 0) {
            if (!isset($this->parents[$gid])) {
                $this->parents[$gid] = [];
                $group = DB::table('groups')->findOne($gid);

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

    public function addParent($gid = null, $newParents = null)
    {
        $gid = (int) $gid;
        $newParent =  (array) $newParents;

        $oldParents = ($this->getParents($gid)) ? $this->getParents($gid) : [];

        foreach ($newParents as $id => $parent) {
            if ($gid == $parent) {
                throw new \ErrorException('Internal error : A group cannot be a parent of itself', 500);
            }
            if (!in_array($parent, $oldParents)) {
                $this->parents[$gid][] = $parent;
            }
        }

        $result = DB::table('groups')
                    ->findOne($gid)
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

        $parents = ($this->getParents($gid)) ? $this->getParents($gid) : [];

        if (($key = array_search($parent, $this->parents[$gid])) !== false) {
            unset($this->parents[$gid][$key]);
            $result = DB::table('groups')
                        ->findOne($gid)
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
            $result = DB::table('permissions')
                        ->where('permission_name', $permission)
                        ->where('user', $uid)
                        ->where('deny', 1)
                        ->findOne();

            if ($result) {
                $result->delete();
            }
            $result = DB::table('permissions')
                        ->create()
                        ->set([
                            'permission_name' => $permission,
                            'user' => $uid,
                            'allow' => 1])
                        ->save();
            if ($result) {
                $this->permissions[$gid][$uid][$permission] = true;
                $this->buildRegex($uid, $gid);
            } else {
                throw new \ErrorException('Internal error : Unable to add new permission to user', 500);
            }
        }
        // Reload permissions cache
        Cache::store('permissions', \FeatherBB\Model\Cache::getPermissions());
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
            $result = DB::table('permissions')
                        ->where('permission_name', $permission)
                        ->where('user', $uid)
                        ->where('allow', 1)
                        ->findOne();
            if ($result) {
                $result->delete();
                unset($this->permissions[$gid][$uid][$permission]);
                $this->buildRegex($uid, $gid);
            }

            $result = DB::table('permissions')
                        ->create()
                        ->set([
                            'permission_name' => $permission,
                            'deny' => 1,
                            'user' => $uid
                        ])
                        ->save();
        }
        // Reload permissions cache
        Cache::store('permissions', \FeatherBB\Model\Cache::getPermissions());
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
            $group = DB::table('groups')->findOne($gid);
            if (!$group) {
                throw new \ErrorException('Internal error : Unknown group ID', 500);
            }
        }

        $result = DB::table('permissions')
                    ->where('permission_name', $permission)
                    ->where('group', $gid)
                    ->where('deny', 1)
                    ->findOne();
        if ($result) {
            $result->delete();
        }

        // If group or one of his parents have not the permission, add it
        if (!$this->getGroupPermissions($gid, $permission)) {
            DB::table('permissions')
                ->create()
                ->set([
                    'permission_name' => $permission,
                    'group' => $gid,
                    'allow' => 1,
                    'deny'  => null
                ])
                ->save();
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
            $group = DB::table('groups')->findOne($gid);
            if (!$group) {
                throw new \ErrorException('Internal error : Unknown user ID', 500);
            }
        }

        // Remove group permission from DB if exists
        $result = DB::table('permissions')
                    ->where('permission_name', $permission)
                    ->where('group', $gid)
                    ->where('allow', 1)
                    ->findOne();
        if ($result) {
            $result->delete();
        }

        // Check if one of his parents have the permission, and force denied permission if needed
        if ($this->getGroupPermissions($gid, $permission)) {
            DB::table('permissions')
                ->create()
                ->set([
                    'permission_name' => $permission,
                    'group' => $gid,
                    'allow' => null,
                    'deny'  => 1
                ])
                ->save();
        }

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
        // Admins 'got the power!
        if ($gid == ForumEnv::get('FEATHER_ADMIN')) {
            $userPerms = ['*' => true];
        } else { // Regular user
            $allPermissions = Cache::retrieve('permissions');
            if (isset($allPermissions[$gid][0])) {
                $groupPerms = $allPermissions[$gid];
            } else {
                $groupPerms = [[]];
            }

            // Init user permissions with the group defaults
            $userPerms = $groupPerms[0];
            if (array_key_exists($uid, $groupPerms)) {
                // If user have custom permissions, override the defaults
                foreach ($groupPerms[$uid] as $permissionName => $isAllowed) {
                    if (!isset($userPerms[$permissionName])) {
                        if ((bool) $isAllowed) {
                            $userPerms[$permissionName] = true;
                        }
                    } else {
                        if ((bool) !$isAllowed) {
                            unset($userPerms[$permissionName]);
                        }
                    }
                }
            }
        }

        $this->permissions[$gid][$uid] = $userPerms;
        $this->buildRegex($uid, $gid);
        return $userPerms;

        // Legacy code which may be useful later
        // $where = array(
        //     ['p.user' => $uid],
        //     ['p.group' => $gid]);
        //
        // if ($parents = $this->getParents($gid)) {
        //     foreach ($parents as $parentId) {
        //         $where[] = ['p.group' => (int) $parentId];
        //     }
        // }
        //
        // $result = DB::forTable('permissions')
        //     ->tableAlias('p')
        //     ->selectMany('p.permission_name', 'p.allow', 'p.deny')
        //     ->innerJoin('users', array('u.id', '=', $uid), 'u', true)
        //     ->whereAnyIs($where)
        //     ->orderByDesc('p.group') // Read groups first to allow user override
        //     ->findArray();
        //
        // $this->permissions[$gid][$uid] = array();
        // foreach ($result as $perm) {
        //     if (!isset($this->permissions[$gid][$uid][$perm['permission_name']])) {
        //         if ((bool) $perm['allow']) {
        //             $this->permissions[$gid][$uid][$perm['permission_name']] = true;
        //         }
        //     } else {
        //         if ((bool) $perm['deny']) {
        //             unset($this->permissions[$gid][$uid][$perm['permission_name']]);
        //         }
        //     }
        // }
        //
        // $this->buildRegex($uid, $gid);
        // return $this->permissions;
    }

    protected function getInfosFromUser($user = null)
    {
        if (is_object($user)) {
            $uid = $user->id;
            $gid = $user->group_id;
        } elseif ((int) $user > 0) {
            $data = User::getBasic((int) $user);
            if (!$data) {
                throw new \ErrorException('Internal error : Unknown user ID', 500);
            }
            $uid = $data['id'];
            $gid = $data['group_id'];
        } else {
            throw new \ErrorException('Internal error : wrong user object type', 500);
        }
        return [(int) $uid, (int) $gid];
    }

    public function getGroupPermissions($groupId = null, $perm = null)
    {
        $groupId = (int) $groupId;
        $permissions = Cache::retrieve('permissions');
        // Return empty perms array if group id doesn't exist in cache
        if (!isset($permissions[$groupId]) || !isset($permissions[$groupId][0])) {
            return [];
        }
        // Return full group permissions or the one we asked for
        return !empty($perm) ? isset($permissions[$groupId][0][$perm]) : $permissions[$groupId][0];
    }
}
