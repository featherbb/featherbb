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

class Preferences
{
    protected $preferences = array();

    public function __construct()
    {

    }

    protected function getInfosFromUser($user = null)
    {
        if (is_object($user)) {
            $uid = $user->id;
            $gid = $user->group_id;
        } elseif ((int) $user > 0) {
            $data = DB::for_table('users')->find_one($user);
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

    public function setUser($user = null, array $prefs)
    {
        list($uid, $gid) = $this->getInfosFromUser($user);

        foreach ($prefs as $pref_name => $pref_value) {
            if ((int) $pref_name > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::for_table('preferences')
                        ->where('preference_name', (string) $pref_name)
                        ->where('user', $uid)
                        ->find_one();
            if ($result) {
                DB::for_table('preferences')
                    ->find_one($result->id())
                    ->set(['preference_value' => (string) $pref_value])
                    ->save();
            } else {
                DB::for_table('preferences')
                    ->create()
                    ->set(array(
                        'preference_name' => (string) $pref_name,
                        'preference_value' => (string) $pref_value,
                        'user' => $uid
                    ))
                    ->save();
            }
        }
        return $this;
    }

    public function setGroup($gid = null, array $prefs)
    {
        $gid = (int) $gid;
        if ($gid < 1) {
            throw new \ErrorException('Internal error : Unknown gid', 500);
        }
        foreach ($prefs as $pref_name => $pref_value) {
            if ((int) $pref_name > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::for_table('preferences')
                        ->where('preference_name', (string) $pref_name)
                        ->where('group', $gid)
                        ->find_one();
            if ($result) {
                DB::for_table('preferences')
                    ->find_one($result->id())
                    ->set(['preference_value' => (string) $pref_value])
                    ->save();
            } else {
                DB::for_table('preferences')
                    ->create()
                    ->set(array(
                        'preference_name' => (string) $pref_name,
                        'preference_value' => (string) $pref_value,
                        'group' => $gid
                    ))
                    ->save();
            }
        }
        return $this;
    }

    public function set(array $prefs)
    {
        foreach ($prefs as $pref_name => $pref_value) {
            if ((int) $pref_name > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::for_table('preferences')
                        ->where('preference_name', (string) $pref_name)
                        ->where('default', 1)
                        ->find_one();
            if ($result) {
                DB::for_table('preferences')
                    ->find_one($result->id())
                    ->set(['preference_value' => (string) $pref_value])
                    ->save();
            } else {
                DB::for_table('preferences')
                    ->create()
                    ->set(array(
                        'preference_name' => (string) $pref_name,
                        'preference_value' => (string) $pref_value,
                        'default' => 1
                    ))
                    ->save();
            }
        }
        return $this;
    }

    // public function exists($uid = null, $gid = null, $pref_name)
    // {
    //     $result = DB::for_table('preferences')
    //                 ->where('preference_name', (string) $pref_name);
    //     if (!is_null($uid)) {
    //         $result->where('user', $uid);
    //     } elseif (!is_null($gid)) {
    //         $result->where('group', $gid);
    //     } else {
    //         $result->where('default', 1);
    //     }
    //     return ($result->find_one()) ? $result : false;
    // }
}
