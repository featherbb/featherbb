<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class edit
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
        $this->model = new \model\edit();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function editpost($id)
    {
        global $lang_common, $lang_prof_reg, $lang_post, $lang_register;

        if ($this->user->g_read_board == '0') {
            message($lang_common['No view'], '403');
        }

        // Fetch some informations about the post, the topic and the forum
        $cur_post = $this->model->get_info_edit($id);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        $is_admmod = ($this->user->g_id == FEATHER_ADMIN || ($this->user->g_moderator == '1' && array_key_exists($this->user->username, $mods_array))) ? true : false;

        $can_edit_subject = $id == $cur_post['first_post_id'];

        if ($this->config['o_censoring'] == '1') {
            $cur_post['subject'] = censor_words($cur_post['subject']);
            $cur_post['message'] = censor_words($cur_post['message']);
        }

        // Do we have permission to edit this post?
        if (($this->user->g_edit_posts == '0' || $cur_post['poster_id'] != $this->user->id || $cur_post['closed'] == '1') && !$is_admmod) {
            message($lang_common['No permission'], '403');
        }

        if ($is_admmod && $this->user->g_id != FEATHER_ADMIN && in_array($cur_post['poster_id'], get_admin_ids())) {
            message($lang_common['No permission'], '403');
        }

        // Load the post.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/post.php';
        
        // Load the bbeditor.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/bbeditor.php';

        // Start with a clean slate
        $errors = array();

        if ($this->feather->request()->isPost()) {
            // Let's see if everything went right
            $errors = $this->model->check_errors_before_edit($id, $can_edit_subject, $errors);

            // Setup some variables before post
            $post = $this->model->setup_variables($cur_post, $is_admmod, $can_edit_subject, $errors);

            // Did everything go according to plan?
            if (empty($errors) && !$this->request->post('preview')) {
                // Edit the post
                $this->model->edit_post($id, $can_edit_subject, $post, $cur_post, $is_admmod);

                redirect(get_link('post/'.$id.'/#p'.$id), $lang_post['Post redirect']);
            }
        } else {
            $post = '';
        }


        $page_title = array(feather_escape($this->config['o_board_title']), $lang_post['Edit post']);
        $required_fields = array('req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
        $focus_element = array('edit', 'req_message');

        define('FEATHER_ACTIVE_PAGE', 'edit');

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

        if ($this->request->post('preview')) {
            require_once FEATHER_ROOT.'include/parser.php';
            $preview_message = parse_message($post['message'], $post['hide_smilies']);
        } else {
            $preview_message = '';
        }

        $this->feather->render('edit.php', array(
                            'lang_common' => $lang_common,
                            'cur_post' => $cur_post,
                            'lang_post' => $lang_post,
                            'errors' => $errors,
                            'preview_message' => $preview_message,
                            'id' => $id,
                            'feather_config' => $this->config,
                            'feather_user' => $this->user,
                            'checkboxes' => $this->model->get_checkboxes($can_edit_subject, $is_admmod, $cur_post, 1),
                            'feather' => $this->feather,
                            'can_edit_subject' => $can_edit_subject,
                            'post' => $post,
                            'lang_bbeditor' => $lang_bbeditor,
                            )
                    );

        $this->footer->display();
    }
}
