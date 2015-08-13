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
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\misc();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function rules()
    {
        global $lang_common;

        if ($this->config['o_rules'] == '0' || ($this->user->is_guest && $this->user->g_read_board == '0' && $this->config['o_regs_allow'] == '0')) {
            message($lang_common['Bad request'], '404');
        }

        // Load the register.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/register.php';

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_register['Forum rules']);

        define('FEATHER_ACTIVE_PAGE', 'rules');

        $this->feather->render('misc/rules.php', array(
                'lang_register' => $lang_register,
                'feather_config' => $this->config,
                )
        );

        $this->footer->display();
    }

    public function markread()
    {
        global $lang_common;

        if ($this->user->is_guest) {
            message($lang_common['No permission'], '403');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        $this->model->update_last_visit();

        // Reset tracked topics
        set_tracked_topics(null);

        redirect(get_base_url(), $lang_misc['Mark read redirect']);
    }

    public function markforumread($id)
    {
        global $lang_common;

        if ($this->user->is_guest) {
            message($lang_common['No permission'], '403');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        $tracked_topics = get_tracked_topics();
        $tracked_topics['forums'][$id] = time();
        set_tracked_topics($tracked_topics);

        redirect(get_link('forum/'.$id.'/'), $lang_misc['Mark forum read redirect']);
    }

    public function subscribeforum($id)
    {
        global $lang_common, $lang_misc;

        if ($this->user->is_guest) {
            message($lang_common['No permission'], '403');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        $this->model->subscribe_forum($id);
    }

    public function subscribetopic($id)
    {
        global $lang_common, $lang_misc;

        if ($this->user->is_guest) {
            message($lang_common['No permission'], '403');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        $this->model->subscribe_topic($id);
    }

    public function unsubscribeforum($id)
    {
        global $lang_common, $lang_misc;

        if ($this->user->is_guest) {
            message($lang_common['No permission'], '403');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        $this->model->unsubscribe_forum($id);
    }

    public function unsubscribetopic($id)
    {
        global $lang_common, $lang_misc;

        if ($this->user->is_guest) {
            message($lang_common['No permission'], '403');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        $this->model->unsubscribe_topic($id);
    }

    public function email($id)
    {
        global $lang_common;

        if ($this->user->is_guest || $this->user->g_send_email == '0') {
            message($lang_common['No permission'], '403');
        }

        if ($id < 2) {
            message($lang_common['Bad request'], '404');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        $mail = $this->model->get_info_mail($id);

        if ($mail['email_setting'] == 2 && !$this->user->is_admmod) {
            message($lang_misc['Form email disabled']);
        }


        if ($this->feather->request()->isPost()) {
            $this->model->send_email($mail, $id);
        }

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_misc['Send email to'].' '.feather_escape($mail['recipient']));
        $required_fields = array('req_subject' => $lang_misc['Email subject'], 'req_message' => $lang_misc['Email message']);
        $focus_element = array('email', 'req_subject');

        define('FEATHER_ACTIVE_PAGE', 'email');

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

        $this->feather->render('misc/email.php', array(
                'lang_misc' => $lang_misc,
                'id' => $id,
                'mail' => $mail,
                )
        );

        $this->footer->display();
    }

    public function report($id)
    {
        global $lang_common;

        if ($this->user->is_guest) {
            message($lang_common['No permission'], '403');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/misc.php';

        if ($this->feather->request()->isPost()) {
            $this->model->insert_report($id);
        }

        // Fetch some info about the post, the topic and the forum
        $cur_post = $this->model->get_info_report($id);

        if ($this->config['o_censoring'] == '1') {
            $cur_post['subject'] = censor_words($cur_post['subject']);
        }

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_misc['Report post']);
        $required_fields = array('req_reason' => $lang_misc['Reason']);
        $focus_element = array('report', 'req_reason');

        define('FEATHER_ACTIVE_PAGE', 'report');

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

        $this->feather->render('misc/report.php', array(
                'lang_misc' => $lang_misc,
                'id' => $id,
                'lang_common' => $lang_common,
                'cur_post' => $cur_post,
                )
        );

        $this->footer->display();
    }
}
