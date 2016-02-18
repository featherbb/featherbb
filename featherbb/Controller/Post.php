<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Post
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Model\Post();
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/prof_reg.mo');
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/delete.mo');
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/post.mo');
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/misc.mo');
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/register.mo');
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/antispam.mo');
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/bbeditor.mo');
    }

    public function newreply($fid = null, $tid = null, $qid = null)
    {
        Container::get('hooks')->fire('controller.post.newreply');

        $this->newpost('', $fid, $tid);
    }

    public function newpost($fid = null, $tid = null, $qid = null)
    {
        Container::get('hooks')->fire('controller.post.create', $fid, $tid, $qid);

        // Antispam feature
        require Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // If $_POST['username'] is filled, we are facing a bot
        if ($this->feather->request->post('username')) {
            throw new Error(__('Bad request'), 400);
        }

        // Fetch some info about the topic and/or the forum
        $cur_posting = $this->model->get_info_post($tid, $fid);

        $is_subscribed = $tid && $cur_posting['is_subscribed'];

        // Is someone trying to post into a redirect forum?
        if ($cur_posting['redirect_url'] != '') {
            throw new Error(__('Bad request'), 400);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_posting['moderators'] != '') ? unserialize($cur_posting['moderators']) : array();
        $is_admmod = (Container::get('user')->g_id == Container::get('forum_env')['FEATHER_ADMIN'] || (Container::get('user')->g_moderator == '1' && array_key_exists(Container::get('user')->username, $mods_array))) ? true : false;

        // Do we have permission to post?
        if ((($tid && (($cur_posting['post_replies'] == '' && Container::get('user')->g_post_replies == '0') || $cur_posting['post_replies'] == '0')) ||
                ($fid && (($cur_posting['post_topics'] == '' && Container::get('user')->g_post_topics == '0') || $cur_posting['post_topics'] == '0')) ||
                (isset($cur_posting['closed']) && $cur_posting['closed'] == '1')) &&
                !$is_admmod) {
            throw new Error(__('No permission'), 403);
        }

        // Start with a clean slate
        $errors = array();

        $post = '';

        // Did someone just hit "Submit" or "Preview"?
        if ($this->feather->request()->isPost()) {

            // Include $pid and $page if needed for confirm_referrer function called in check_errors_before_post()
            if ($this->feather->request->post('pid')) {
                $pid = $this->feather->request->post('pid');
            } else {
                $pid = '';
            }

            if ($this->feather->request->post('page')) {
                $page = $this->feather->request->post('page');
            } else {
                $page = '';
            }

                // Let's see if everything went right
                $errors = $this->model->check_errors_before_post($fid, $tid, $qid, $pid, $page, $errors);

                // Setup some variables before post
                $post = $this->model->setup_variables($errors, $is_admmod);

                // Did everything go according to plan?
                if (empty($errors) && !$this->feather->request->post('preview')) {
                        // If it's a reply
                        if ($tid) {
                            // Insert the reply, get the new_pid
                                $new = $this->model->insert_reply($post, $tid, $cur_posting, $is_subscribed);

                                // Should we send out notifications?
                                if ($this->feather->forum_settings['o_topic_subscriptions'] == '1') {
                                    $this->model->send_notifications_reply($tid, $cur_posting, $new['pid'], $post);
                                }
                        }
                        // If it's a new topic
                        elseif ($fid) {
                            // Insert the topic, get the new_pid
                                $new = $this->model->insert_topic($post, $fid);

                                // Should we send out notifications?
                                if ($this->feather->forum_settings['o_forum_subscriptions'] == '1') {
                                    $this->model->send_notifications_new_topic($post, $cur_posting, $new['tid']);
                                }
                        }

                        // If we previously found out that the email was banned
                        if (Container::get('user')->is_guest && isset($errors['banned_email']) && $this->feather->forum_settings['o_mailing_list'] != '') {
                            $this->model->warn_banned_user($post, $new['pid']);
                        }

                        // If the posting user is logged in, increment his/her post count
                        if (!Container::get('user')->is_guest) {
                            $this->model->increment_post_count($post, $new['tid']);
                        }

                    Router::redirect(Router::pathFor('viewPost', ['pid' => $new['pid']]).'#p'.$new['pid'], __('Post redirect'));
                }
        }

        $quote = '';

        // If a topic ID was specified in the url (it's a reply)
        if ($tid) {
            $action = __('Post a reply');
            $form = '<form id="post" method="post" action="'.$this->feather->urlFor('newReply', ['tid' => $tid]).'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">';

                // If a quote ID was specified in the url
                if (isset($qid)) {
                    $quote = $this->model->get_quote_message($qid, $tid);
                    $form = '<form id="post" method="post" action="'.$this->feather->urlFor('newQuoteReply', ['pid' => $tid, 'qid' => $qid]).'" onsubmit="this.submit.disabled=true;if(process_form(this)){return true;}else{this.submit.disabled=false;return false;}">';
                }
        }
        // If a forum ID was specified in the url (new topic)
        elseif ($fid) {
            $action = __('Post new topic');
            $form = '<form id="post" method="post" action="'.$this->feather->urlFor('newTopic', ['fid' => $fid]).'" onsubmit="return process_form(this)">';
        } else {
            throw new Error(__('Bad request'), 404);
        }

        $url_forum = Url::url_friendly($cur_posting['forum_name']);

        $is_subscribed = $tid && $cur_posting['is_subscribed'];

        if (isset($cur_posting['subject'])) {
            $url_topic = Url::url_friendly($cur_posting['subject']);
        } else {
            $url_topic = '';
        }

        $required_fields = array('req_email' => __('Email'), 'req_subject' => __('Subject'), 'req_message' => __('Message'));
        if (Container::get('user')->is_guest) {
            $required_fields['captcha'] = __('Robot title');
        }

        // Set focus element (new post or new reply to an existing post ?)
        $focus_element[] = 'post';
        if (!Container::get('user')->is_guest) {
            $focus_element[] = ($fid) ? 'req_subject' : 'req_message';
        } else {
            $required_fields['req_username'] = __('Guest name');
            $focus_element[] = 'req_username';
        }

        // Get the current state of checkboxes
        $checkboxes = $this->model->get_checkboxes($fid, $is_admmod, $is_subscribed);

        // Check to see if the topic review is to be displayed
        if ($tid && $this->feather->forum_settings['o_topic_review'] != '0') {
            $post_data = $this->model->topic_review($tid);
        } else {
            $post_data = '';
        }

        View::setPageInfo(array(
                'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), $action),
                'required_fields' => $required_fields,
                'focus_element' => $focus_element,
                'active_page' => 'post',
                'post' => $post,
                'tid' => $tid,
                'fid' => $fid,
                'cur_posting' => $cur_posting,
                'lang_antispam' => $lang_antispam,
                'lang_antispam_questions' => $lang_antispam_questions,
                'index_questions' => $index_questions,
                'checkboxes' => $checkboxes,
                'action' => $action,
                'form' => $form,
                'post_data' => $post_data,
                'url_forum' => $url_forum,
                'url_topic' => $url_topic,
                'quote' => $quote,
                'errors'    =>    $errors,
            )
        )->addTemplate('post.php')->display();
    }

    public function delete($id)
    {
        Container::get('hooks')->fire('controller.post.delete');

        // Fetch some informations about the post, the topic and the forum
        $cur_post = $this->model->get_info_delete($id);

        if ($this->feather->forum_settings['o_censoring'] == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        $is_admmod = (Container::get('user')->g_id == Container::get('forum_env')['FEATHER_ADMIN'] || (Container::get('user')->g_moderator == '1' && array_key_exists(Container::get('user')->username, $mods_array))) ? true : false;

        $is_topic_post = ($id == $cur_post['first_post_id']) ? true : false;

        // Do we have permission to edit this post?
        if ((Container::get('user')->g_delete_posts == '0' ||
                (Container::get('user')->g_delete_topics == '0' && $is_topic_post) ||
                $cur_post['poster_id'] != Container::get('user')->id ||
                $cur_post['closed'] == '1') &&
                !$is_admmod) {
            throw new Error(__('No permission'), 403);
        }

        if ($is_admmod && Container::get('user')->g_id != Container::get('forum_env')['FEATHER_ADMIN'] && in_array($cur_post['poster_id'], Utils::get_admin_ids())) {
            throw new Error(__('No permission'), 403);
        }

        if ($this->feather->request()->isPost()) {
            $this->model->handle_deletion($is_topic_post, $id, $cur_post['tid'], $cur_post['fid']);
        }

        $cur_post['message'] = $this->feather->parser->parse_message($cur_post['message'], $cur_post['hide_smilies']);

        View::setPageInfo(array(
            'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), __('Delete post')),
            'active_page' => 'delete',
            'cur_post' => $cur_post,
            'id' => $id,
            'is_topic_post' => $is_topic_post
        ))->addTemplate('delete.php')->display();
    }

    public function editpost($id)
    {
        Container::get('hooks')->fire('controller.post.edit');

        // Fetch some informations about the post, the topic and the forum
        $cur_post = $this->model->get_info_edit($id);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        $is_admmod = (Container::get('user')->g_id == Container::get('forum_env')['FEATHER_ADMIN'] || (Container::get('user')->g_moderator == '1' && array_key_exists(Container::get('user')->username, $mods_array))) ? true : false;

        $can_edit_subject = $id == $cur_post['first_post_id'];

        if ($this->feather->config['o_censoring'] == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
            $cur_post['message'] = Utils::censor($cur_post['message']);
        }

        // Do we have permission to edit this post?
        if ((Container::get('user')->g_edit_posts == '0' || $cur_post['poster_id'] != Container::get('user')->id || $cur_post['closed'] == '1') && !$is_admmod) {
            throw new Error(__('No permission'), 403);
        }

        if ($is_admmod && Container::get('user')->g_id != Container::get('forum_env')['FEATHER_ADMIN'] && in_array($cur_post['poster_id'], Utils::get_admin_ids())) {
            throw new Error(__('No permission'), 403);
        }

        // Start with a clean slate
        $errors = array();

        if ($this->feather->request()->isPost()) {
            Container::get('hooks')->fire('controller.post.edit.submit', $id);

            // Let's see if everything went right
            $errors = $this->model->check_errors_before_edit($can_edit_subject, $errors);

            // Setup some variables before post
            $post = $this->model->setup_edit_variables($cur_post, $is_admmod, $can_edit_subject, $errors);

            // Did everything go according to plan?
            if (empty($errors) && !$this->feather->request->post('preview')) {
                Container::get('hooks')->fire('controller.post.edit.valid', $id);
                // Edit the post
                $this->model->edit_post($id, $can_edit_subject, $post, $cur_post, $is_admmod);

                Router::redirect(Router::pathFor('viewPost', ['pid' => $id]).'#p'.$id, __('Post redirect'));
            }
        } else {
            $post = '';
        }

        if ($this->feather->request->post('preview')) {
            $preview_message = $this->feather->parser->parse_message($post['message'], $post['hide_smilies']);
            $preview_message = Container::get('hooks')->fire('controller.post.edit.preview', $preview_message);
        } else {
            $preview_message = '';
        }

        View::setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('Edit post')),
                'required_fields' => array('req_subject' => __('Subject'), 'req_message' => __('Message')),
                'focus_element' => array('edit', 'req_message'),
                'cur_post' => $cur_post,
                'errors' => $errors,
                'preview_message' => $preview_message,
                'id' => $id,
                'checkboxes' => $this->model->get_edit_checkboxes($can_edit_subject, $is_admmod, $cur_post, 1),
                'can_edit_subject' => $can_edit_subject,
                'post' => $post,
            )
        )->addTemplate('edit.php')->display();
    }

    public function report($id)
    {
        $id = Container::get('hooks')->fire('controller.post.report', $id);

        if ($this->feather->request()->isPost()) {
            $this->model->insert_report($id);
        }

        // Fetch some info about the post, the topic and the forum
        $cur_post = $this->model->get_info_report($id);

        if ($this->feather->forum_settings['o_censoring'] == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
        }

        View::setPageInfo(array(
            'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), __('Report post')),
            'active_page' => 'report',
            'required_fields' => array('req_reason' => __('Reason')),
            'focus_element' => array('report', 'req_reason'),
            'id' => $id,
            'cur_post' => $cur_post
            ))->addTemplate('misc/report.php')->display();
    }

    public function gethost($pid)
    {
        $pid = Container::get('hooks')->fire('controller.post.gethost', $pid);

        $this->model->display_ip_address($pid);
    }
}
