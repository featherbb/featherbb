<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;
use DB;

class Misc
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
        $this->email = $this->feather->email;
    }

    public function update_last_visit()
    {
        $this->hook->fire('update_last_visit_start');

        $update_last_visit = DB::for_table('users')->where('id', $this->user->id)
                                                   ->find_one()
                                                   ->set('last_visit', $this->user->logged);
        $update_last_visit = $this->hook->fireDB('update_last_visit', $update_last_visit);
        $update_last_visit = $update_last_visit->save();
    }

    public function get_info_mail($recipient_id)
    {
        $recipient_id = $this->hook->fire('get_info_mail_start', $recipient_id);

        $mail['select'] = array('username', 'email', 'email_setting');

        $mail = DB::for_table('users')
                ->select_many($mail['select'])
                ->where('id', $recipient_id);
        $mail = $this->hook->fireDB('get_info_mail_query', $mail);
        $mail = $mail->find_one();

        if (!$mail) {
            throw new \FeatherBB\Core\Error(__('Bad request'), 404);
        }

        $mail['recipient'] = $mail['username'];
        $mail['recipient_email'] = $mail['email'];

        $mail = $this->hook->fireDB('get_info_mail', $mail);

        return $mail;
    }

    public function send_email($mail)
    {
        $mail = $this->hook->fire('send_email_start', $mail);

        // Clean up message and subject from POST
        $subject = Utils::trim($this->request->post('req_subject'));
        $message = Utils::trim($this->request->post('req_message'));

        if ($subject == '') {
            throw new \FeatherBB\Core\Error(__('No email subject'), 400);
        } elseif ($message == '') {
            throw new \FeatherBB\Core\Error(__('No email message'), 400);
        }
        // Here we use strlen() not Utils::strlen() as we want to limit the post to FEATHER_MAX_POSTSIZE bytes, not characters
        elseif (strlen($message) > FEATHER_MAX_POSTSIZE) {
            throw new \FeatherBB\Core\Error(__('Too long email message'), 400);
        }

        if ($this->user->last_email_sent != '' && (time() - $this->user->last_email_sent) < $this->user->g_email_flood && (time() - $this->user->last_email_sent) >= 0) {
            throw new \FeatherBB\Core\Error(sprintf(__('Email flood'), $this->user->g_email_flood, $this->user->g_email_flood - (time() - $this->user->last_email_sent)), 429);
        }

        // Load the "form email" template
        $mail_tpl = trim(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/mail_templates/form_email.tpl'));
        $mail_tpl = $this->hook->fire('send_email_mail_tpl', $mail_tpl);

        // The first row contains the subject
        $first_crlf = strpos($mail_tpl, "\n");
        $mail_subject = Utils::trim(substr($mail_tpl, 8, $first_crlf-8));
        $mail_message = Utils::trim(substr($mail_tpl, $first_crlf));

        $mail_subject = str_replace('<mail_subject>', $subject, $mail_subject);
        $mail_message = str_replace('<sender>', $this->user->username, $mail_message);
        $mail_message = str_replace('<board_title>', $this->config['o_board_title'], $mail_message);
        $mail_message = str_replace('<mail_message>', $message, $mail_message);
        $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

        $mail_message = $this->hook->fire('send_email_mail_message', $mail_message);

        $this->email->feather_mail($mail['recipient_email'], $mail_subject, $mail_message, $this->user->email, $this->user->username);

        $update_last_mail_sent = DB::for_table('users')->where('id', $this->user->id)
                                                  ->find_one()
                                                  ->set('last_email_sent', time());
        $update_last_mail_sent = $this->hook->fireDB('send_email_update_last_mail_sent', $update_last_mail_sent);
        $update_last_mail_sent = $update_last_mail_sent->save();

        // Try to determine if the data in redirect_url is valid (if not, we redirect to index.php after the email is sent) TODO
        //$redirect_url = validate_redirect($this->request->post('redirect_url'), 'index.php');

        redirect(Url::base(), __('Email sent redirect'));
    }

    public function get_redirect_url($recipient_id)
    {
        $recipient_id = $this->hook->fire('get_redirect_url_start', $recipient_id);

        // Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to the user's profile after the email is sent)
        // TODO
        if ($this->request->getReferrer()) {
            $redirect_url = validate_redirect($this->request->getReferrer(), null);
        }

        if (!isset($redirect_url)) {
            $redirect_url = Url::get('user/'.$recipient_id.'/');
        } elseif (preg_match('%viewtopic\.php\?pid=(\d+)$%', $redirect_url, $matches)) {
            $redirect_url .= '#p'.$matches[1];
        }

        $redirect_url = $this->hook->fire('get_redirect_url', $redirect_url);

        return $redirect_url;
    }

    public function insert_report($post_id)
    {
        $post_id = $this->hook->fire('insert_report_start', $post_id);

        // Clean up reason from POST
        $reason = Utils::linebreaks(Utils::trim($this->request->post('req_reason')));
        if ($reason == '') {
            throw new \FeatherBB\Core\Error(__('No reason'), 400);
        } elseif (strlen($reason) > 65535) { // TEXT field can only hold 65535 bytes
            throw new \FeatherBB\Core\Error(__('Reason too long'), 400);
        }

        if ($this->user->last_report_sent != '' && (time() - $this->user->last_report_sent) < $this->user->g_report_flood && (time() - $this->user->last_report_sent) >= 0) {
            throw new \FeatherBB\Core\Error(sprintf(__('Report flood'), $this->user->g_report_flood, $this->user->g_report_flood - (time() - $this->user->last_report_sent)), 429);
        }

        // Get the topic ID
        $topic = DB::for_table('posts')->select('topic_id')
                                      ->where('id', $post_id);
        $topic = $this->hook->fireDB('insert_report_topic_id', $topic);
        $topic = $topic->find_one();

        if (!$topic) {
            throw new \FeatherBB\Core\Error(__('Bad request'), 404);
        }

        // Get the subject and forum ID
        $report['select'] = array('subject', 'forum_id');
        $report = DB::for_table('topics')->select_many($report['select'])
                                        ->where('id', $topic['topic_id']);
        $report = $this->hook->fireDB('insert_report_get_subject', $report);
        $report = $report->find_one();

        if (!$report) {
            throw new \FeatherBB\Core\Error(__('Bad request'), 404);
        }

        // Should we use the internal report handling?
        if ($this->config['o_report_method'] == '0' || $this->config['o_report_method'] == '2') {

            // Insert the report
            $query['insert'] = array(
                'post_id' => $post_id,
                'topic_id'  => $topic['topic_id'],
                'forum_id'  => $report['forum_id'],
                'reported_by'  => $this->user->id,
                'created'  => time(),
                'message'  => $reason,
            );
            $query = DB::for_table('reports')
                ->create()
                ->set($query['insert']);
            $query = $this->hook->fireDB('insert_report_query', $query);
            $query = $query->save();
        }

        // Should we email the report?
        if ($this->config['o_report_method'] == '1' || $this->config['o_report_method'] == '2') {
            // We send it to the complete mailing-list in one swoop
            if ($this->config['o_mailing_list'] != '') {
                // Load the "new report" template
                $mail_tpl = trim(file_get_contents($this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/mail_templates/new_report.tpl'));
                $mail_tpl = $this->hook->fire('insert_report_mail_tpl', $mail_tpl);

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_message = trim(substr($mail_tpl, $first_crlf));

                $mail_subject = str_replace('<forum_id>', $report['forum_id'], $mail_subject);
                $mail_subject = str_replace('<topic_subject>', $report['subject'], $mail_subject);
                $mail_message = str_replace('<username>', $this->user->username, $mail_message);
                $mail_message = str_replace('<post_url>', Url::get('post/'.$post_id.'/#p'.$post_id), $mail_message);
                $mail_message = str_replace('<reason>', $reason, $mail_message);
                $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                $mail_message = $this->hook->fire('insert_report_mail_message', $mail_message);

                $this->email->feather_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
            }
        }

        $last_report_sent = DB::for_table('users')->where('id', $this->user->id)
            ->find_one()
            ->set('last_report_sent', time());
        $last_report_sent = $this->hook->fireDB('insert_last_report_sent', $last_report_sent);
        $last_report_sent = $last_report_sent->save();

        redirect(Url::get('forum/'.$report['forum_id'].'/'.Url::url_friendly($report['subject']).'/'), __('Report redirect'));
    }

    public function get_info_report($post_id)
    {
        $post_id = $this->hook->fire('get_info_report_start', $post_id);

        $cur_post['select'] = array('fid' => 'f.id', 'f.forum_name', 'tid' => 't.id', 't.subject');
        $cur_post['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $cur_post = DB::for_table('posts')
                        ->table_alias('p')
                        ->select_many($cur_post['select'])
                        ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
                        ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
                        ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                        ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                        ->where_any_is($cur_post['where'])
                        ->where('p.id', $post_id);
        $cur_post = $this->hook->fireDB('get_info_report_query', $cur_post);
        $cur_post = $cur_post->find_one();

        if (!$cur_post) {
            throw new \FeatherBB\Core\Error(__('Bad request'), 404);
        }

        $cur_post = $this->hook->fire('get_info_report', $cur_post);

        return $cur_post;
    }

    public function subscribe_topic($topic_id)
    {
        $topic_id = $this->hook->fire('subscribe_topic_start', $topic_id);

        if ($this->config['o_topic_subscriptions'] != '1') {
            throw new \FeatherBB\Core\Error(__('No permission'), 403);
        }

        // Make sure the user can view the topic
        $authorized['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $authorized = DB::for_table('topics')
                            ->table_alias('t')
                            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                            ->where_any_is($authorized['where'])
                            ->where('t.id', $topic_id)
                            ->where_null('t.moved_to');
        $authorized = $this->hook->fireDB('subscribe_topic_authorized_query', $authorized);
        $authorized = $authorized->find_one();

        if (!$authorized) {
            throw new \FeatherBB\Core\Error(__('Bad request'), 404);
        }

        $is_subscribed = DB::for_table('topic_subscriptions')
                        ->where('user_id', $this->user->id)
                        ->where('topic_id', $topic_id);
        $is_subscribed = $this->hook->fireDB('subscribe_topic_is_subscribed_query', $is_subscribed);
        $is_subscribed = $is_subscribed->find_one();

        if ($is_subscribed) {
            throw new \FeatherBB\Core\Error(__('Already subscribed topic'), 400);
        }

        $subscription['insert'] = array(
            'user_id' => $this->user->id,
            'topic_id'  => $topic_id
        );

        // Insert the subscription
        $subscription = DB::for_table('topic_subscriptions')
                                    ->create()
                                    ->set($subscription['insert']);
        $subscription = $this->hook->fireDB('subscribe_topic_query', $subscription);
        $subscription = $subscription->save();

        redirect(Url::get('topic/'.$topic_id.'/'), __('Subscribe redirect'));
    }

    public function unsubscribe_topic($topic_id)
    {
        $topic_id = $this->hook->fire('unsubscribe_topic_start', $topic_id);

        if ($this->config['o_topic_subscriptions'] != '1') {
            throw new \FeatherBB\Core\Error(__('No permission'), 403);
        }

        $is_subscribed = DB::for_table('topic_subscriptions')
                            ->where('user_id', $this->user->id)
                            ->where('topic_id', $topic_id);
        $is_subscribed = $this->hook->fireDB('unsubscribe_topic_subscribed_query', $is_subscribed);
        $is_subscribed = $is_subscribed->find_one();

        if (!$is_subscribed) {
            throw new \FeatherBB\Core\Error(__('Not subscribed topic'), 400);
        }

        // Delete the subscription
        $delete = DB::for_table('topic_subscriptions')
                    ->where('user_id', $this->user->id)
                    ->where('topic_id', $topic_id);
        $delete = $this->hook->fireDB('unsubscribe_topic_query', $delete);
        $delete = $delete->delete_many();

        redirect(Url::get('topic/'.$topic_id.'/'), __('Unsubscribe redirect'));
    }

    public function unsubscribe_forum($forum_id)
    {
        $forum_id = $this->hook->fire('unsubscribe_forum_start', $forum_id);

        if ($this->config['o_forum_subscriptions'] != '1') {
            throw new \FeatherBB\Core\Error(__('No permission'), 403);
        }

        $is_subscribed = DB::for_table('forum_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('forum_id', $forum_id);
        $is_subscribed = $this->hook->fireDB('unsubscribe_forum_subscribed_query', $is_subscribed);
        $is_subscribed = $is_subscribed->find_one();

        if (!$is_subscribed) {
            throw new \FeatherBB\Core\Error(__('Not subscribed forum'), 400);
        }

        // Delete the subscription
        $delete = DB::for_table('forum_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('forum_id', $forum_id);
        $delete = $this->hook->fireDB('unsubscribe_forum_query', $delete);
        $delete = $delete->delete_many();

        redirect(Url::get('forum/'.$forum_id.'/'), __('Unsubscribe redirect'));
    }

    public function subscribe_forum($forum_id)
    {
        $forum_id = $this->hook->fire('subscribe_forum_start', $forum_id);

        if ($this->config['o_forum_subscriptions'] != '1') {
            throw new \FeatherBB\Core\Error(__('No permission'), 403);
        }

        // Make sure the user can view the forum
        $authorized['where'] = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $authorized = DB::for_table('forums')
                        ->table_alias('f')
                        ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                        ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                        ->where_any_is($authorized['where'])
                        ->where('f.id', $forum_id);
        $authorized = $this->hook->fireDB('subscribe_forum_authorized_query', $authorized);
        $authorized = $authorized->find_one();

        if (!$authorized) {
            throw new \FeatherBB\Core\Error(__('Bad request'), 404);
        }

        $is_subscribed = DB::for_table('forum_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('forum_id', $forum_id);
        $is_subscribed = $this->hook->fireDB('subscribe_forum_subscribed_query', $is_subscribed);
        $is_subscribed = $is_subscribed->find_one();

        if ($is_subscribed) {
            throw new \FeatherBB\Core\Error(__('Already subscribed forum'), 400);
        }

        // Insert the subscription
        $subscription['insert'] = array(
            'user_id' => $this->user->id,
            'forum_id'  => $forum_id
        );
        $subscription = DB::for_table('forum_subscriptions')
                            ->create()
                            ->set($subscription['insert']);
        $subscription = $this->hook->fireDB('subscribe_forum_query', $subscription);
        $subscription = $subscription->save();

        redirect(Url::get('forum/'.$forum_id.'/'), __('Subscribe redirect'));
    }
}
