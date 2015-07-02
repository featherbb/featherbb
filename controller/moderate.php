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
    public function gethostpost($pid)
    {
        global $lang_common, $feather_user;

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        }

        // Load the viewforum.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/forum.php';

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the moderate.php model file
        require FEATHER_ROOT.'model/moderate.php';

        // This particular function doesn't require forum-based moderator access. It can be used
        // by all moderators and admins
        if (!$feather_user['is_admmod']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        display_ip_address_post($pid);
    }

    public function gethostip($ip)
    {
        global $lang_common, $feather_user;

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        }

        // Load the viewforum.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/forum.php';

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the moderate.php model file
        require FEATHER_ROOT.'model/moderate.php';

        // This particular function doesn't require forum-based moderator access. It can be used
        // by all moderators and admins
        if (!$feather_user['is_admmod']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        display_ip_info($ip);
    }

    public function moderatetopic($id = null, $fid = null, $action = null, $param = null)
    {
        global $feather, $lang_common, $lang_topic, $lang_misc, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        }

        // Load the viewforum.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/forum.php';

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the moderate.php model file
        require FEATHER_ROOT.'model/moderate.php';

        // This particular function doesn't require forum-based moderator access. It can be used
        // by all moderators and admins
        if ($action == 'get_host') {
            if (!$feather_user['is_admmod']) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            display_ip_address($feather);
        }

                    // Make sure that only admmods allowed access this page
                    $moderators = get_moderators($id);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if ($feather_user['g_id'] != PUN_ADMIN && ($feather_user['g_moderator'] == '0' || !array_key_exists($feather_user['username'], $mods_array))) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        $result = $db->query('SELECT subject FROM '.$db->prefix.'topics WHERE id='.$id) or error('Unable to get subject', __FILE__, __LINE__, $db->error());
        $subject_tid = $db->result($result);
        if (!$db->num_rows($result)) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }
        $url_subject = url_friendly($subject_tid);
        $url_referer = array(
                            get_link_r('topic/'.$id.'/'),
                            get_link_r('topic/'.$id.'/'.$url_subject.'/'),
                            get_link_r('topic/'.$id.'/'.$url_subject.'/page/'.$param.'/'),
                            get_link_r('post/'.$param.'/#p'.$param),
                            );

        // Stick a topic
        if ($action == 'stick') {
            confirm_referrer($url_referer);

            $db->query('UPDATE '.$db->prefix.'topics SET sticky=\'1\' WHERE id='.$id.' AND forum_id='.$fid) or error('Unable to stick topic', __FILE__, __LINE__, $db->error());

            redirect(get_link('topic/'.$id.'/'), $lang_misc['Stick topic redirect']);
        }


        // Unstick a topic
        if ($action == 'unstick') {
            confirm_referrer($url_referer);

            $db->query('UPDATE '.$db->prefix.'topics SET sticky=\'0\' WHERE id='.$id.' AND forum_id='.$fid) or error('Unable to unstick topic', __FILE__, __LINE__, $db->error());

            redirect(get_link('topic/'.$id.'/'), $lang_misc['Unstick topic redirect']);
        }

        // Open a topic
        if ($action == 'open') {
            confirm_referrer($url_referer);

            $db->query('UPDATE '.$db->prefix.'topics SET closed=\'0\' WHERE id='.$id.' AND forum_id='.$fid) or error('Unable to unstick topic', __FILE__, __LINE__, $db->error());

            redirect(get_link('topic/'.$id.'/'), $lang_misc['Unstick topic redirect']);
        }

        // Close a topic
        if ($action == 'close') {
            confirm_referrer($url_referer);

            $db->query('UPDATE '.$db->prefix.'topics SET closed=\'1\' WHERE id='.$id.' AND forum_id='.$fid) or error('Unable to unstick topic', __FILE__, __LINE__, $db->error());

            redirect(get_link('topic/'.$id.'/'), $lang_misc['Unstick topic redirect']);
        }

        $cur_topic = get_topic_info($fid, $id);

        // Determine the post offset (based on $_GET['p'])
        $num_pages = ceil(($cur_topic['num_replies'] + 1) / $feather_user['disp_posts']);

        $p = (!isset($param) || $param <= 1 || $param > $num_pages) ? 1 : intval($param);

        $start_from = $feather_user['disp_posts'] * ($p - 1);

                    // Move a topic - send a POST after
                    if ($action == 'move') {
                        // Check if there are enough forums to move the topic
                            check_move_possible();

                        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_misc['Moderate']);
                        if (!defined('PUN_ACTIVE_PAGE')) {
                            define('PUN_ACTIVE_PAGE', 'moderate');
                        }
                        require FEATHER_ROOT.'include/header.php';

                        $feather->render('header.php', array(
                                    'lang_common' => $lang_common,
                                    'page_title' => $page_title,
                                    'p' => $p,
                                    'feather_user' => $feather_user,
                                    'feather_config' => $feather_config,
                                    '_SERVER'    =>    $_SERVER,
                                    'page_head'        =>    '',
                                    'navlinks'        =>    $navlinks,
                                    'page_info'        =>    $page_info,
                                    'db'        =>    $db,
                                    )
                            );

                        $feather->render('moderate/move_topics.php', array(
                                    'action'    =>    'single',
                                    'id'    =>    $id,
                                    'topics'    =>    $id,
                                    'lang_misc'    =>    $lang_misc,
                                    'lang_common'    =>    $lang_common,
                                    )
                            );

                        $feather->render('footer.php', array(
                                    'lang_common' => $lang_common,
                                    'feather_user' => $feather_user,
                                    'feather_config' => $feather_config,
                                    'feather_start' => $feather_start,
                                    'footer_style' => 'moderate',
                                    'forum_id' => $id,
                                    )
                            );

                        require FEATHER_ROOT.'include/footer.php';
                    }

                    // Moderate a topic
                    if ($action == 'moderate') {

                            // Delete one or more posts
                            if ($feather->request->post('delete_posts') || $feather->request->post('delete_posts_comply')) {
                                $posts = delete_posts($feather, $id, $fid, $p);

                                $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_misc['Moderate']);
                                if (!defined('PUN_ACTIVE_PAGE')) {
                                    define('PUN_ACTIVE_PAGE', 'moderate');
                                }
                                require FEATHER_ROOT.'include/header.php';

                                $feather->render('header.php', array(
                                            'lang_common' => $lang_common,
                                            'page_title' => $page_title,
                                            'p' => $p,
                                            'feather_user' => $feather_user,
                                            'feather_config' => $feather_config,
                                            '_SERVER'    =>    $_SERVER,
                                            'page_head'        =>    '',
                                            'navlinks'        =>    $navlinks,
                                            'page_info'        =>    $page_info,
                                            'db'        =>    $db,
                                            )
                                    );

                                $feather->render('moderate/delete_posts.php', array(
                                    'lang_common' => $lang_common,
                                    'lang_misc' => $lang_misc,
                                    'id' => $id,
                                    'posts' => $posts,
                                    )
                            );

                                $feather->render('footer.php', array(
                                            'lang_common' => $lang_common,
                                            'feather_user' => $feather_user,
                                            'feather_config' => $feather_config,
                                            'feather_start' => $feather_start,
                                            'footer_style' => 'moderate',
                                            'forum_id' => $id,
                                            )
                                    );

                                require FEATHER_ROOT.'include/footer.php';
                            }
                        if ($feather->request->post('split_posts') || $feather->request->post('split_posts_comply')) {
                            $posts = split_posts($feather, $id, $fid, $p);

                            $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_misc['Moderate']);
                            $focus_element = array('subject','new_subject');
                            if (!defined('PUN_ACTIVE_PAGE')) {
                                define('PUN_ACTIVE_PAGE', 'moderate');
                            }
                            require FEATHER_ROOT.'include/header.php';

                            $feather->render('header.php', array(
                                            'lang_common' => $lang_common,
                                            'page_title' => $page_title,
                                            'p' => $p,
                                            'feather_user' => $feather_user,
                                            'feather_config' => $feather_config,
                                            '_SERVER'    =>    $_SERVER,
                                            'page_head'        =>    '',
                                            'navlinks'        =>    $navlinks,
                                            'page_info'        =>    $page_info,
                                            'db'        =>    $db,
                                            )
                                    );

                            $feather->render('moderate/split_posts.php', array(
                                    'lang_common' => $lang_common,
                                    'lang_misc' => $lang_misc,
                                    'id' => $id,
                                    'posts' => $posts,
                                    )
                            );

                            $feather->render('footer.php', array(
                                            'lang_common' => $lang_common,
                                            'feather_user' => $feather_user,
                                            'feather_config' => $feather_config,
                                            'feather_start' => $feather_start,
                                            'footer_style' => 'moderate',
                                            'forum_id' => $id,
                                            )
                                    );

                            require FEATHER_ROOT.'include/footer.php';
                        }

                            // Show the moderate posts view

                            // Load the viewtopic.php language file
                            require FEATHER_ROOT.'lang/'.$feather_user['language'].'/topic.php';

                            // Used to disable the Move and Delete buttons if there are no replies to this topic
                            $button_status = ($cur_topic['num_replies'] == 0) ? ' disabled="disabled"' : '';

                            /*if (isset($_GET['action']) && $_GET['action'] == 'all') {
                                    $feather_user['disp_posts'] = $cur_topic['num_replies'] + 1;
                            }*/

                            // Generate paging links
                            $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'moderate/topic/'.$id.'/forum/'.$fid.'/action/moderate/#');

                        if ($feather_config['o_censoring'] == '1') {
                            $cur_topic['subject'] = censor_words($cur_topic['subject']);
                        }

                        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), pun_htmlspecialchars($cur_topic['forum_name']), pun_htmlspecialchars($cur_topic['subject']));
                        if (!defined('PUN_ACTIVE_PAGE')) {
                            define('PUN_ACTIVE_PAGE', 'moderate');
                        }
                        require FEATHER_ROOT.'include/header.php';

                        $feather->render('header.php', array(
                                    'lang_common' => $lang_common,
                                    'page_title' => $page_title,
                                    'p' => $p,
                                    'feather_user' => $feather_user,
                                    'feather_config' => $feather_config,
                                    '_SERVER'    =>    $_SERVER,
                                    'page_head'        =>    '',
                                    'navlinks'        =>    $navlinks,
                                    'page_info'        =>    $page_info,
                                    'db'        =>    $db,
                                    )
                            );

                        $feather->render('moderate/posts_view.php', array(
                                    'lang_common' => $lang_common,
                                    'lang_topic' => $lang_topic,
                                    'lang_misc' => $lang_misc,
                                    'cur_topic' => $cur_topic,
                                    'url_topic' => url_friendly($cur_topic['subject']),
                                    'url_forum' => url_friendly($cur_topic['forum_name']),
                                    'fid' => $fid,
                                    'id' => $id,
                                    'paging_links' => $paging_links,
                                    'post_data' => display_posts_view($id, $start_from),
                                    'button_status' => $button_status,
                                    'start_from' => $start_from,
                                    )
                            );

                        $feather->render('footer.php', array(
                                    'lang_common' => $lang_common,
                                    'feather_user' => $feather_user,
                                    'feather_config' => $feather_config,
                                    'feather_start' => $feather_start,
                                    'footer_style' => 'moderate',
                                    'forum_id' => $id,
                                    )
                            );

                        require FEATHER_ROOT.'include/footer.php';
                    }
    }

    public function display($id, $name = null, $page = null)
    {
        global $feather, $lang_common, $lang_forum, $lang_misc, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        }

                    // Load the viewforum.php language file
                    require FEATHER_ROOT.'lang/'.$feather_user['language'].'/forum.php';

                    // Load the misc.php language file
                    require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

                    // Load the moderate.php model file
                    require FEATHER_ROOT.'model/moderate.php';

                    // Make sure that only admmods allowed access this page
                    $moderators = get_moderators($id);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if ($feather_user['g_id'] != PUN_ADMIN && ($feather_user['g_moderator'] == '0' || !array_key_exists($feather_user['username'], $mods_array))) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

                    // Fetch some info about the forum
                    $cur_forum = get_forum_info($id);

                    // Is this a redirect forum? In that case, abort!
                    if ($cur_forum['redirect_url'] != '') {
                        message($lang_common['Bad request'], false, '404 Not Found');
                    }

        $sort_by = forum_sort_by($cur_forum['sort_by']);

                    // Determine the topic offset (based on $_GET['p'])
                    $num_pages = ceil($cur_forum['num_topics'] / $feather_user['disp_topics']);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $feather_user['disp_topics'] * ($p - 1);
        $url_forum = url_friendly($cur_forum['forum_name']);

                    // Generate paging links
                    $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'moderate/forum/'.$id.'/#');

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), pun_htmlspecialchars($cur_forum['forum_name']));
        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'moderate');
        }
        require FEATHER_ROOT.'include/header.php';

        $feather->render('header.php', array(
                            'lang_common' => $lang_common,
                            'page_title' => $page_title,
                            'p' => $p,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            '_SERVER'    =>    $_SERVER,
                            'page_head'        =>    '',
                            'navlinks'        =>    $navlinks,
                            'page_info'        =>    $page_info,
                            'db'        =>    $db,
                            )
                    );

        $topic_data = display_topics($id, $sort_by, $start_from);

        $feather->render('moderate/moderator_forum.php', array(
                            'lang_common' => $lang_common,
                            'lang_misc' => $lang_misc,
                            'id' => $id,
                            'p' => $p,
                            'url_forum' => $url_forum,
                            'cur_forum' => $cur_forum,
                            'paging_links' => $paging_links,
                            'feather_config' => $feather_config,
                            'lang_forum' => $lang_forum,
                            'topic_data' => $topic_data,
                            'start_from' => $start_from,
                            )
                    );

        $feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            'feather_start' => $feather_start,
                            'footer_style' => 'moderate',
                            'forum_id' => $id,
                            )
                    );

        require FEATHER_ROOT.'include/footer.php';
    }

    public function dealposts($fid)
    {
        global $feather, $lang_common, $lang_forum, $lang_topic, $lang_misc, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        }

            // Load the viewforum.php language file
            require FEATHER_ROOT.'lang/'.$feather_user['language'].'/forum.php';

            // Load the misc.php language file
            require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

            // Load the moderate.php model file
            require FEATHER_ROOT.'model/moderate.php';

            // Make sure that only admmods allowed access this page
            $moderators = get_moderators($fid);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if ($feather_user['g_id'] != PUN_ADMIN && ($feather_user['g_moderator'] == '0' || !array_key_exists($feather_user['username'], $mods_array))) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

            // Move one or more topics
            if ($feather->request->post('move_topics') || $feather->request->post('move_topics_to')) {
                if ($feather->request->post('move_topics_to')) {
                    move_topics_to($feather, $fid);
                }

                $topics = $feather->request->post('topics') ? $feather->request->post('topics') : array();
                if (empty($topics)) {
                    message($lang_misc['No topics selected']);
                }

                $topics = implode(',', array_map('intval', array_keys($topics)));

                    // Check if there are enough forums to move the topic
                    check_move_possible();

                $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_misc['Moderate']);
                if (!defined('PUN_ACTIVE_PAGE')) {
                    define('PUN_ACTIVE_PAGE', 'moderate');
                }
                require FEATHER_ROOT.'include/header.php';

                $feather->render('header.php', array(
                            'lang_common' => $lang_common,
                            'page_title' => $page_title,
                            'p' => $p,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            '_SERVER'    =>    $_SERVER,
                            'page_head'        =>    '',
                            'navlinks'        =>    $navlinks,
                            'page_info'        =>    $page_info,
                            'db'        =>    $db,
                            )
                    );


                $feather->render('moderate/move_topics.php', array(
                            'action'    =>    'multi',
                            'id'    =>    $fid,
                            'topics'    =>    $topics,
                            'lang_misc'    =>    $lang_misc,
                            'lang_common'    =>    $lang_common,
                            )
                    );

                $feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            'feather_start' => $feather_start,
                            'footer_style' => 'moderate',
                            'forum_id' => $fid,
                            )
                    );

                require FEATHER_ROOT.'include/footer.php';
            }

            // Merge two or more topics
            elseif ($feather->request->post('merge_topics') || $feather->request->post('merge_topics_comply')) {
                if ($feather->request->post('merge_topics_comply')) {
                    merge_topics($feather, $fid);
                }

                $topics = $feather->request->post('topics') ? $feather->request->post('topics') : array();
                if (count($topics) < 2) {
                    message($lang_misc['Not enough topics selected']);
                }

                $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_misc['Moderate']);
                if (!defined('PUN_ACTIVE_PAGE')) {
                    define('PUN_ACTIVE_PAGE', 'moderate');
                }
                require FEATHER_ROOT.'include/header.php';

                $feather->render('header.php', array(
                            'lang_common' => $lang_common,
                            'page_title' => $page_title,
                            'p' => $p,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            '_SERVER'    =>    $_SERVER,
                            'page_head'        =>    '',
                            'navlinks'        =>    $navlinks,
                            'page_info'        =>    $page_info,
                            'db'        =>    $db,
                            )
                    );

                $feather->render('moderate/merge_topics.php', array(
                            'id'    =>    $fid,
                            'topics'    =>    $topics,
                            'lang_misc'    =>    $lang_misc,
                            'lang_common'    =>    $lang_common,
                            )
                    );

                $feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            'feather_start' => $feather_start,
                            'footer_style' => 'moderate',
                            'forum_id' => $fid,
                            )
                    );

                require FEATHER_ROOT.'include/footer.php';
            }

            // Delete one or more topics
            elseif ($feather->request->post('delete_topics') || $feather->request->post('delete_topics_comply')) {
                $topics = $feather->request->post('topics') ? $feather->request->post('topics') : array();
                if (empty($topics)) {
                    message($lang_misc['No topics selected']);
                }

                if (isset($_POST['delete_topics_comply'])) {
                    delete_topics($topics, $fid);
                }

                $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_misc['Moderate']);
                if (!defined('PUN_ACTIVE_PAGE')) {
                    define('PUN_ACTIVE_PAGE', 'moderate');
                }
                require FEATHER_ROOT.'include/header.php';

                $feather->render('header.php', array(
                            'lang_common' => $lang_common,
                            'page_title' => $page_title,
                            'p' => $p,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            '_SERVER'    =>    $_SERVER,
                            'page_head'        =>    '',
                            'navlinks'        =>    $navlinks,
                            'page_info'        =>    $page_info,
                            'db'        =>    $db,
                            )
                    );


                $feather->render('moderate/delete_topics.php', array(
                            'id'    =>    $fid,
                            'topics'    =>    $topics,
                            'lang_misc'    =>    $lang_misc,
                            'lang_common'    =>    $lang_common,
                            )
                    );

                $feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            'feather_start' => $feather_start,
                            'footer_style' => 'moderate',
                            'forum_id' => $fid,
                            )
                    );

                require FEATHER_ROOT.'include/footer.php';
            }


            // Open or close one or more topics
            elseif ($feather->request->post('open') || $feather->request->post('close')) {
                $action = ($feather->request->post('open')) ? 0 : 1;

                    // There could be an array of topic IDs in $_POST
                    if ($feather->request->post('open') || $feather->request->post('close')) {
                        confirm_referrer(array(
                                    get_link_r('moderate/forum/'.$fid.'/page/'.$feather->request->post('page').'/'),
                                    get_link_r('moderate/forum/'.$fid.'/'),
                                    ));

                        $topics = $feather->request->post('topics') ? @array_map('intval', @array_keys($feather->request->post('topics'))) : array();
                        if (empty($topics)) {
                            message($lang_misc['No topics selected']);
                        }

                        $db->query('UPDATE '.$db->prefix.'topics SET closed='.$action.' WHERE id IN('.implode(',', $topics).') AND forum_id='.$fid) or error('Unable to close topics', __FILE__, __LINE__, $db->error());

                        $redirect_msg = ($action) ? $lang_misc['Close topics redirect'] : $lang_misc['Open topics redirect'];
                        redirect(get_link('moderate/forum/'.$fid.'/'), $redirect_msg);
                    }
            }
    }
}
