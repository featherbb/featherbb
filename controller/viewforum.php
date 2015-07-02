<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class viewforum
{
    public function display($id, $name = null, $page = null)
    {
        global $feather, $lang_common, $lang_forum, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        }

        if ($id < 1) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Load the viewforum.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/forum.php';

        // Load the viewforum.php model file
        require FEATHER_ROOT.'model/viewforum.php';

        // Fetch some informations about the forum
        $cur_forum = get_info_forum($id);

        // Is this a redirect forum? In that case, redirect!
        if ($cur_forum['redirect_url'] != '') {
            header('Location: '.$cur_forum['redirect_url']);
            exit;
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();
$is_admmod = ($feather_user['g_id'] == PUN_ADMIN || ($feather_user['g_moderator'] == '1' && array_key_exists($feather_user['username'], $mods_array))) ? true : false;

$sort_by = sort_forum_by($cur_forum['sort_by']);

        // Can we or can we not post new topics?
        if (($cur_forum['post_topics'] == '' && $feather_user['g_post_topics'] == '1') || $cur_forum['post_topics'] == '1' || $is_admmod) {
            $post_link = "\t\t\t".'<p class="postlink conr"><a href="'.get_link('post/new-topic/'.$id.'/').'">'.$lang_forum['Post topic'].'</a></p>'."\n";
        } else {
            $post_link = '';
        }

        // Determine the topic offset (based on $page)
        $num_pages = ceil($cur_forum['num_topics'] / $feather_user['disp_topics']);

$p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
$start_from = $feather_user['disp_topics'] * ($p - 1);
$url_forum = url_friendly($cur_forum['forum_name']);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate($num_pages, $p, 'forum/'.$id.'/'.$url_forum.'/#');

        $forum_actions = get_forum_actions($id, $feather_config['o_forum_subscriptions'], $cur_forum['is_subscribed']);


        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), pun_htmlspecialchars($cur_forum['forum_name']));
        define('PUN_ALLOW_INDEX', 1);
        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'viewforum');
        }

        require FEATHER_ROOT.'include/header.php';

        $feather->render('header.php', array(
                            'lang_common' => $lang_common,
                            'page_title' => $page_title,
                            'p' => $p,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            '_SERVER'    =>    $_SERVER,
                            'page_head'        =>    get_page_head($id, $num_pages, $p, $url_forum),
                            'navlinks'        =>    $navlinks,
                            'page_info'        =>    $page_info,
                            'db'        =>    $db,
                            )
                    );

                    // Print topics
                    $forum_data = print_topics($id, $sort_by, $start_from);

        $feather->render('viewforum.php', array(
                            'id' => $id,
                            'forum_data' => $forum_data,
                            'lang_common' => $lang_common,
                            'lang_forum' => $lang_forum,
                            'cur_forum' => $cur_forum,
                            'paging_links' => $paging_links,
                            'post_link' => $post_link,
                            'is_admmod' => $is_admmod,
                            'start_from' => $start_from,
                            'url_forum' => $url_forum,
                            'forum_actions' => $forum_actions,
                            )
                    );

        $feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            'feather_start' => $feather_start,
                            'footer_style' => 'viewforum',
                            'forum_id' => $id,
                            )
                    );

        require FEATHER_ROOT.'include/footer.php';
    }
}
