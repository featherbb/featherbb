<?php

/**
* Copyright (C) 2015-2017 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace FeatherBB\Core;

use FeatherBB\Core\Database as DB;

class Preferences
{
    protected $preferences = [];

    // Add / Update

    public function setUser($user = null, $prefs, $gid = null)
    {
        if ($gid === null) {
            list($uid, $gid) = $this->getInfosFromUser($user);
        } else {
            $uid = (int) $user;
        }

        foreach ($prefs as $prefName => $prefValue) {
            $prefName = (string) $prefName;
            $prefValue = (string) $prefValue;

            if ((int) $prefName > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::forTable('preferences')
                        ->where('preference_name', $prefName)
                        ->where('user', $uid)
                        ->findOne();

            if (Container::get('forum_settings') && ForumSettings::get($prefName) == $prefValue) {
                if ($result) {
                    $result->delete();
                }
            } else {
                if ($result) {
                    DB::forTable('preferences')
                        ->findOne($result->id())
                        ->set(['preference_value' => $prefValue])
                        ->save();
                } else {
                    DB::forTable('preferences')
                        ->create()
                        ->set([
                            'preference_name' => $prefName,
                            'preference_value' => $prefValue,
                            'user' => $uid
                        ])
                        ->save();
                }
            }
            $this->preferences[$gid][$uid][$prefName] = $prefValue;
        }
        return $this;
    }

    public function setGroup($gid = null, $prefs)
    {
        $gid = (int) $gid;
        if ($gid < 1) {
            throw new \ErrorException('Internal error : Unknown gid', 500);
        }
        foreach ($prefs as $prefName => $prefValue) {
            if ((int) $prefName > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::forTable('preferences')
                        ->where('preference_name', (string) $prefName)
                        ->where('group', $gid)
                        ->findOne();
            if (Container::get('forum_settings') && ForumSettings::get($prefName) == $prefValue) {
                if ($result) {
                    $result->delete();
                }
            } else {
                if ($result) {
                    DB::forTable('preferences')
                        ->findOne($result->id())
                        ->set(['preference_value' => (string) $prefValue])
                        ->save();
                } else {
                    DB::forTable('preferences')
                        ->create()
                        ->set([
                            'preference_name' => (string) $prefName,
                            'preference_value' => (string) $prefValue,
                            'group' => $gid
                        ])
                        ->save();
                }
            }
            unset($this->preferences[$gid]);
        }
        return $this;
    }

    public function set(array $prefs) // Default
    {
        foreach ($prefs as $prefName => $prefValue) {
            if ((int) $prefName > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::forTable('preferences')
                        ->where('preference_name', (string) $prefName)
                        ->where('default', 1)
                        ->findOne();
            if ($result) {
                DB::forTable('preferences')
                    ->findOne($result->id())
                    ->set(['preference_value' => (string) $prefValue])
                    ->save();
            } else {
                DB::forTable('preferences')
                    ->create()
                    ->set([
                        'preference_name' => (string) $prefName,
                        'preference_value' => (string) $prefValue,
                        'default' => 1
                    ])
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
        foreach ($prefs as $prefId => $prefName) {
            $prefName = (string) $prefName;

            if ((int) $prefName > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::forTable('preferences')
                        ->where('preference_name', $prefName)
                        ->where('user', $uid)
                        ->findOne();
            if ($result) {
                $result->delete();
                unset($this->preferences[$gid][$uid][$prefName]);
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

        foreach ($prefs as $prefId => $prefName) {
            $prefName = (string) $prefName;

            if ((int) $prefName > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::forTable('preferences')
                        ->where('preference_name', $prefName)
                        ->where('group', $gid)
                        ->findOne();
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
        foreach ($prefs as $prefId => $prefName) {
            if ((int) $prefName > 0) {
                throw new \ErrorException('Internal error : preference name cannot be an integer', 500);
            }
            $result = DB::forTable('preferences')
                        ->where('preference_name', (string) $prefName)
                        ->where('default', 1)
                        ->findOne();
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

        $result = DB::forTable('preferences')
                    ->tableAlias('p')
                    ->whereAnyIs([
                        ['p.user' => $uid],
                        ['p.group' => $gid],
                        ['p.default' => 1],
                    ])
                    ->orderByDesc('p.default')
                    ->orderByAsc('p.user')
                    ->findArray();

        $this->preferences[$gid][$uid] = [];
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
        return [(int) $uid, (int) $gid];
    }

    public function getGroupPreferences($groupId = null, $preference = null)
    {
        $preferences = Container::get('cache')->retrieve('group_preferences');
        if (empty($preference)) {
            return (array) $preferences[$groupId];
        }

        return isset($preferences[$groupId][$preference]) ? $preferences[$groupId][$preference] : null;
    }
}
