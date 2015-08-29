<?php

/**
* Copyright (C) 2015 FeatherBB
* based on code by (C) 2008-2012 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace controller;

class misc
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \model\misc();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/register.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->feather->user->language.'/misc.mo');
    }

    public function rules()
    {
        if ($this->feather->forum_settings['o_rules'] == '0' || ($this->feather->user->is_guest && $this->feather->user->g_read_board == '0' && $this->feather->forum_settings['o_regs_allow'] == '0')) {
            throw new \FeatherBB\Error(__('Bad request'), 404);
        }

        $this->feather->view2->setPageInfo(array(
            'title' => array(feather_escape($this->feather->forum_settings['o_board_title']), __('Forum rules')),
            'active_page' => 'rules'
            ))->addTemplate('misc/rules.php')->display();
    }

    public function markread()
    {
        if ($this->feather->user->is_guest) {
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        $this->model->update_last_visit();

        // Reset tracked topics
        set_tracked_topics(null);

        redirect($this->feather->url->base(), __('Mark read redirect'));
    }

    public function markforumread($id)
    {
        if ($this->feather->user->is_guest) {
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        $tracked_topics = get_tracked_topics();
        $tracked_topics['forums'][$id] = time();
        set_tracked_topics($tracked_topics);

        redirect($this->feather->url->get('forum/'.$id.'/'), __('Mark forum read redirect'));
    }

    public function subscribeforum($id)
    {
        if ($this->feather->user->is_guest) {
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        $this->model->subscribe_forum($id);
    }

    public function subscribetopic($id)
    {
        if ($this->feather->user->is_guest) {
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        $this->model->subscribe_topic($id);
    }

    public function unsubscribeforum($id)
    {
        if ($this->feather->user->is_guest) {
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        $this->model->unsubscribe_forum($id);
    }

    public function unsubscribetopic($id)
    {
        if ($this->feather->user->is_guest) {
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        $this->model->unsubscribe_topic($id);
    }

    public function email($id)
    {
        if ($this->feather->user->is_guest || $this->feather->user->g_send_email == '0') {
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        if ($id < 2) {
            throw new \FeatherBB\Error(__('Bad request'), 400);
        }

        $mail = $this->model->get_info_mail($id);

        if ($mail['email_setting'] == 2 && !$this->feather->user->is_admmod) {
            throw new \FeatherBB\Error(__('Form email disabled'), 403);
        }


        if ($this->feather->request()->isPost()) {
            $this->model->send_email($mail);
        }

        $this->feather->view2->setPageInfo(array(
            'title' => array(feather_escape($this->feather->forum_settings['o_board_title']), __('Send email to').' '.feather_escape($mail['recipient'])),
            'active_page' => 'email',
            'required_fields' => array('req_subject' => __('Email subject'), 'req_message' => __('Email message')),
            'focus_element' => array('email', 'req_subject'),
            'id' => $id,
            'mail' => $mail
            ))->addTemplate('misc/email.php')->display();
    }

    public function report($id)
    {
        if ($this->feather->user->is_guest) {
            throw new \FeatherBB\Error(__('No permission'), 403);
        }

        if ($this->feather->request()->isPost()) {
            $this->model->insert_report($id);
        }

        // Fetch some info about the post, the topic and the forum
        $cur_post = $this->model->get_info_report($id);

        if ($this->feather->forum_settings['o_censoring'] == '1') {
            $cur_post['subject'] = censor_words($cur_post['subject']);
        }

        $this->feather->view2->setPageInfo(array(
            'title' => array(feather_escape($this->feather->forum_settings['o_board_title']), __('Report post')),
            'active_page' => 'report',
            'required_fields' => array('req_reason' => __('Reason')),
            'focus_element' => array('report', 'req_reason'),
            'id' => $id,
            'cur_post' => $cur_post
            ))->addTemplate('misc/report.php')->display();
    }
}
