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

        DB::configure('id_column_overrides', array(
            'pms_data' => array('conversation_id', 'user_id'),
        ));
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
        $result = DB::for_table('pms_folders')
            ->select('name')
            ->select('id')
            ->where_any_is([
                ['user_id' => $uid],
                ['user_id' => 1]
            ])
            ->find_array();

        $output = false;
        foreach($result as $inbox) {
            $output[(int) $inbox['id']] = array('name' => $inbox['name']);
        }
        return $output;
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

    public function getConversations($inboxes = null, $uid = null, $limit = 50, $start = 0)
    {
        $inboxes = (array) $inboxes;
        $where = array();
        foreach($inboxes as $id => $inbox_id) {
            $where[]['d.folder_id'] = (int) $inbox_id;
        }

        $select = array(
            'c.id', 'c.subject', 'c.poster', 'c.poster_id', 'c.num_replies', 'c.last_post', 'c.last_poster', 'c.last_post_id',
            'd.viewed',
            'poster_gid' => 'u.group_id', 'u.email',
            'last_poster_id' => 'u2.id', 'last_poster_gid' => 'u2.group_id'
        );
        $result = DB::for_table('pms_conversations')
            ->select_many($select)
            ->table_alias('c')
            ->inner_join('pms_data', array('c.id', '=', 'd.conversation_id'), 'd')
            // ->left_outer_join('pms_messages', array('m.poster_id', '=', 'c.last_post_id'), 'm')
            ->left_outer_join('users', array('u.id', '=', 'c.poster_id'), 'u')
            ->left_outer_join('users', array('u2.username', '=', 'c.last_poster'), 'u2', true)
            ->where('d.user_id', $uid)
            ->where('d.deleted', 0)
            ->where_any_is($where)
            ->order_by_desc('c.last_post')
            ->limit($limit)
            ->offset($start)
            ->find_array();

        foreach($result as $key => $conversation) {
            $receivers = DB::for_table('pms_data')
                            ->table_alias('d')
                            ->select(array('d.user_id', 'u.username'))
                            ->left_outer_join('users', array('u.id', '=', 'd.user_id'), 'u')
                            ->where('d.conversation_id', $conversation['id'])
                            ->find_array();
            if (is_array($receivers)) {
                foreach ($receivers as $receiver) {
                    $result[$key]['receivers'][$receiver['user_id']] = $receiver['username'];
                }
            }
        }
        return $result;
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
        DB::for_table('pms_data')
            ->where('user_id', $uid)
            ->where_in('conversation_id', $convers)
            ->find_result_set()
            ->set('deleted', 1)
            ->save();

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


    // Return false if the conv has been deleted, or if the uid has no rights to access it
    public function getConversation($conv_id = null, $uid = null)
    {
        $result = DB::for_table('pms_conversations')
                    ->table_alias('c')
                    ->inner_join('pms_data', array('d.conversation_id', '=', 'c.id'), 'd')
                    ->where_any_is(array(array('c.poster_id' => $uid),
                                         array('d.user_id' => $uid)))
                    ->where('c.id', $conv_id)
                    ->find_one();
        return $result;
    }

    public function addMessage(array $data = array(), $conv_id = null, array $uid = array())
    {
        $add = DB::for_table('pms_messages')
                    ->create()
                    ->set($data)
                    ->set('conversation_id', $conv_id);
        $add->save();
        $update = DB::for_table('pms_conversations')
                    ->find_one($conv_id)
                    ->set(array(
                        'first_post_id'	=>	$add->id(),
                        'last_post_id'	=>	$add->id(),
                    ))
                    ->save();
        if (!empty($uid)) {
            foreach ($uid as $user) {
                $notifs = DB::for_table('pms_data')
                        ->create()
                        ->set(array(
                                'conversation_id'	=>	$conv_id,
                                'user_id'	=>	$user,
                                'viewed'	=>	(($user == $this->feather->user->id) ? 1 : 0)))
                        ->save();
            }
        } else {
            $notifs = DB::for_table('pms_data')
                    ->where('conversation_id', $conv_id)
                    ->find_result_set();

            $notifs->set('viewed', (($uid == $this->feather->user->id) ? 1 : 0))
                    ->save();
        }

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

    public function getMessagesFromConversation($conv_id = null, $uid = null, $limit = 50, $start = 0)
    {
        $result = DB::for_table('pms_messages')
                    ->table_alias('m')
                    ->where('m.conversation_id', $conv_id)
                    ->order_by_asc('sent')
                    ->limit($limit)
                    ->find_many();
        return $result;
    }
}
