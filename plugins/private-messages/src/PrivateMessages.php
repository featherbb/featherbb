<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins;

use FeatherBB\Core\Plugin as BasePlugin;
use FeatherBB\Core\Error;

class PrivateMessages extends BasePlugin
{
    public function __construct()
    {
        parent::__construct();
        
    }

    public function run()
    {
        $this->hooks->bind('admin.plugin.menu', [$this, 'getName']);
        // $this->hooks->bind('header.navlinks', [$this, 'addNavlink']);
        // $feather = $this->feather;
        // $this->feather->get('/conversations(/)', function() use ($feather) {
        //     if(!$feather->user->logged) {
        //         throw new Error(__('No permission'), 403);
        //     }
        // }, [$this, 'testRoute'])->name('testRoute');
    }

    public function addMarkRead($forum_actions)
    {
        $forum_actions[] = '<a href="' . $this->feather->url->get('mark-read/') . '">Test1</a>';
        $forum_actions[] = '<a href="' . $this->feather->url->get('mark-read/') . '">Test2</a>';
        return $forum_actions;
    }

    public function addNavlink($navlinks)
    {
        $navlinks[] = [5 => '<a href="'.$this->feather->urlFor('testRoute').'">Test plugin</a>'];
        return $navlinks;
    }

    public function install()
    {
        $database_scheme = array(
            'pms_data' => "CREATE TABLE IF NOT EXISTS %t% (
                `conversation_id` int(10) unsigned NOT NULL,
                `user_id` int(10) unsigned NOT NULL DEFAULT '0',
                `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `viewed` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `folder_id` int(10) unsigned NOT NULL DEFAULT '2',
                PRIMARY KEY (`conversation_id`, `user_id`),
                KEY `folder_idx` (`folder_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
            'pms_folders' => "CREATE TABLE IF NOT EXISTS %t% (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(80) NOT NULL DEFAULT 'New Folder',
                `user_id` int(10) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `user_idx` (`user_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
            'pms_messages' => "CREATE TABLE IF NOT EXISTS %t% (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `poster` varchar(200) NOT NULL DEFAULT '',
                `poster_id` int(10) unsigned NOT NULL DEFAULT '1',
                `poster_ip` varchar(39) DEFAULT NULL,
                `message` mediumtext,
                `hide_smilies` tinyint(1) NOT NULL DEFAULT '0',
                `sent` int(10) unsigned NOT NULL DEFAULT '0',
                `edited` int(10) unsigned DEFAULT NULL,
                `edited_by` varchar(200) DEFAULT NULL,
                `conversation_id` int(10) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `conversation_idx` (`conversation_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;",
            'pms_conversations' => "CREATE TABLE IF NOT EXISTS %t% (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `poster` varchar(200) NOT NULL DEFAULT '',
                `poster_id` int(10) unsigned NOT NULL DEFAULT '0',
                `subject` varchar(255) NOT NULL DEFAULT '',
                `first_post_id` int(10) unsigned NOT NULL DEFAULT '0',
                `last_post_id` int(10) unsigned NOT NULL DEFAULT '0',
                `last_post` int(10) unsigned NOT NULL DEFAULT '0',
                `last_poster` varchar(200) DEFAULT NULL,
                `num_replies` mediumint(8) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;"
        );

        // Create tables
        $installer = new \FeatherBB\Model\Install();
        $errors = [];
        foreach ($database_scheme as $table => $sql) {
            if (!$installer->create_table($this->feather->forum_settings['db_prefix'].$table, $sql)) {
                $errors[] = $table;
            }
        }
        if (!empty($errors)) {
            $this->feather->flash('error', 'A problem was encountered while creating tables '.implode(', ', $errors));
        }

        // Create default
        $folders = array($lang_install['New'], $lang_install['Inbox'], $lang_install['Archived']);
    	foreach ($folders as $folder)
    	{
    		$insert = array(
    			'name'	=>	$folder,
    			'user_id'	=>	1,
    		);

    		$db->insert('folders', $insert);
    	}

    }

}
