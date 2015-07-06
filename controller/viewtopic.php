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
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
    }
    
    public function display($id = null, $name = null, $page = null, $pid = null)
    {
        global $lang_common, $lang_post, $lang_topic, $feather_config, $feather_user, $db, $pd;

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        }

        // Load the viewtopic.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/topic.php';

        // Load the post.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/post.php';

        // Antispam feature
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // Load the viewtopic.php model file
        require_once FEATHER_ROOT.'model/viewtopic.php';

        // Fetch some informations about the topic TODO
        $cur_topic = get_info_topic($id);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
        $is_admmod = ($feather_user['g_id'] == FEATHER_ADMIN || ($feather_user['g_moderator'] == '1' && array_key_exists($feather_user['username'], $mods_array))) ? true : false;
        if ($is_admmod) {
            $admin_ids = get_admin_ids();
        }

        // Can we or can we not post replies?
        $post_link = get_post_link($id, $cur_topic['closed'], $cur_topic['post_replies'], $is_admmod);

        // Add/update this topic in our list of tracked topics
        if (!$feather_user['is_guest']) {
            $tracked_topics = get_tracked_topics();
            $tracked_topics['topics'][$id] = time();
            set_tracked_topics($tracked_topics);
        }

        // Determine the post offset (based on $_GET['p'])
        $num_pages = ceil(($cur_topic['num_replies'] + 1) / $feather_user['disp_posts']);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $feather_user['disp_posts'] * ($p - 1);

        $url_topic = url_friendly($cur_topic['subject']);
        $url_forum = url_friendly($cur_topic['forum_name']);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'topic/'.$id.'/'.$url_topic.'/#');


        if ($feather_config['o_censoring'] == '1') {
            $cur_topic['subject'] = censor_words($cur_topic['subject']);
        }

        $quickpost = is_quickpost($cur_topic['post_replies'], $cur_topic['closed'], $is_admmod);

        $subscraction = get_subscraction($cur_topic['is_subscribed'], $id);

        // Add relationship meta tags
        $page_head = get_page_head($id, $num_pages, $p, $url_topic);

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), pun_htmlspecialchars($cur_topic['forum_name']), pun_htmlspecialchars($cur_topic['subject']));
        define('FEATHER_ALLOW_INDEX', 1);

        define('FEATHER_ACTIVE_PAGE', 'viewtopic');

        require FEATHER_ROOT.'include/header.php';

        $forum_id = $cur_topic['forum_id'];

        require FEATHER_ROOT.'include/parser.php';

        $this->feather->render('viewtopic.php', array(
                            'id' => $id,
                            'p' => $p,
                            'post_data' => print_posts($id, $start_from, $cur_topic, $is_admmod),
                            'lang_common' => $lang_common,
                            'lang_topic' => $lang_topic,
                            'lang_post' => $lang_post,
                            'cur_topic'    =>    $cur_topic,
                            'subscraction'    =>    $subscraction,
                            'is_admmod'    =>    $is_admmod,
                            'feather_config' => $feather_config,
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
                            )
                    );
        
        $footer_style = 'viewtopic';
        $forum_id = $cur_topic['forum_id'];

        // Increment "num_views" for topic
        if ($feather_config['o_topic_views'] == '1') {
            $db->query('UPDATE '.$db->prefix.'topics SET num_views=num_views+1 WHERE id='.$id) or error('Unable to update topic', __FILE__, __LINE__, $db->error());
        }

        require FEATHER_ROOT.'include/footer.php';
    }

    public function viewpost($pid)
    {
        global $lang_common, $feather_config, $feather_user, $db;

        // Load the viewtopic.php model file
        require FEATHER_ROOT.'model/viewtopic.php';

        $post = redirect_to_post($pid);

        return Viewtopic::display($post['topic_id'], null, $post['get_p'], $pid); // TODO: $this->
    }

    public function action($id, $action)
    {
        global $lang_common, $feather_config, $feather_user, $db;

        // Load the viewtopic.php model file
        require FEATHER_ROOT.'model/viewtopic.php';

        handle_actions($id, $action);
    }
}
