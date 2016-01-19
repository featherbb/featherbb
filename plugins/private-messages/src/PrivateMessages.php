<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Error;
use FeatherBB\Core\Plugin as BasePlugin;

class PrivateMessages extends BasePlugin
{

    public function run()
    {
        $feather = $this->feather;

        $this->hooks->bind('admin.plugin.menu', [$this, 'getName']);
        $this->hooks->bind('view.header.navlinks', [$this, 'addNavlink']);
        $this->hooks->bind('model.print_posts.one', function ($cur_post) use ($feather) {
            $cur_post['user_contacts'][] = '<span class="email"><a href="'.$feather->urlFor('Conversations.send', ['uid' => $cur_post['poster_id']]).'">PM</a></span>';
            return $cur_post;
        });

        $this->feather->group('/conversations',
            function() use ($feather) {
                if($feather->user->is_guest) throw new Error(__('No permission'), 403);
            }, function() use ($feather){
                $feather->map('/inbox(/:inbox_id)(/)', '\FeatherBB\Plugins\Controller\PrivateMessages:index')->conditions(array('inbox_id' => '[0-9]+'))->via('GET', 'POST')->name('Conversations.home');
                $feather->map('/inbox(/:inbox_id)/page(/:page)(/)', '\FeatherBB\Plugins\Controller\PrivateMessages:index')->conditions(array('inbox_id' => '[0-9]+', 'page' => '[0-9]+'))->via('GET', 'POST')->name('Conversations.home.page');
                $feather->get('/thread(/:tid)(/)', '\FeatherBB\Plugins\Controller\PrivateMessages:show')->conditions(array('tid' => '[0-9]+'))->name('Conversations.show');
                $feather->get('/thread(/:tid)/page(/:page)(/)', '\FeatherBB\Plugins\Controller\PrivateMessages:show')->conditions(array('tid' => '[0-9]+', 'page' => '[0-9]+'))->name('Conversations.show.page');
                $feather->map('/send(/:uid)(/)', '\FeatherBB\Plugins\Controller\PrivateMessages:send')->conditions(array('uid' => '[0-9]+'))->via('GET', 'POST')->name('Conversations.send');
                $feather->map('/reply/:tid(/)', '\FeatherBB\Plugins\Controller\PrivateMessages:reply')->conditions(array('tid' => '[0-9]+'))->via('GET', 'POST')->name('Conversations.reply');
                $feather->map('/quote/:mid(/)', '\FeatherBB\Plugins\Controller\PrivateMessages:reply')->conditions(array('mid' => '[0-9]+'))->via('GET', 'POST')->name('Conversations.quote');
                $feather->map('/options/blocked(/)', '\FeatherBB\Plugins\Controller\PrivateMessages:blocked')->conditions(array('mid' => '[0-9]+'))->via('GET', 'POST')->name('Conversations.blocked');
                $feather->map('/options/folders(/)', '\FeatherBB\Plugins\Controller\PrivateMessages:folders')->conditions(array('mid' => '[0-9]+'))->via('GET', 'POST')->name('Conversations.folders');
            }
        );

        $this->feather->template->addAsset('css', 'plugins/private-messages/src/style/private-messages.css');
    }

    public function addNavlink($navlinks)
    {
        load_textdomain('private_messages', dirname(__FILE__).'/lang/'.$this->feather->user->language.'/private-messages.mo');
        if (!$this->feather->user->is_guest) {
            $nbUnread = Model\PrivateMessages::countUnread($this->feather->user->id);
            $count = ($nbUnread > 0) ? ' ('.$nbUnread.')' : '';
            $navlinks[] = '4 = <a href="'.$this->feather->urlFor('Conversations.home').'">PMS'.$count.'</a>';
            if ($nbUnread > 0) {
                $this->hooks->bind('header.toplist', function($toplists) {
                    $toplists[] = '<li class="reportlink"><span><strong><a href="'.$this->feather->urlFor('Conversations.home', ['inbox_id' => 1]).'">'.__('Unread messages', 'private_messages').'</a></strong></span></li>';
                    return $toplists;
                });
            }
        }
        return $navlinks;
    }

    public function install()
    {
        load_textdomain('private_messages', dirname(__FILE__).'/lang/'.$this->feather->forum_settings['o_default_lang'].'/private-messages.mo');

        $database_scheme = array(
            'pms_data' => "CREATE TABLE IF NOT EXISTS %t% (
                `conversation_id` int(10) unsigned NOT NULL,
                `user_id` int(10) unsigned NOT NULL DEFAULT '0',
                `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `viewed` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `folder_id` int(10) unsigned NOT NULL DEFAULT '2',
                PRIMARY KEY (`conversation_id`, `user_id`),
                KEY `folder_idx` (`folder_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
            'pms_folders' => "CREATE TABLE IF NOT EXISTS %t% (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(80) NOT NULL DEFAULT 'New Folder',
                `user_id` int(10) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `user_idx` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
            'pms_blocks' => "CREATE TABLE IF NOT EXISTS %t% (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `user_id` int(10) NOT NULL DEFAULT '0',
                `block_id` int(10) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );

        // Create tables
        $installer = new \FeatherBB\Model\Install();
        foreach ($database_scheme as $table => $sql) {
            $installer->create_table($this->feather->forum_settings['db_prefix'].$table, $sql);
        }

        // Create default inboxes
        $folders = array(
            __('New', 'private_messages'),
            __('Inbox', 'private_messages'),
            __('Archived', 'private_messages')
        );

        foreach ($folders as $folder)
        {
            $insert = array(
                'name'    =>    $folder,
                'user_id'    =>    1,
            );
            $installer->add_data('pms_folders', $insert);
        }
    }

    public function remove()
    {
        $db = DB::get_db();
        $tables = ['pms_data', 'pms_folders', 'pms_messages', 'pms_conversations', 'pms_blocks'];
        foreach ($tables as $i)
        {
            $tableExists = DB::for_table($i)->raw_query('SHOW TABLES LIKE "'. $this->feather->forum_settings['db_prefix'].$i . '"')->find_one();
            if ($tableExists)
            {
                $db->exec('DROP TABLE '.$this->feather->forum_settings['db_prefix'].$i);
            }
        }
    }

}
