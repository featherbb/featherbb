<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins\Model;


use FeatherBB\Core\Error;
use DB;

class PrivateMessages
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hooks = $this->feather->hooks;
    }

    // Check if current user owns the folder
    public function checkFolderOwner($fid, $uid)
    {
        return DB::for_table('pms_folders')
            ->select('name')
            ->where_any_is([
                ['user_id' => $uid],
                ['user_id' => 1]
            ])
            ->where('id' , $fid)
            ->find_one();
    }

    // Get messages count from context
    public function countMessages($fid, $uid)
    {
        $where[] = ['cd.folder_id' => $fid];
        if ($fid == 1) {
            $where[] = ['cd.viewed' => 0];
        }
        return DB::for_table('pms_conversations')
            ->select('id')
            ->table_alias('c')
            ->inner_join('pms_data', array('c.id', '=', 'cd.conversation_id'), 'cd')
            ->where('cd.user_id', $uid)
            ->where('cd.deleted', 0)
            ->where_any_is($where)
            ->count();
    }

    public function getMessages($fid, $uid, $limit, $start)
    {
        $where[] = ['cd.folder_id' => $fid];
        if ($fid == 1) {
            $where[] = ['cd.viewed' => 0];
        }

        $select = array(
            'c.id', 'c.subject', 'c.poster', 'c.poster_id', 'c.num_replies', 'c.last_post', 'c.last_poster', 'c.last_post_id',
            'cd.viewed',
            'poster_gid' => 'u.group_id', 'u.email',
            'last_poster_id' => 'l.id', 'last_poster_gid' => 'l.group_id'
        );
        return DB::for_table('pms_conversations')
            ->table_alias('c')
            ->select_many($select)
            ->inner_join('pms_data', array('c.id', '=', 'cd.conversation_id'), 'cd')
            ->left_outer_join('users', array('u.id', '=', 'c.poster_id'), 'u')
            ->left_outer_join('users', array('l.username', '=', 'c.last_poster'), 'l', true)
            ->where('cd.user_id', $uid)
            ->where('cd.deleted', 0)
            ->where_any_is($where)
            ->order_by_desc('c.last_post')
            ->limit($limit)
            ->offset($start)
            ->find_many();
    }

}
