<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
use DB;

class Delete
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
    }

    public function get_info_delete($id)
    {
        $id = $this->hook->fire('get_info_delete_start', $id);

        $query['select'] = array('fid' => 'f.id', 'f.forum_name', 'f.moderators', 'f.redirect_url', 'fp.post_replies',  'fp.post_topics', 'tid' => 't.id', 't.subject', 't.first_post_id', 't.closed', 'p.poster', 'p.posted', 'p.poster_id', 'p.message', 'p.hide_smilies');
        $query['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $query = DB::for_table('posts')
            ->table_alias('p')
            ->select_many($query['select'])
            ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($query['where'])
            ->where('p.id', $id);

        $query = $this->hook->fireDB('get_info_delete_query', $query);

        $query = $query->find_one();

        if (!$query) {
            throw new \FeatherBB\Core\Error(__('Bad request'), 404);
        }

        return $query;
    }

    public function handle_deletion($is_topic_post, $id, $tid, $fid)
    {
        $this->hook->fire('handle_deletion_start', $is_topic_post, $id, $tid, $fid);

        if ($is_topic_post) {
            $this->hook->fire('handle_deletion_topic_post', $tid, $fid);

            // Delete the topic and all of its posts
            self::topic($tid);
            Forum::update($fid);

            Url::redirect($this->feather->urlFor('Forum', array('id' => $fid)), __('Topic del redirect'));
        } else {
            $this->hook->fire('handle_deletion', $tid, $fid, $id);

            // Delete just this one post
            self::post($id, $tid);
            Forum::update($fid);

            // Redirect towards the previous post
            $post = DB::for_table('posts')
                ->select('id')
                ->where('topic_id', $tid)
                ->where_lt('id', $id)
                ->order_by_desc('id');

            $post = $this->hook->fireDB('handle_deletion_query', $post);

            $post = $post->find_one();

            Url::redirect(Url::get('post/'.$post['id'].'/#p'.$post['id']), __('Post del redirect'));
        }
    }

    //
    // Delete a topic and all of its posts
    //
    public static function topic($topic_id)
    {
        // Delete the topic and any redirect topics
        $where_delete_topic = array(
            array('id' => $topic_id),
            array('moved_to' => $topic_id)
        );

        DB::for_table('topics')
            ->where_any_is($where_delete_topic)
            ->delete_many();

        // Delete posts in topic
        DB::for_table('posts')
            ->where('topic_id', $topic_id)
            ->delete_many();

        // Delete any subscriptions for this topic
        DB::for_table('topic_subscriptions')
            ->where('topic_id', $topic_id)
            ->delete_many();
    }


    //
    // Delete a single post
    //
    public static function post($post_id, $topic_id)
    {
        $result = DB::for_table('posts')
            ->select_many('id', 'poster', 'posted')
            ->where('topic_id', $topic_id)
            ->order_by_desc('id')
            ->limit(2)
            ->find_many();

        $i = 0;
        foreach ($result as $cur_result) {
            if ($i == 0) {
                $last_id = $cur_result['id'];
            }
            else {
                $second_last_id = $cur_result['id'];
                $second_poster = $cur_result['poster'];
                $second_posted = $cur_result['posted'];
            }
            ++$i;
        }

        // Delete the post
        DB::for_table('posts')
            ->where('id', $post_id)
            ->find_one()
            ->delete();

        $search = new \FeatherBB\Core\Search();

        $search->strip_search_index($post_id);

        // Count number of replies in the topic
        $num_replies = DB::for_table('posts')->where('topic_id', $topic_id)->count() - 1;

        // If the message we deleted is the most recent in the topic (at the end of the topic)
        if ($last_id == $post_id) {
            // If there is a $second_last_id there is more than 1 reply to the topic
            if (isset($second_last_id)) {
                $update_topic = array(
                    'last_post'  => $second_posted,
                    'last_post_id'  => $second_last_id,
                    'last_poster'  => $second_poster,
                    'num_replies'  => $num_replies,
                );
                DB::for_table('topics')
                    ->where('id', $topic_id)
                    ->find_one()
                    ->set($update_topic)
                    ->save();
            } else {
                // We deleted the only reply, so now last_post/last_post_id/last_poster is posted/id/poster from the topic itself
                DB::for_table('topics')
                    ->where('id', $topic_id)
                    ->find_one()
                    ->set_expr('last_post', 'posted')
                    ->set_expr('last_post_id', 'id')
                    ->set_expr('last_poster', 'poster')
                    ->set('num_replies', $num_replies)
                    ->save();
            }
        } else {
            // Otherwise we just decrement the reply counter
            DB::for_table('topics')
                ->where('id', $topic_id)
                ->find_one()
                ->set('num_replies', $num_replies)
                ->save();
        }
    }

    //
    // Deletes any avatars owned by the specified user ID
    //
    public static function avatar($user_id)
    {
        $feather = \Slim\Slim::getInstance();

        $filetypes = array('jpg', 'gif', 'png');

        // Delete user avatar
        foreach ($filetypes as $cur_type) {
            if (file_exists($feather->forum_env['FEATHER_ROOT'].$feather->config['o_avatars_dir'].'/'.$user_id.'.'.$cur_type)) {
                @unlink($feather->forum_env['FEATHER_ROOT'].$feather->config['o_avatars_dir'].'/'.$user_id.'.'.$cur_type);
            }
        }
    }
}
