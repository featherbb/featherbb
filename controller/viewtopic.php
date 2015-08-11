<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class viewtopic
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
        $this->model = new \model\viewtopic();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function display($id = null, $name = null, $page = null, $pid = null)
    {
        global $lang_common, $lang_post, $lang_topic, $lang_bbeditor, $pd;

        if ($this->user->g_read_board == '0') {
            message($lang_common['No view'], '403');
        }

        // Load the viewtopic.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/topic.php';

        // Load the post.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/post.php';

        // Antispam feature
        require FEATHER_ROOT.'lang/'.$this->user->language.'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // BBcode toolbar feature
        require FEATHER_ROOT.'lang/'.$this->user['language'].'/bbeditor.php';

        // Load the viewtopic.php model file
        require_once FEATHER_ROOT.'model/viewtopic.php';

        // Fetch some informations about the topic TODO
        $cur_topic = $this->model->get_info_topic($id);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
        $is_admmod = ($this->user->g_id == FEATHER_ADMIN || ($this->user->g_moderator == '1' && array_key_exists($this->user->username, $mods_array))) ? true : false;
        if ($is_admmod) {
            $admin_ids = get_admin_ids();
        }

        // Can we or can we not post replies?
        $post_link = $this->model->get_post_link($id, $cur_topic['closed'], $cur_topic['post_replies'], $is_admmod);

        // Add/update this topic in our list of tracked topics
        if (!$this->user->is_guest) {
            $tracked_topics = get_tracked_topics();
            $tracked_topics['topics'][$id] = time();
            set_tracked_topics($tracked_topics);
        }

        // Determine the post offset (based on $_GET['p'])
        $num_pages = ceil(($cur_topic['num_replies'] + 1) / $this->user->disp_posts);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->user->disp_posts * ($p - 1);

        $url_topic = url_friendly($cur_topic['subject']);
        $url_forum = url_friendly($cur_topic['forum_name']);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'topic/'.$id.'/'.$url_topic.'/#');


        if ($this->config['o_censoring'] == '1') {
            $cur_topic['subject'] = censor_words($cur_topic['subject']);
        }

        $quickpost = $this->model->is_quickpost($cur_topic['post_replies'], $cur_topic['closed'], $is_admmod);

        $subscraction = $this->model->get_subscraction($cur_topic['is_subscribed'], $id);

        // Add relationship meta tags
        $page_head = $this->model->get_page_head($id, $num_pages, $p, $url_topic);

        $page_title = array(feather_escape($this->config['o_board_title']), feather_escape($cur_topic['forum_name']), feather_escape($cur_topic['subject']));
        define('FEATHER_ALLOW_INDEX', 1);

        define('FEATHER_ACTIVE_PAGE', 'viewtopic');

        $this->header->setTitle($page_title)->setPage($p)->setPagingLinks($paging_links)->setPageHead($page_head)->display();

        $forum_id = $cur_topic['forum_id'];

        require FEATHER_ROOT.'include/parser.php';

        $this->feather->render('viewtopic.php', array(
                            'id' => $id,
                            'p' => $p,
                            'post_data' => $this->model->print_posts($id, $start_from, $cur_topic, $is_admmod),
                            'lang_common' => $lang_common,
                            'lang_topic' => $lang_topic,
                            'lang_post' => $lang_post,
                            'lang_bbeditor' => $lang_bbeditor,
                            'cur_topic'    =>    $cur_topic,
                            'subscraction'    =>    $subscraction,
                            'is_admmod'    =>    $is_admmod,
                            'feather_config' => $this->config,
                            'paging_links' => $paging_links,
                            'post_link' => $post_link,
                            'start_from' => $start_from,
                            'lang_antispam' => $lang_antispam,
                            'pid' => $pid,
                            'quickpost'        =>    $quickpost,
                            'index_questions'        =>    $index_questions,
                            'lang_antispam_questions'        =>    $lang_antispam_questions,
                            'url_forum'        =>    $url_forum,
                            'url_topic'        =>    $url_topic,
                            'feather'          =>    $this->feather,
                            )
                    );

        // Increment "num_views" for topic
        $this->model->increment_views($id);

        $this->footer->display('viewtopic', $id, $p, $pid, $cur_topic['forum_id'], $num_pages);
    }

    public function viewpost($pid)
    {
        global $lang_common;

        $post = $this->model->redirect_to_post($pid);

        return self::display($post['topic_id'], null, $post['get_p'], $pid);
    }

    public function action($id, $action)
    {
        global $lang_common;

        $this->model->handle_actions($id, $action);
    }
}
