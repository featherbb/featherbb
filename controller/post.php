<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class post
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
        $this->model = new \model\post();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function newreply($fid = null, $tid = null, $qid = null)
    {
        self::newpost('', $fid, $tid);
    }

    public function newpost($fid = null, $tid = null, $qid = null)
    {
        global $lang_common, $lang_prof_reg, $lang_antispam_questions, $lang_antispam, $lang_post, $lang_register, $lang_bbeditor;

        // Load the register.php/profile.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/prof_reg.php';

        // Load the register.php/profile.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/register.php';
        
        // Load the bbeditor.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/bbeditor.php';

        // Antispam feature
        require FEATHER_ROOT.'lang/'.$this->user->language.'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // BBcode toolbar feature
        require FEATHER_ROOT.'lang/'.$this->user['language'].'/bbeditor.php';

        // If $_POST['username'] is filled, we are facing a bot
        if ($this->request->post('username')) {
            message($lang_common['Bad request'], '404');
        }

        // Fetch some info about the topic and/or the forum
        $cur_posting = $this->model->get_info_post($tid, $fid);

        $is_subscribed = $tid && $cur_posting['is_subscribed'];

        // Is someone trying to post into a redirect forum?
        if ($cur_posting['redirect_url'] != '') {
            message($lang_common['Bad request'], '404');
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
        $is_admmod = ($this->user->g_id == FEATHER_ADMIN || ($this->user->g_moderator == '1' && array_key_exists($this->user->username, $mods_array))) ? true : false;

        // Do we have permission to post?
        if ((($tid && (($cur_posting['post_replies'] == '' && $this->user->g_post_replies == '0') || $cur_posting['post_replies'] == '0')) ||
                ($fid && (($cur_posting['post_topics'] == '' && $this->user->g_post_topics == '0') || $cur_posting['post_topics'] == '0')) ||
                (isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
                !$is_admmod) {
            message($lang_common['No permission'], '403');
        }

        // Load the post.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/post.php';

        // Start with a clean slate
        $errors = array();

        $post = '';

        if (!$this->user->is_guest) {
            $focus_element[] = ($fid) ? 'req_subject' : 'req_message';
        } else {
            $required_fields['req_username'] = $lang_post['Guest name'];
            $focus_element[] = 'req_username';
        }

        // Did someone just hit "Submit" or "Preview"?
        if ($this->feather->request()->isPost()) {

                // Include $pid and $page if needed for confirm_referrer function called in check_errors_before_post()
                if ($this->request->post('pid')) {
                    $pid = $this->request->post('pid');
                } else {
                    $pid = '';
                }

            if ($this->request->post('page')) {
                $page = $this->request->post('page');
            } else {
                $page = '';
            }

                // Let's see if everything went right
                $errors = $this->model->check_errors_before_post($fid, $tid, $qid, $pid, $page, $errors);

                // Setup some variables before post
                $post = $this->model->setup_variables($errors, $is_admmod);

                // Did everything go according to plan?
                if (empty($errors) && !$this->request->post('preview')) {
                    require FEATHER_ROOT.'include/search_idx.php';

                        // If it's a reply
                        if ($tid) {
                            // Insert the reply, get the new_pid
                                $new = $this->model->insert_reply($post, $tid, $cur_posting, $is_subscribed);

                                // Should we send out notifications?
                                if ($this->config['o_topic_subscriptions'] == '1') {
                                    $this->model->send_notifications_reply($tid, $cur_posting, $new['pid'], $post);
                                }
                        }
                        // If it's a new topic
                        elseif ($fid) {
                            // Insert the topic, get the new_pid
                                $new = $this->model->insert_topic($post, $fid);

                                // Should we send out notifications?
                                if ($this->config['o_forum_subscriptions'] == '1') {
                                    $this->model->send_notifications_new_topic($post, $cur_posting, $new['tid']);
                                }
                        }

                        // If we previously found out that the email was banned
                        if ($this->user->is_guest && isset($errors['banned_email']) && $this->config['o_mailing_list'] != '') {
                            $this->model->warn_banned_user($post, $new['pid']);
                        }

                        // If the posting user is logged in, increment his/her post count
                        if (!$this->user->is_guest) {
                            $this->model->increment_post_count($post, $new['tid']);
                        }

                    redirect(get_link('post/'.$new['pid'].'/#p'.$new['pid']), $lang_post['Post redirect']);
                }
        }

        $quote = '';

        // If a topic ID was specified in the url (it's a reply)
        if ($tid) {
            $action = $lang_post['Post a reply'];
            $form = '<form id="post" method="post" action="'.get_link('post/reply/'.$tid.'/').'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">';

                // If a quote ID was specified in the url
                if (isset($qid)) {
                    $quote = $this->model->get_quote_message($qid, $tid);
                    $form = '<form id="post" method="post" action="'.get_link('post/reply/'.$tid.'/quote/'.$qid.'/').'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">';
                }
        }
        // If a forum ID was specified in the url (new topic)
        elseif ($fid) {
            $action = $lang_post['Post new topic'];
            $form = '<form id="post" method="post" action="'.get_link('post/new-topic/'.$fid.'/').'" onsubmit="return process_form(this)">';
        } else {
            message($lang_common['Bad request'], '404');
        }

        $url_forum = url_friendly($cur_posting['forum_name']);

        $is_subscribed = $tid && $cur_posting['is_subscribed'];

        if (isset($cur_posting['subject'])) {
            $url_topic = url_friendly($cur_posting['subject']);
        } else {
            $url_topic = '';
        }

        $page_title = array(feather_escape($this->config['o_board_title']), $action);
        $required_fields = array('req_email' => $lang_common['Email'], 'req_subject' => $lang_common['Subject'], 'req_message' => $lang_common['Message']);
        if ($this->user->is_guest) {
            $required_fields['captcha'] = $lang_antispam['Robot title'];
        }
        $focus_element = array('post');

        define('FEATHER_ACTIVE_PAGE', 'post');

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->setRequiredFields($required_fields)->display();

        // Get the current state of checkboxes
        $checkboxes = $this->model->get_checkboxes($fid, $is_admmod, $is_subscribed);

        // Check to see if the topic review is to be displayed
        if ($tid && $this->config['o_topic_review'] != '0') {
            $post_data = $this->model->topic_review($tid);
        } else {
            $post_data = '';
        }

        $this->feather->render('post.php', array(
                            'post' => $post,
                            'tid' => $tid,
                            'fid' => $fid,
                            'feather_config' => $this->config,
                            'feather_user' => $this->user,
                            'cur_posting' => $cur_posting,
                            'lang_common' => $lang_common,
                            'lang_post' => $lang_post,
                            'lang_antispam' => $lang_antispam,
                            'lang_antispam_questions' => $lang_antispam_questions,
                            'lang_bbeditor' => $lang_bbeditor,
                            'index_questions' => $index_questions,
                            'checkboxes' => $checkboxes,
                            'cur_posting' => $cur_posting,
                            'feather' => $this->feather,
                            'action' => $action,
                            'form' => $form,
                            'post_data' => $post_data,
                            'url_forum' => $url_forum,
                            'url_topic' => $url_topic,
                            'quote' => $quote,
                            'errors'    =>    $errors,
                            'lang_bbeditor' => $lang_bbeditor,
                            )
                    );

        $this->footer->display();
    }
}
