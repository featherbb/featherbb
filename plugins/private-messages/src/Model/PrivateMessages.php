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
            ->select('id')
            ->where_any_is([
                ['user_id' => $uid],
                ['user_id' => 1]
            ])
            ->where('id' , $fid)
            ->find_one();
    }

    // Get all inboxes owned by a user
    public function getUserFolders($uid)
    {
        return DB::for_table('pms_folders')
            ->select('name')
            ->select('id')
            ->where_any_is([
                ['user_id' => $uid],
                ['user_id' => 1]
            ])
            ->find_many();
    }

    // Get messages count from context
    public function countMessages($fid, $uid)
    {
        $where[] = ['cd.folder_id' => $fid];
        // if ($fid == 1) {
        //     $where[] = ['cd.viewed' => 0];
        // }
        return DB::for_table('pms_conversations')
            ->select('id')
            ->table_alias('c')
            ->inner_join('pms_data', array('c.id', '=', 'cd.conversation_id'), 'cd')
            ->where('cd.user_id', $uid)
            ->where('cd.deleted', 0)
            ->where_any_is($where)
            ->count();
    }

    // Get messages from inbox
    public function getMessages($fid, $uid, $limit, $start)
    {
        $where[] = ['cd.folder_id' => $fid];
        // if ($fid == 1) {
        //     $where[] = ['cd.viewed' => 0];
        // }

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

    // Delete one or more messages
    public function delete($convers, $uid)
    {
        // Get the number of conversation messages and the number of replies from all conversations
        $numConvers = count($convers);
        $numReplies = DB::for_table('pms_conversations')
            ->table_alias('c')
            ->select('c.num_replies')
            ->inner_join('pms_data', array('c.id', '=', 'cd.conversation_id'), 'cd')
            // ->inner_join('pms_data', array('cd.user_id', '=', $uid), null, true)
            ->where('cd.user_id', $uid)
            ->where_in('c.id', $convers)
            ->sum('c.num_replies');
        $numPms = ($numReplies + $numConvers);

        // Soft delete messages
        DB::configure('id_column_overrides', array(
            'pms_data' => 'conversation_id'
        ));
        DB::for_table('pms_data')
            ->where('user_id', $uid)
            ->where_id_in($convers)
            ->find_result_set()
            ->set('deleted', 1)
            ->save();

        // Decrement user PMs count
        // DB::for_table('users')
        //     ->where('id', $uid)
        //     ->set_expr('num_pms', 'num_pms-'.$numPms);
        //     ->save();

        // Now check if anyone left in the conversation has any of these topics undeleted. If so, then we leave them. Otherwise, actually delete them.
		foreach ($convers as $cid)
		{
            $left = DB::for_table('pms_data')
                ->where('conversation_id', $cid)
                ->where('deleted', 0);

            if ($left->count()) { // People are still left
                continue;
            }

            DB::for_table('pms_data')->where('conversation_id', $cid)->delete_many();
            DB::for_table('pms_messages')->where('conversation_id', $cid)->delete_many();
            DB::for_table('pms_conversations')->where('id', $cid)->delete_many();

		}
    }

    public function addConversation(array $data = array())
    {
        $result = DB::for_table('pms_conversations')
                    ->create()
                    ->set($data);
        $result->save();
        return $result->id();
    }



    public function getConversation($id = null)
    {

    }

    public function addMessage(array $data = array(), $tid = null, $uid = null)
    {
        $add = DB::for_table('pms_messages')
                    ->create()
                    ->set($data)
                    ->set('conversation_id', $tid);
        $add->save();
        $update = DB::for_table('pms_conversations')
                    ->find_one($tid)
                    ->set(array(
                        'first_post_id'	=>	$add->id(),
                        'last_post_id'	=>	$add->id(),
                    ))
                    ->save();
        $notifs = DB::for_table('pms_data')
                ->create()
                ->set(array(
    					'conversation_id'	=>	$tid,
    					'user_id'	=>	$uid,
    					'viewed'	=>	(($uid == $this->feather->user->id) ? 1 : 0)))
                ->save();

        return ($add && $update && $notifs) ? $add->id() : false;
    }

    public function isAllowed($username = null)
    {
        if (!$username) {
            return false;
        }

        $result = DB::for_table('users')
                    ->where('username', $username)
                    ->where_gt('id', 1)
                    ->find_one();
        return $result;
    }

    public function getUserByID($id = null)
    {
        if (!$id) {
            return false;
        }
        $result = DB::for_table('users')
                    ->where('id', $id)
                    ->find_one();
        return $result;
    }
}
