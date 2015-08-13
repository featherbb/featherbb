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
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\delete();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function deletepost($id)
    {
        global $lang_common, $lang_post, $pd;

        if ($this->user->g_read_board == '0') {
            message($lang_common['No view'], '403');
        }

        // Fetch some informations about the post, the topic and the forum
        $cur_post = $this->model->get_info_delete($id);

        if ($this->config['o_censoring'] == '1') {
            $cur_post['subject'] = censor_words($cur_post['subject']);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        $is_admmod = ($this->user->g_id == FEATHER_ADMIN || ($this->user->g_moderator == '1' && array_key_exists($this->user->username, $mods_array))) ? true : false;

        $is_topic_post = ($id == $cur_post['first_post_id']) ? true : false;

        // Do we have permission to edit this post?
        if (($this->user->g_delete_posts == '0' ||
                ($this->user->g_delete_topics == '0' && $is_topic_post) ||
                $cur_post['poster_id'] != $this->user->id ||
                $cur_post['closed'] == '1') &&
                !$is_admmod) {
            message($lang_common['No permission'], '403');
        }

        if ($is_admmod && $this->user->g_id != FEATHER_ADMIN && in_array($cur_post['poster_id'], get_admin_ids())) {
            message($lang_common['No permission'], '403');
        }

        // Load the delete.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/delete.php';


        if ($this->feather->request()->isPost()) {
            $this->model->handle_deletion($is_topic_post, $id, $cur_post['tid'], $cur_post['fid']);
        }


        $page_title = array(feather_escape($this->config['o_board_title']), $lang_delete['Delete post']);

        define('FEATHER_ACTIVE_PAGE', 'delete');

        $this->header->setTitle($page_title)->display();

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

        $this->footer->display();
    }
}
