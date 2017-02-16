<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Random;
use FeatherBB\Core\Utils;

class Install
{
    protected $databaseScheme = [
        'bans' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `username` varchar(200) DEFAULT NULL,
            `ip` varchar(255) DEFAULT NULL,
            `email` varchar(80) DEFAULT NULL,
            `message` varchar(255) DEFAULT NULL,
            `expire` int(10) unsigned DEFAULT NULL,
            `ban_creator` int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `bans_username_idx` (`username`(25))
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'categories' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `cat_name` varchar(80) NOT NULL DEFAULT 'New Category',
            `disp_position` int(10) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'censoring' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `search_for` varchar(60) NOT NULL DEFAULT '',
            `replace_with` varchar(60) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'config' => "CREATE TABLE IF NOT EXISTS %t% (
            `conf_name` varchar(255) NOT NULL DEFAULT '',
            `conf_value` text,
            PRIMARY KEY (`conf_name`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'forum_perms' => "CREATE TABLE IF NOT EXISTS %t% (
            `group_id` int(10) NOT NULL DEFAULT '0',
            `forum_id` int(10) NOT NULL DEFAULT '0',
            `read_forum` tinyint(1) NOT NULL DEFAULT '1',
            `post_replies` tinyint(1) NOT NULL DEFAULT '1',
            `post_topics` tinyint(1) NOT NULL DEFAULT '1',
            PRIMARY KEY (`group_id`,`forum_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'forum_subscriptions' => "CREATE TABLE IF NOT EXISTS %t% (
            `user_id` int(10) unsigned NOT NULL DEFAULT '0',
            `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`user_id`,`forum_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'forums' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `forum_name` varchar(80) NOT NULL DEFAULT 'New forum',
            `forum_desc` text,
            `redirect_url` varchar(100) DEFAULT NULL,
            `moderators` text,
            `num_topics` mediumint(8) unsigned NOT NULL DEFAULT '0',
            `num_posts` mediumint(8) unsigned NOT NULL DEFAULT '0',
            `last_post` int(10) unsigned DEFAULT NULL,
            `last_post_id` int(10) unsigned DEFAULT NULL,
            `last_poster` varchar(200) DEFAULT NULL,
            `sort_by` tinyint(1) NOT NULL DEFAULT '0',
            `disp_position` int(10) NOT NULL DEFAULT '0',
            `cat_id` int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'groups' => "CREATE TABLE  IF NOT EXISTS %t% (
            `g_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `g_title` varchar(50) NOT NULL DEFAULT '',
            `g_user_title` varchar(50) DEFAULT NULL,
            `inherit` text,
            PRIMARY KEY (`g_id`)
        ) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;",
        'online' => "CREATE TABLE IF NOT EXISTS %t% (
            `user_id` int(10) unsigned NOT NULL DEFAULT '1',
            `ident` varchar(200) NOT NULL DEFAULT '',
            `logged` int(10) unsigned NOT NULL DEFAULT '0',
            `idle` tinyint(1) NOT NULL DEFAULT '0',
            `last_post` int(10) unsigned DEFAULT NULL,
            `last_search` int(10) unsigned DEFAULT NULL,
            UNIQUE KEY `online_user_id_ident_idx` (`user_id`,`ident`(25)),
            KEY `online_ident_idx` (`ident`(25)),
            KEY `online_logged_idx` (`logged`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'permissions' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `permission_name` varchar(255) DEFAULT NULL,
            `allow` tinyint(1) DEFAULT NULL,
            `deny` tinyint(1) DEFAULT NULL,
            `user` int(11) DEFAULT NULL,
            `group` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'plugins' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(200) NOT NULL DEFAULT '',
            `installed` tinyint(1) unsigned NOT NULL DEFAULT '1',
            `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
            PRIMARY KEY (`id`),
            UNIQUE KEY `plugin_namex` (`name`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'posts' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `poster` varchar(200) NOT NULL DEFAULT '',
            `poster_id` int(10) unsigned NOT NULL DEFAULT '1',
            `poster_ip` varchar(39) DEFAULT NULL,
            `poster_email` varchar(80) DEFAULT NULL,
            `message` mediumtext,
            `hide_smilies` tinyint(1) NOT NULL DEFAULT '0',
            `posted` int(10) unsigned NOT NULL DEFAULT '0',
            `edited` int(10) unsigned DEFAULT NULL,
            `edited_by` varchar(200) DEFAULT NULL,
            `topic_id` int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `posts_topic_id_idx` (`topic_id`),
            KEY `posts_multi_idx` (`poster_id`,`topic_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'preferences' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `preference_name` varchar(200) NOT NULL,
            `preference_value` tinytext,
            `user` int(10) unsigned DEFAULT NULL,
            `group` int(10) unsigned DEFAULT NULL,
            `default` tinyint(1) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;",
        'reports' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `post_id` int(10) unsigned NOT NULL DEFAULT '0',
            `topic_id` int(10) unsigned NOT NULL DEFAULT '0',
            `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
            `reported_by` int(10) unsigned NOT NULL DEFAULT '0',
            `created` int(10) unsigned NOT NULL DEFAULT '0',
            `message` text,
            `zapped` int(10) unsigned DEFAULT NULL,
            `zapped_by` int(10) unsigned DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `reports_zapped_idx` (`zapped`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'search_cache' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL DEFAULT '0',
            `ident` varchar(200) NOT NULL DEFAULT '',
            `search_data` mediumtext,
            PRIMARY KEY (`id`),
            KEY `search_cache_ident_idx` (`ident`(8))
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'search_matches' => "CREATE TABLE IF NOT EXISTS %t% (
            `post_id` int(10) unsigned NOT NULL DEFAULT '0',
            `word_id` int(10) unsigned NOT NULL DEFAULT '0',
            `subject_match` tinyint(1) NOT NULL DEFAULT '0',
            KEY `search_matches_word_id_idx` (`word_id`),
            KEY `search_matches_post_id_idx` (`post_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'search_words' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `word` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
            PRIMARY KEY (`word`),
            KEY `search_words_id_idx` (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'smilies' => "CREATE TABLE IF NOT EXISTS %t% (
          `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `image` varchar(40) NOT NULL DEFAULT '',
          `text` varchar(20) NOT NULL DEFAULT '',
          `disp_position` tinyint(2) UNSIGNED NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'topic_subscriptions' => "CREATE TABLE IF NOT EXISTS %t% (
            `user_id` int(10) unsigned NOT NULL DEFAULT '0',
            `topic_id` int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`user_id`,`topic_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'topics' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `poster` varchar(200) NOT NULL DEFAULT '',
            `subject` varchar(255) NOT NULL DEFAULT '',
            `posted` int(10) unsigned NOT NULL DEFAULT '0',
            `first_post_id` int(10) unsigned NOT NULL DEFAULT '0',
            `last_post` int(10) unsigned NOT NULL DEFAULT '0',
            `last_post_id` int(10) unsigned NOT NULL DEFAULT '0',
            `last_poster` varchar(200) DEFAULT NULL,
            `num_views` mediumint(8) unsigned NOT NULL DEFAULT '0',
            `num_replies` mediumint(8) unsigned NOT NULL DEFAULT '0',
            `closed` tinyint(1) NOT NULL DEFAULT '0',
            `sticky` tinyint(1) NOT NULL DEFAULT '0',
            `moved_to` int(10) unsigned DEFAULT NULL,
            `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`),
            KEY `topics_forum_id_idx` (`forum_id`),
            KEY `topics_moved_to_idx` (`moved_to`),
            KEY `topics_last_post_idx` (`last_post`),
            KEY `topics_first_post_id_idx` (`first_post_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
        'users' => "CREATE TABLE IF NOT EXISTS %t% (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `group_id` int(10) unsigned NOT NULL DEFAULT '3',
            `username` varchar(200) NOT NULL DEFAULT '',
            `password` char(72) NOT NULL DEFAULT '',
            `email` varchar(80) NOT NULL DEFAULT '',
            `title` varchar(50) DEFAULT NULL,
            `realname` varchar(40) DEFAULT NULL,
            `url` varchar(100) DEFAULT NULL,
            `location` varchar(30) DEFAULT NULL,
            `signature` text,
            `num_posts` int(10) unsigned NOT NULL DEFAULT '0',
            `last_post` int(10) unsigned DEFAULT NULL,
            `last_search` int(10) unsigned DEFAULT NULL,
            `last_email_sent` int(10) unsigned DEFAULT NULL,
            `last_report_sent` int(10) unsigned DEFAULT NULL,
            `registered` int(10) unsigned NOT NULL DEFAULT '0',
            `registration_ip` varchar(39) NOT NULL DEFAULT '0.0.0.0',
            `last_visit` int(10) unsigned NOT NULL DEFAULT '0',
            `admin_note` varchar(30) DEFAULT NULL,
            `activate_string` varchar(80) DEFAULT NULL,
            `activate_key` varchar(8) DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `users_username_idx` (`username`(25)),
            KEY `users_registered_idx` (`registered`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",];

    public function createTable($tableName, $sql)
    {
        $db = DB::getDb();
        $req = preg_replace('/%t%/', '`'.$tableName.'`', $sql);
        return $db->exec($req);
    }

    public function addData($tableName, array $data)
    {
        return (bool) DB::table($tableName)
                        ->create()
                        ->set($data)
                        ->save();
    }

    public function addMockForum(array $arch)
    {
        foreach ($arch as $tableName => $data) {
            $this->addData($tableName, $data);
        }
    }

    public function saveConfig(array $data)
    {
        foreach ($data as $key => $value) {
            $this->addData('config', ['conf_name' => $key,
                                            'conf_value' => $value]);
        }
    }

    public function addSmilies(array $smilies)
    {
        foreach ($smilies as $smiley) {
            $this->addData('smilies', $smiley);
        }
    }

    public function getDatabaseScheme()
    {
        return $this->databaseScheme;
    }

    public static function loadDefaultGroups()
    {
        $groups['Administrators'] = [
            'g_id' => 1,
            'g_title' => __('Administrators'),
            'g_user_title' => __('Administrator'),
        ];
        $groups['Moderators'] = [
            'g_id' => 2,
            'g_title' => __('Moderators'),
            'g_user_title' => __('Moderator'),
        ];
        $groups['Guests'] = [
            'g_id' => 3,
            'g_title' => __('Guests'),
            'g_user_title' => __('Guest'),
        ];
        $groups['Members'] = [
            'g_id' => 4,
            'g_title' => __('Members'),
            'g_user_title' => __('Member'),
        ];

        return $groups;
    }

    public static function loadDefaultUser()
    {
        return $user = [
                'group_id' => 3,
                'username' => __('Guest'),
                'password' => __('Guest'),
                'email' => __('Guest')];
    }

    public static function loadAdminUser(array $data)
    {
        $now = time();
        return $user = [
            'group_id' => 1,
            'username' => $data['username'],
            'password' => Utils::passwordHash($data['password']),
            'email' => $data['email'],
            'num_posts' => 1,
            'last_post' => $now,
            'registered' => $now,
            'registration_ip' => Utils::getIp(),
            'last_visit' => $now];
    }

    public static function loadMockForumData(array $data)
    {
        $catName = __('Test category');
        $subject = __('Test post');
        $message = __('Message');
        $forumName = __('Test forum');
        $forumDesc = __('This is just a test forum');
        $now = time();
        $ip = Utils::getIp();

        return $mockData = [
            'categories' => ['cat_name' => $catName,
                                  'disp_position' => 1],
                'forums' => ['forum_name' => $forumName,
                                  'forum_desc' => $forumDesc,
                                  'num_topics' => 1,
                                  'num_posts' => 1,
                                  'last_post' => $now,
                                  'last_post_id' => 1,
                                  'last_poster' => $data['username'],
                                  'disp_position' => 1,
                                  'cat_id' =>  1],
                'topics' => ['poster' => $data['username'],
                                  'subject' => $subject,
                                  'posted' => $now,
                                  'first_post_id' => 1,
                                  'last_post' => $now,
                                  'last_post_id' => 1,
                                  'last_poster' => $data['username'],
                                  'forum_id' => 1],
                'posts' => ['poster' => $data['username'],
                                 'poster_id' => 2,
                                 'poster_ip' => $ip,
                                 'message' => $message,
                                 'posted' => $now,
                                 'topic_id' => 1]];
    }

    public static function loadSmilies()
    {
        return $smilies = [
            [
                'id' => 1,
                'image' => 'smile.png',
                'text' => ':)',
                'disp_position' => 0
            ],
            [
                'id' => 2,
                'image' => 'smile.png',
                'text' => '=)',
                'disp_position' => 1
            ],
            [
                'id' => 3,
                'image' => 'neutral.png',
                'text' => ':|',
                'disp_position' => 2
            ],
            [
                'id' => 4,
                'image' => 'neutral.png',
                'text' => '=|',
                'disp_position' => 3
            ],
            [
                'id' => 5,
                'image' => 'sad.png',
                'text' => ':(',
                'disp_position' => 4
            ],
            [
                'id' => 6,
                'image' => 'sad.png',
                'text' => '=(',
                'disp_position' => 5
            ],
            [
                'id' => 7,
                'image' => 'big_smile.png',
                'text' => '=D',
                'disp_position' => 6
            ],
            [
                'id' => 8,
                'image' => 'big_smile.png',
                'text' => ':D',
                'disp_position' => 7
            ],
            [
                'id' => 9,
                'image' => 'yikes.png',
                'text' => ':o',
                'disp_position' => 8
            ],
            [
                'id' => 10,
                'image' => 'yikes.png',
                'text' => ':O',
                'disp_position' => 9
            ],
            [
                'id' => 11,
                'image' => 'wink.png',
                'text' => ';)',
                'disp_position' => 10
            ],
            [
                'id' => 12,
                'image' => 'hmm.png',
                'text' => ':/',
                'disp_position' => 11
            ],
            [
                'id' => 13,
                'image' => 'tongue.png',
                'text' => ':P',
                'disp_position' => 12
            ],
            [
                'id' => 14,
                'image' => 'tongue.png',
                'text' => ':p',
                'disp_position' => 13
            ],
            [
                'id' => 15,
                'image' => 'lol.png',
                'text' => ':lol:',
                'disp_position' => 14
            ],
            [
                'id' => 16,
                'image' => 'mad.png',
                'text' => ':mad:',
                'disp_position' => 15
            ],
            [
                'id' => 17,
                'image' => 'roll.png',
                'text' => ':rolleyes:',
                'disp_position' => 16
            ],
            [
                'id' => 18,
                'image' => 'cool.png',
                'text' => ':cool:',
                'disp_position' => 17
            ],
        ];
    }
}
