<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class delete
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
    }
    
    public function deletepost($id)
    {
        global $lang_common, $feather_config, $feather_user, $db, $lang_post, $pd;

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        }

        // Load the delete.php model file
        require FEATHER_ROOT.'model/delete.php';

        // Fetch some informations about the post, the topic and the forum
        $cur_post = get_info_delete($id);

        if ($feather_config['o_censoring'] == '1') {
            $cur_post['subject'] = censor_words($cur_post['subject']);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        $is_admmod = ($feather_user['g_id'] == FEATHER_ADMIN || ($feather_user['g_moderator'] == '1' && array_key_exists($feather_user['username'], $mods_array))) ? true : false;

        $is_topic_post = ($id == $cur_post['first_post_id']) ? true : false;

        // Do we have permission to edit this post?
        if (($feather_user['g_delete_posts'] == '0' ||
                ($feather_user['g_delete_topics'] == '0' && $is_topic_post) ||
                $cur_post['poster_id'] != $feather_user['id'] ||
                $cur_post['closed'] == '1') &&
                !$is_admmod) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        if ($is_admmod && $feather_user['g_id'] != FEATHER_ADMIN && in_array($cur_post['poster_id'], get_admin_ids())) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the delete.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/delete.php';


        if ($this->feather->request()->isPost()) {
            handle_deletion($is_topic_post, $id, $cur_post['tid'], $cur_post['fid']);
        }


        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_delete['Delete post']);

        define('FEATHER_ACTIVE_PAGE', 'delete');

        require FEATHER_ROOT.'include/header.php';

        require FEATHER_ROOT.'include/parser.php';
        $cur_post['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

        $this->feather->render('delete.php', array(
                            'lang_common' => $lang_common,
                            'lang_delete' => $lang_delete,
                            'cur_post' => $cur_post,
                            'id' => $id,
                            'is_topic_post' => $is_topic_post,
                            )
                    );

        require FEATHER_ROOT.'include/footer.php';
    }
}
