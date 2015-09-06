<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

class Moderate
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Moderate();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/topic.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/forum.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/misc.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/post.mo');
    }

    public function gethostpost($pid)
    {
        if ($this->user->g_read_board == '0') {
            throw new \FeatherBB\Core\Error(__('No view'), 403);
        }

        $this->model->display_ip_address_post($pid);
    }

    public function gethostip($ip)
    {
        if ($this->user->g_read_board == '0') {
            throw new \FeatherBB\Core\Error(__('No view'), 403);
        }

        $this->model->display_ip_info($ip);
    }

    public function moderatetopic($id = null, $fid = null, $action = null, $param = null)
    {
        if ($this->user->g_read_board == '0') {
            throw new \FeatherBB\Core\Error(__('No view'), 403);
        }

        if ($action == 'get_host') {
            $this->model->display_ip_address();
        }

        // Make sure that only admmods allowed access this page
        $moderators = $this->model->get_moderators($id);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator == '0' || !array_key_exists($this->user->username, $mods_array))) {
            throw new \FeatherBB\Core\Error(__('No permission'), 403);
        }

        // Move one topic
        if ($this->request->post('move_topics') || $this->request->post('move_topics_to')) {
            if ($this->request->post('move_topics_to')) {
                $this->model->move_topics_to($fid, $id, $param);
            }

            $topics = $this->request->post('topics') ? $this->request->post('topics') : array();
            if (empty($topics)) {
                throw new \FeatherBB\Core\Error(__('No topics selected'), 400);
            }

            $topics = implode(',', array_map('intval', array_keys($topics)));

            // Check if there are enough forums to move the topic
            $this->model->check_move_possible();

            $this->feather->template->setPageInfo(array(
                    'title' => array(Utils::escape($this->config['o_board_title']), __('Moderate')),
                    'active_page' => 'moderate',
                    'action'    =>    'multi',
                    'id'    =>    $fid,
                    'topics'    =>    $topics,
                    'list_forums'   => $this->model->get_forum_list_move($fid),
                )
            )->addTemplate('moderate/move_topics.php')->display();
        }

        // Stick a topic
        if ($action == 'stick') {
            $this->model->stick_topic($id, $fid);
            Url::redirect($this->feather->urlFor('viewTopic', array('id' => $id)), __('Stick topic redirect'));
        }


        // Unstick a topic
        if ($action == 'unstick') {
            $this->model->unstick_topic($id, $fid);
            Url::redirect($this->feather->urlFor('viewTopic', array('id' => $id)), __('Unstick topic redirect'));
        }

        // Open a topic
        if ($action == 'open') {
            $this->model->open_topic($id, $fid);
            Url::redirect($this->feather->urlFor('viewTopic', array('id' => $id)), __('Open topic redirect'));
        }

        // Close a topic
        if ($action == 'close') {
            $this->model->close_topic($id, $fid);
            Url::redirect($this->feather->urlFor('viewTopic', array('id' => $id)), __('Close topic redirect'));
        }

        $cur_topic = $this->model->get_topic_info($fid, $id);

        // Determine the post offset (based on $_GET['p'])
        $num_pages = ceil(($cur_topic['num_replies'] + 1) / $this->user->disp_posts);

        $p = (!isset($param) || $param <= 1 || $param > $num_pages) ? 1 : intval($param);

        $start_from = $this->user->disp_posts * ($p - 1);

        // Move a topic - send a POST after
        if ($action == 'move') {
            // Check if there are enough forums to move the topic
            $this->model->check_move_possible();

            $this->feather->template->setPageInfo(array(
                        'title' => array(Utils::escape($this->config['o_board_title']), __('Moderate')),
                        'active_page' => 'moderate',
                        'page' => $p,
                        'action'    =>    'single',
                        'id'    =>    $id,
                        'topics'    =>    $id,
                        'list_forums'   => $this->model->get_forum_list_move($fid),
                        )
                )->addTemplate('moderate/move_topics.php')->display();
        }

        // Moderate a topic
        if ($action == 'moderate') {

                // Delete one or more posts
                if ($this->request->post('delete_posts') || $this->request->post('delete_posts_comply')) {
                    $posts = $this->model->delete_posts($id, $fid, $p);

                    $this->feather->template->setPageInfo(array(
                            'title' => array(Utils::escape($this->config['o_board_title']), __('Moderate')),
                            'active_page' => 'moderate',
                            'page' => $p,
                            'id' => $id,
                            'posts' => $posts,
                        ))
                        ->addTemplate('moderate/delete_posts.php')->display();
                }
            if ($this->request->post('split_posts') || $this->request->post('split_posts_comply')) {

                $this->feather->template->setPageInfo(array(
                        'title' => array(Utils::escape($this->config['o_board_title']), __('Moderate')),
                        'focus_element' => array('subject','new_subject'),
                        'page' => $p,
                        'active_page' => 'moderate',
                        'id' => $id,
                        'posts' => $this->model->split_posts($id, $fid, $p),
                        'list_forums' => $this->model->get_forum_list_split($id),
                        )
                )->addTemplate('moderate/split_posts.php')->display();

            }

                // Show the moderate posts view

                // Used to disable the Move and Delete buttons if there are no replies to this topic
                $button_status = ($cur_topic['num_replies'] == 0) ? ' disabled="disabled"' : '';

                /*if (isset($_GET['action']) && $_GET['action'] == 'all') {
                        $this->user->disp_posts = $cur_topic['num_replies'] + 1;
                }*/

            if ($this->config['o_censoring'] == '1') {
                $cur_topic['subject'] = censor_words($cur_topic['subject']);
            }

            $this->feather->template->setPageInfo(array(
                        'title' => array(Utils::escape($this->config['o_board_title']), Utils::escape($cur_topic['forum_name']), Utils::escape($cur_topic['subject'])),
                        'page' => $p,
                        'active_page' => 'moderate',
                        'cur_topic' => $cur_topic,
                        'url_topic' => Url::url_friendly($cur_topic['subject']),
                        'url_forum' => Url::url_friendly($cur_topic['forum_name']),
                        'fid' => $fid,
                        'id' => $id,
                        'paging_links' => '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, 'moderate/topic/'.$id.'/forum/'.$fid.'/action/moderate/#'),
                        'post_data' => $this->model->display_posts_view($id, $start_from),
                        'button_status' => $button_status,
                        'start_from' => $start_from,
                        )
                )->addTemplate('moderate/posts_view.php')->display();
        }
    }

    public function display($id, $name = null, $page = null)
    {
        if ($this->user->g_read_board == '0') {
            throw new \FeatherBB\Core\Error(__('No view'), 403);
        }

        // Make sure that only admmods allowed access this page
        $moderators = $this->model->get_moderators($id);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator == '0' || !array_key_exists($this->user->username, $mods_array))) {
            throw new \FeatherBB\Core\Error(__('No permission'), 403);
        }

        // Fetch some info about the forum
        $cur_forum = $this->model->get_forum_info($id);

        // Is this a redirect forum? In that case, abort!
        if ($cur_forum['redirect_url'] != '') {
            throw new \FeatherBB\Core\Error(__('Bad request'), '404');
        }

        $sort_by = $this->model->forum_sort_by($cur_forum['sort_by']);

        // Determine the topic offset (based on $_GET['p'])
        $num_pages = ceil($cur_forum['num_topics'] / $this->user->disp_topics);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->user->disp_topics * ($p - 1);
        $url_forum = Url::url_friendly($cur_forum['forum_name']);

        $this->feather->template->setPageInfo(array(
                            'title' => array(Utils::escape($this->config['o_board_title']), Utils::escape($cur_forum['forum_name'])),
                            'active_page' => 'moderate',
                            'page' => $p,
                            'id' => $id,
                            'p' => $p,
                            'url_forum' => $url_forum,
                            'cur_forum' => $cur_forum,
                            'paging_links' => '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, 'moderate/forum/'.$id.'/#'),
                            'topic_data' => $this->model->display_topics($id, $sort_by, $start_from),
                            'start_from' => $start_from,
                            )
                    )->addTemplate('moderate/moderator_forum.php')->display();
    }

    public function dealposts($fid)
    {
        if ($this->user->g_read_board == '0') {
            throw new \FeatherBB\Core\Error(__('No view'), 403);
        }

        // Make sure that only admmods allowed access this page
        $moderators = $this->model->get_moderators($fid);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator == '0' || !array_key_exists($this->user->username, $mods_array))) {
            throw new \FeatherBB\Core\Error(__('No permission'), 403);
        }

        // Move one or more topics
        if ($this->request->post('move_topics') || $this->request->post('move_topics_to')) {
            if ($this->request->post('move_topics_to')) {
                $this->model->move_topics_to($fid);
            }

            $topics = $this->request->post('topics') ? $this->request->post('topics') : array();
            if (empty($topics)) {
                throw new \FeatherBB\Core\Error(__('No topics selected'), 400);
            }

            // Check if there are enough forums to move the topic
            $this->model->check_move_possible();

            $this->feather->template->setPageInfo(array(
                        'action'    =>    'multi',
                        'title' => array(Utils::escape($this->config['o_board_title']), __('Moderate')),
                        'active_page' => 'moderate',
                        'id'    =>    $fid,
                        'topics'    =>    implode(',', array_map('intval', array_keys($topics))),
                        'list_forums'   => $this->model->get_forum_list_move($fid),
                        )
                )->addTemplate('moderate/move_topics.php')->display();
        }

        // Merge two or more topics
        elseif ($this->request->post('merge_topics') || $this->request->post('merge_topics_comply')) {
            if ($this->request->post('merge_topics_comply')) {
                $this->model->merge_topics($fid);
            }

            $topics = $this->request->post('topics') ? $this->request->post('topics') : array();
            if (count($topics) < 2) {
                throw new \FeatherBB\Core\Error(__('Not enough topics selected'), 400);
            }

            $this->feather->template->setPageInfo(array(
                        'title' => array(Utils::escape($this->config['o_board_title']), __('Moderate')),
                        'active_page' => 'moderate',
                        'id'    =>    $fid,
                        'topics'    =>    $topics,
                        )
                )->addTemplate('moderate/merge_topics.php')->display();
        }

        // Delete one or more topics
        elseif ($this->request->post('delete_topics') || $this->request->post('delete_topics_comply')) {
            $topics = $this->request->post('topics') ? $this->request->post('topics') : array();
            if (empty($topics)) {
                throw new \FeatherBB\Core\Error(__('No topics selected'), 400);
            }

            if ($this->request->post('delete_topics_comply')) {
                $this->model->delete_topics($topics, $fid);
            }

            $this->feather->template->setPageInfo(array(
                        'title' => array(Utils::escape($this->config['o_board_title']), __('Moderate')),
                        'active_page' => 'moderate',
                        'id'    =>    $fid,
                        'topics'    =>    $topics,
                        )
                )->addTemplate('moderate/delete_topics.php')->display();
        }


        // Open or close one or more topics
        elseif ($this->request->post('open') || $this->request->post('close')) {
            $action = ($this->request->post('open')) ? 0 : 1;

            // There could be an array of topic IDs in $_POST
            if ($this->request->post('open') || $this->request->post('close')) {
                $topics = $this->request->post('topics') ? @array_map('intval', @array_keys($this->request->post('topics'))) : array();
                if (empty($topics)) {
                    throw new \FeatherBB\Core\Error(__('No topics selected'), 400);
                }

                $this->model->close_multiple_topics($action, $topics, $fid);

                $redirect_msg = ($action) ? __('Close topics redirect') : __('Open topics redirect');
                Url::redirect($this->feather->urlFor('moderateForum', array('id' => $fid)), $redirect_msg);
            }
        }
    }
}
