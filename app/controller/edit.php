<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace App\Controller;

class Edit
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \App\Model\edit();
        load_textdomain('featherbb', FEATHER_ROOT.'app/lang/'.$this->user->language.'/register.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'app/lang/'.$this->user->language.'/prof_reg.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'app/lang/'.$this->user->language.'/post.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'app/lang/'.$this->user->language.'/bbeditor.mo');
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function editpost($id)
    {
        if ($this->user->g_read_board == '0') {
            throw new \FeatherBB\Error(__('No view'), 403);
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
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        if ($is_admmod && $this->user->g_id != FEATHER_ADMIN && in_array($cur_post['poster_id'], get_admin_ids())) {
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        // Start with a clean slate
        $errors = array();

        if ($this->feather->request()->isPost()) {
            // Let's see if everything went right
            $errors = $this->model->check_errors_before_edit($can_edit_subject, $errors);

            // Setup some variables before post
            $post = $this->model->setup_variables($cur_post, $is_admmod, $can_edit_subject, $errors);

            // Did everything go according to plan?
            if (empty($errors) && !$this->request->post('preview')) {
                // Edit the post
                $this->model->edit_post($id, $can_edit_subject, $post, $cur_post, $is_admmod);

                redirect($this->feather->url->get('post/'.$id.'/#p'.$id), __('Post redirect'));
            }
        } else {
            $post = '';
        }

        if ($this->request->post('preview')) {
            require_once FEATHER_ROOT.'include/parser.php';
            $preview_message = parse_message($post['message'], $post['hide_smilies']);
        } else {
            $preview_message = '';
        }

        $lang_bbeditor = array(
            'btnBold' => __('btnBold'),
            'btnItalic' => __('btnItalic'),
            'btnUnderline' => __('btnUnderline'),
            'btnColor' => __('btnColor'),
            'btnLeft' => __('btnLeft'),
            'btnRight' => __('btnRight'),
            'btnJustify' => __('btnJustify'),
            'btnCenter' => __('btnCenter'),
            'btnLink' => __('btnLink'),
            'btnPicture' => __('btnPicture'),
            'btnList' => __('btnList'),
            'btnQuote' => __('btnQuote'),
            'btnCode' => __('btnCode'),
            'promptImage' => __('promptImage'),
            'promptUrl' => __('promptUrl'),
            'promptQuote' => __('promptQuote')
        );

        $this->feather->view2->setPageInfo(array(
                            'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Edit post')),
                            'required_fields' => array('req_subject' => __('Subject'), 'req_message' => __('Message')),
                            'focus_element' => array('edit', 'req_message'),
                            'cur_post' => $cur_post,
                            'errors' => $errors,
                            'preview_message' => $preview_message,
                            'id' => $id,
                            'checkboxes' => $this->model->get_checkboxes($can_edit_subject, $is_admmod, $cur_post, 1),
                            'can_edit_subject' => $can_edit_subject,
                            'lang_bbeditor'    =>    $lang_bbeditor,
                            'post' => $post,
                            )
                    )->addTemplate('edit.php')->display();
    }
}
