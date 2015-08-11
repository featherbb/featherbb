<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class moderate
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\moderate();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function gethostpost($pid)
    {
        global $lang_common;

        if ($this->user->g_read_board == '0') {
            message($lang_common['No view'], '403');
        }

        // Load the viewforum.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/forum.php';

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        // This particular function doesn't require forum-based moderator access. It can be used
        // by all moderators and admins
        if (!$this->user->is_admmod) {
            message($lang_common['No permission'], '403');
        }

        $this->model->display_ip_address_post($pid);
    }

    public function gethostip($ip)
    {
        global $lang_common;

        if ($this->user->g_read_board == '0') {
            message($lang_common['No view'], '403');
        }

        // Load the viewforum.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/forum.php';

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        // This particular function doesn't require forum-based moderator access. It can be used
        // by all moderators and admins
        if (!$this->user->is_admmod) {
            message($lang_common['No permission'], '403');
        }

        $this->model->display_ip_info($ip);
    }

    public function moderatetopic($id = null, $fid = null, $action = null, $param = null)
    {
        global $lang_common, $lang_topic, $lang_misc;

        if ($this->user->g_read_board == '0') {
            message($lang_common['No view'], '403');
        }

        // Load the viewforum.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/forum.php';

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        // This particular function doesn't require forum-based moderator access. It can be used
        // by all moderators and admins
        if ($action == 'get_host') {
            if (!$this->user->is_admmod) {
                message($lang_common['No permission'], '403');
            }

            $this->model->display_ip_address();
        }

        // Make sure that only admmods allowed access this page
        $moderators = $this->model->get_moderators($id);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator == '0' || !array_key_exists($this->user->username, $mods_array))) {
            message($lang_common['No permission'], '403');
        }

        $url_subject = url_friendly($this->model->get_subject_tid($id));

        // Move one topic
        if ($this->request->post('move_topics') || $this->request->post('move_topics_to')) {
            if ($this->request->post('move_topics_to')) {
                $this->model->move_topics_to($fid, $id, $param);
            }

            $topics = $this->request->post('topics') ? $this->request->post('topics') : array();
            if (empty($topics)) {
                message($lang_misc['No topics selected']);
            }

            $topics = implode(',', array_map('intval', array_keys($topics)));

            // Check if there are enough forums to move the topic
            $this->model->check_move_possible();

            $page_title = array(feather_escape($this->config['o_board_title']), $lang_misc['Moderate']);

            define('FEATHER_ACTIVE_PAGE', 'moderate');

            $this->header->setTitle($page_title)->setPage($p)->display();

            $this->feather->render('moderate/move_topics.php', array(
                    'action'    =>    'multi',
                    'id'    =>    $fid,
                    'topics'    =>    $topics,
                    'lang_misc'    =>    $lang_misc,
                    'lang_common'    =>    $lang_common,
                    'list_forums'   => $this->model->get_forum_list_move($fid),
                )
            );

            $this->footer->display();
        }

        // Stick a topic
        if ($action == 'stick') {
            

            $this->model->stick_topic($id, $fid);
 
            redirect(get_link('topic/'.$id.'/'), $lang_misc['Stick topic redirect']);
        }


        // Unstick a topic
        if ($action == 'unstick') {
            

            $this->model->unstick_topic($id, $fid);

            redirect(get_link('topic/'.$id.'/'), $lang_misc['Unstick topic redirect']);
        }

        // Open a topic
        if ($action == 'open') {
            
            
            $this->model->open_topic($id, $fid);

            redirect(get_link('topic/'.$id.'/'), $lang_misc['Unstick topic redirect']);
        }

        // Close a topic
        if ($action == 'close') {
            

            $this->model->close_topic($id, $fid);

            redirect(get_link('topic/'.$id.'/'), $lang_misc['Unstick topic redirect']);
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

            $page_title = array(feather_escape($this->config['o_board_title']), $lang_misc['Moderate']);

            define('FEATHER_ACTIVE_PAGE', 'moderate');

            $this->header->setTitle($page_title)->setPage($p)->display();

            $this->feather->render('moderate/move_topics.php', array(
                        'action'    =>    'single',
                        'id'    =>    $id,
                        'topics'    =>    $id,
                        'lang_misc'    =>    $lang_misc,
                        'lang_common'    =>    $lang_common,
                        'list_forums'   => $this->model->get_forum_list_move($fid),
                        )
                );

            $this->footer->display();
        }

        // Moderate a topic
        if ($action == 'moderate') {

                // Delete one or more posts
                if ($this->request->post('delete_posts') || $this->request->post('delete_posts_comply')) {
                    $posts = $this->model->delete_posts($id, $fid, $p);

                    $page_title = array(feather_escape($this->config['o_board_title']), $lang_misc['Moderate']);

                    define('FEATHER_ACTIVE_PAGE', 'moderate');

                    $this->header->setTitle($page_title)->setPage($p)->display();

                    $this->feather->render('moderate/delete_posts.php', array(
                        'lang_common' => $lang_common,
                        'lang_misc' => $lang_misc,
                        'id' => $id,
                        'posts' => $posts,
                        )
                    );

                    $this->footer->display();
                }
            if ($this->request->post('split_posts') || $this->request->post('split_posts_comply')) {
                $posts = $this->model->split_posts($id, $fid, $p);

                $page_title = array(feather_escape($this->config['o_board_title']), $lang_misc['Moderate']);
                $focus_element = array('subject','new_subject');

                define('FEATHER_ACTIVE_PAGE', 'moderate');

                $this->header->setTitle($page_title)->setPage($p)->setFocusElement($focus_element)->display();

                $this->feather->render('moderate/split_posts.php', array(
                        'lang_common' => $lang_common,
                        'lang_misc' => $lang_misc,
                        'id' => $id,
                        'posts' => $posts,
                        'list_forums' => $this->model->get_forum_list_split($id),
                        )
                );

                $this->footer->display();
            }

                // Show the moderate posts view

                // Load the viewtopic.php language file
                require FEATHER_ROOT.'lang/'.$this->user->language.'/topic.php';

                // Used to disable the Move and Delete buttons if there are no replies to this topic
                $button_status = ($cur_topic['num_replies'] == 0) ? ' disabled="disabled"' : '';

                /*if (isset($_GET['action']) && $_GET['action'] == 'all') {
                        $this->user->disp_posts = $cur_topic['num_replies'] + 1;
                }*/

                // Generate paging links
                $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'moderate/topic/'.$id.'/forum/'.$fid.'/action/moderate/#');

            if ($this->config['o_censoring'] == '1') {
                $cur_topic['subject'] = censor_words($cur_topic['subject']);
            }

            $page_title = array(feather_escape($this->config['o_board_title']), feather_escape($cur_topic['forum_name']), feather_escape($cur_topic['subject']));

            define('FEATHER_ACTIVE_PAGE', 'moderate');

            $this->header->setTitle($page_title)->setPage($p)->setPagingLinks($paging_links)->display();

            $this->feather->render('moderate/posts_view.php', array(
                        'lang_common' => $lang_common,
                        'lang_topic' => $lang_topic,
                        'lang_misc' => $lang_misc,
                        'cur_topic' => $cur_topic,
                        'url_topic' => url_friendly($cur_topic['subject']),
                        'url_forum' => url_friendly($cur_topic['forum_name']),
                        'fid' => $fid,
                        'id' => $id,
                        'paging_links' => $paging_links,
                        'post_data' => $this->model->display_posts_view($id, $start_from),
                        'button_status' => $button_status,
                        'start_from' => $start_from,
                        )
                );

            $this->footer->display();
        }
    }

    public function display($id, $name = null, $page = null)
    {
        global $lang_common, $lang_forum, $lang_misc;

        if ($this->user->g_read_board == '0') {
            message($lang_common['No view'], '403');
        }

        // Load the viewforum.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/forum.php';

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        // Make sure that only admmods allowed access this page
        $moderators = $this->model->get_moderators($id);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator == '0' || !array_key_exists($this->user->username, $mods_array))) {
            message($lang_common['No permission'], '403');
        }

        // Fetch some info about the forum
        $cur_forum = $this->model->get_forum_info($id);

        // Is this a redirect forum? In that case, abort!
        if ($cur_forum['redirect_url'] != '') {
            message($lang_common['Bad request'], '404');
        }

        $sort_by = $this->model->forum_sort_by($cur_forum['sort_by']);

        // Determine the topic offset (based on $_GET['p'])
        $num_pages = ceil($cur_forum['num_topics'] / $this->user->disp_topics);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->user->disp_topics * ($p - 1);
        $url_forum = url_friendly($cur_forum['forum_name']);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'moderate/forum/'.$id.'/#');

        $page_title = array(feather_escape($this->config['o_board_title']), feather_escape($cur_forum['forum_name']));

        define('FEATHER_ACTIVE_PAGE', 'moderate');

        $this->header->setTitle($page_title)->setPage($p)->setPagingLinks($paging_links)->display();

        $this->feather->render('moderate/moderator_forum.php', array(
                            'lang_common' => $lang_common,
                            'lang_misc' => $lang_misc,
                            'id' => $id,
                            'p' => $p,
                            'url_forum' => $url_forum,
                            'cur_forum' => $cur_forum,
                            'paging_links' => $paging_links,
                            'feather_config' => $this->config,
                            'lang_forum' => $lang_forum,
                            'topic_data' => $this->model->display_topics($id, $sort_by, $start_from),
                            'start_from' => $start_from,
                            )
                    );

        $this->footer->display();
    }

    public function dealposts($fid)
    {
        global $lang_common, $lang_forum, $lang_topic, $lang_misc;

        if ($this->user->g_read_board == '0') {
            message($lang_common['No view'], '403');
        }

        // Load the viewforum.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/forum.php';

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        // Make sure that only admmods allowed access this page
        $moderators = $this->model->get_moderators($fid);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if ($this->user->g_id != FEATHER_ADMIN && ($this->user->g_moderator == '0' || !array_key_exists($this->user->username, $mods_array))) {
            message($lang_common['No permission'], '403');
        }

        // Move one or more topics
        if ($this->request->post('move_topics') || $this->request->post('move_topics_to')) {
            if ($this->request->post('move_topics_to')) {
                $this->model->move_topics_to($fid);
            }

            $topics = $this->request->post('topics') ? $this->request->post('topics') : array();
            if (empty($topics)) {
                message($lang_misc['No topics selected']);
            }

            $topics = implode(',', array_map('intval', array_keys($topics)));

            // Check if there are enough forums to move the topic
            $this->model->check_move_possible();

            $page_title = array(feather_escape($this->config['o_board_title']), $lang_misc['Moderate']);

            define('FEATHER_ACTIVE_PAGE', 'moderate');

            $this->header->setTitle($page_title)->display();

            $this->feather->render('moderate/move_topics.php', array(
                        'action'    =>    'multi',
                        'id'    =>    $fid,
                        'topics'    =>    $topics,
                        'lang_misc'    =>    $lang_misc,
                        'lang_common'    =>    $lang_common,
                        'list_forums'   => $this->model->get_forum_list_move($fid),
                        )
                );

            $this->footer->display();
        }

        // Merge two or more topics
        elseif ($this->request->post('merge_topics') || $this->request->post('merge_topics_comply')) {
            if ($this->request->post('merge_topics_comply')) {
                $this->model->merge_topics($fid);
            }

            $topics = $this->request->post('topics') ? $this->request->post('topics') : array();
            if (count($topics) < 2) {
                message($lang_misc['Not enough topics selected']);
            }

            $page_title = array(feather_escape($this->config['o_board_title']), $lang_misc['Moderate']);

            define('FEATHER_ACTIVE_PAGE', 'moderate');

            $this->header->setTitle($page_title)->display();

            $this->feather->render('moderate/merge_topics.php', array(
                        'id'    =>    $fid,
                        'topics'    =>    $topics,
                        'lang_misc'    =>    $lang_misc,
                        'lang_common'    =>    $lang_common,
                        )
                );

            $this->footer->display();
        }

        // Delete one or more topics
        elseif ($this->request->post('delete_topics') || $this->request->post('delete_topics_comply')) {
            $topics = $this->request->post('topics') ? $this->request->post('topics') : array();
            if (empty($topics)) {
                message($lang_misc['No topics selected']);
            }

            if ($this->request->post('delete_topics_comply')) {
                $this->model->delete_topics($topics, $fid);
            }

            $page_title = array(feather_escape($this->config['o_board_title']), $lang_misc['Moderate']);

            define('FEATHER_ACTIVE_PAGE', 'moderate');

            $this->header->setTitle($page_title)->display();

            $this->feather->render('moderate/delete_topics.php', array(
                        'id'    =>    $fid,
                        'topics'    =>    $topics,
                        'lang_misc'    =>    $lang_misc,
                        'lang_common'    =>    $lang_common,
                        )
                );

            $this->footer->display();
        }


        // Open or close one or more topics
        elseif ($this->request->post('open') || $this->request->post('close')) {
            $action = ($this->request->post('open')) ? 0 : 1;

            // There could be an array of topic IDs in $_POST
            if ($this->request->post('open') || $this->request->post('close')) {
                $topics = $this->request->post('topics') ? @array_map('intval', @array_keys($this->request->post('topics'))) : array();
                if (empty($topics)) {
                    message($lang_misc['No topics selected']);
                }

                $this->model->close_multiple_topics($action, $topics, $fid);

                $redirect_msg = ($action) ? $lang_misc['Close topics redirect'] : $lang_misc['Open topics redirect'];
                redirect(get_link('moderate/forum/'.$fid.'/'), $redirect_msg);
            }
        }
    }
}
