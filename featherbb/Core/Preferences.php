<?php

/**
* Copyright (C) 2015-2016 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace FeatherBB\Core;
use FeatherBB\Core\Database as DB;

class Preferences
{
    protected $preferences = array();

    // Add / Update

    public function setUser($user = null, $prefs, $gid = null)
    {
        if ($gid === null) {
            list($uid, $gid) = $this->getInfosFromUser($user);
        }
        else {
            $uid = (int) $user;
        }

        foreach ($prefs as $pref_name => $pref_value) {
            $pref_name = (string) $pref_name;
            $pref_value = (string) $pref_value;

            if ((int) $pref_name > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::for_table('preferences')
                        ->where('preference_name', $pref_name)
                        ->where('user', $uid)
                        ->find_one();

            if (Container::get('forum_settings') && ForumSettings::get($pref_name) == $pref_value) {
                if ($result) {
                    $result->delete();
                }
            } else {
                if ($result) {
                    DB::for_table('preferences')
                        ->find_one($result->id())
                        ->set(['preference_value' => $pref_value])
                        ->save();
                } else {
                    DB::for_table('preferences')
                        ->create()
                        ->set(array(
                            'preference_name' => $pref_name,
                            'preference_value' => $pref_value,
                            'user' => $uid
                        ))
                        ->save();
                }
            }
            $this->preferences[$gid][$uid][$pref_name] = $pref_value;
        }
        return $this;
    }

    public function setGroup($gid = null, $prefs)
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
            if (Container::get('forum_settings') && ForumSettings::get($pref_name) == $pref_value) {
                if ($result) {
                    $result->delete();
                }
            } else {
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
            unset($this->preferences[$gid]);
        }
        return $this;
    }

    public function set(array $prefs) // Default
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
            unset($this->preferences);
        }
        return $this;
    }

    // Delete

    public function delUser($user = null, $prefs = null)
    {
        list($uid, $gid) = $this->getInfosFromUser($user);
        $prefs = (array) $prefs;
        foreach ($prefs as $pref_id => $pref_name) {
            $pref_name = (string) $pref_name;

            if ((int) $pref_name > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::for_table('preferences')
                        ->where('preference_name', $pref_name)
                        ->where('user', $uid)
                        ->find_one();
            if ($result) {
                $result->delete();
                unset($this->preferences[$gid][$uid][$pref_name]);
            } else {
                throw new \ErrorException('Internal error : Unknown preference name', 500);
            }
        }
        return $this;
    }

    public function delGroup($gid = null, $prefs = null)
    {
        $gid = (int) $gid;
        if ($gid < 1) {
            throw new \ErrorException('Internal error : Unknown gid', 500);
        }
        $prefs = (array) $prefs;

        foreach ($prefs as $pref_id => $pref_name) {
            $pref_name = (string) $pref_name;

            if ((int) $pref_name > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::for_table('preferences')
                        ->where('preference_name', $pref_name)
                        ->where('group', $gid)
                        ->find_one();
            if ($result) {
                $result->delete();
            } else {
                throw new \ErrorException('Internal error : Unknown preference name', 500);
            }
        }
        unset($this->preferences[$gid]);
        return $this;
    }

    public function del($prefs = null) // Default
    {
        $prefs = (array) $prefs;
        foreach ($prefs as $pref_id => $pref_name) {
            if ((int) $pref_name > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::for_table('preferences')
                        ->where('preference_name', (string) $pref_name)
                        ->where('default', 1)
                        ->find_one();
            if ($result) {
                $result->delete();
            } else {
                throw new \ErrorException('Internal error : Unknown preference name', 500);
            }
        }
        unset($this->preferences);
        return $this;
    }

    // Getters

    public function get($user = null, $pref = null)
    {
        list($uid, $gid) = $this->getInfosFromUser($user);

        if (!isset($this->preferences[$gid][$uid])) {
            $this->loadPrefs($user);
        }
        if (empty($pref)) {
            return $this->preferences[$gid][$uid];
        }
        return (isset($this->preferences[$gid][$uid][(string) $pref])) ? $this->preferences[$gid][$uid][(string) $pref] : null;
    }

    // Utils

    public function loadPrefs($user = null)
    {
        list($uid, $gid) = $this->getInfosFromUser($user);

        $result = DB::for_table('preferences')
                    ->table_alias('p')
                    ->where_any_is(array(
                        array('p.user' => $uid),
                        array('p.group' => $gid),
                        array('p.default' => 1),
                    ))
                    ->order_by_desc('p.default')
                    ->order_by_asc('p.user')
                    ->find_array();

        $this->preferences[$gid][$uid] = array();
        foreach ($result as $pref) {
            $this->preferences[$gid][$uid][(string) $pref['preference_name']] = $pref['preference_value'];
        }
        return $this->preferences[$gid][$uid];
    }

    protected function getInfosFromUser($user = null)
    {
        if (is_object($user)) {
            $uid = $user->id;
            $gid = $user->group_id;
        } elseif ((int) $user > 0) {
            $data = User::get($user);
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

    public function getGroupPreferences($group_id = null, $preference = null)
    {
        $preferences = Container::get('cache')->retrieve('group_preferences');
        if (empty($preference)) {
            return (array) $preferences[$group_id];
        }

        return isset($preferences[$group_id][$preference]) ? $preferences[$group_id][$preference] : null;
    }
}
