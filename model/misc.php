<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model;

use DB;

class misc
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    public function update_last_visit()
    {
        DB::for_table('users')->where('id', $this->user->id)
                                                  ->find_one()
                                                  ->set('last_visit', $this->user->logged)
                                                  ->save();
    }

    public function get_info_mail($recipient_id)
    {
        global $lang_common;
        
        $select_get_info_mail = array('username', 'email', 'email_setting');
        
        $mail = DB::for_table('users')
                ->select_many($select_get_info_mail)
                ->where('id', $recipient_id)
                ->find_one();
        
        if (!$mail) {
            message($lang_common['Bad request'], '404');
        }
        
        $mail['recipient'] = $mail['username'];
        $mail['recipient_email'] = $mail['email'];

        return $mail;
    }

    public function send_email($mail, $id)
    {
        global $lang_misc;

        // Clean up message and subject from POST
        $subject = feather_trim($this->request->post('req_subject'));
        $message = feather_trim($this->request->post('req_message'));

        if ($subject == '') {
            message($lang_misc['No email subject']);
        } elseif ($message == '') {
            message($lang_misc['No email message']);
        }
        // Here we use strlen() not feather_strlen() as we want to limit the post to FEATHER_MAX_POSTSIZE bytes, not characters
        elseif (strlen($message) > FEATHER_MAX_POSTSIZE) {
            message($lang_misc['Too long email message']);
        }

        if ($this->user->last_email_sent != '' && (time() - $this->user->last_email_sent) < $this->user->g_email_flood && (time() - $this->user->last_email_sent) >= 0) {
            message(sprintf($lang_misc['Email flood'], $this->user->g_email_flood, $this->user->g_email_flood - (time() - $this->user->last_email_sent)));
        }

        // Load the "form email" template
        $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user->language.'/mail_templates/form_email.tpl'));

        // The first row contains the subject
        $first_crlf = strpos($mail_tpl, "\n");
        $mail_subject = feather_trim(substr($mail_tpl, 8, $first_crlf-8));
        $mail_message = feather_trim(substr($mail_tpl, $first_crlf));

        $mail_subject = str_replace('<mail_subject>', $subject, $mail_subject);
        $mail_message = str_replace('<sender>', $this->user->username, $mail_message);
        $mail_message = str_replace('<board_title>', $this->config['o_board_title'], $mail_message);
        $mail_message = str_replace('<mail_message>', $message, $mail_message);
        $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

        require_once FEATHER_ROOT.'include/email.php';

        pun_mail($mail['recipient_email'], $mail_subject, $mail_message, $this->user->email, $this->user->username);

        DB::for_table('users')->where('id', $this->user->id)
                                                  ->find_one()
                                                  ->set('last_email_sent', time())
                                                  ->save();

        // Try to determine if the data in redirect_url is valid (if not, we redirect to index.php after the email is sent)
        //$redirect_url = validate_redirect($this->request->post('redirect_url'), 'index.php');

        redirect(get_base_url(), $lang_misc['Email sent redirect']);
    }

    public function get_redirect_url($recipient_id)
    {
        // Try to determine if the data in HTTP_REFERER is valid (if not, we redirect to the user's profile after the email is sent)
        // TODO
        if ($this->request->getReferrer()) {
            $redirect_url = validate_redirect($this->request->getReferrer(), null);
        }

        if (!isset($redirect_url)) {
            $redirect_url = get_link('user/'.$recipient_id.'/');
        } elseif (preg_match('%viewtopic\.php\?pid=(\d+)$%', $redirect_url, $matches)) {
            $redirect_url .= '#p'.$matches[1];
        }

        return $redirect_url;
    }

    public function insert_report($post_id)
    {
        global $lang_misc, $lang_common;

        // Clean up reason from POST
        $reason = feather_linebreaks(feather_trim($this->request->post('req_reason')));
        if ($reason == '') {
            message($lang_misc['No reason']);
        } elseif (strlen($reason) > 65535) { // TEXT field can only hold 65535 bytes
            message($lang_misc['Reason too long']);
        }

        if ($this->user->last_report_sent != '' && (time() - $this->user->last_report_sent) < $this->user->g_report_flood && (time() - $this->user->last_report_sent) >= 0) {
            message(sprintf($lang_misc['Report flood'], $this->user->g_report_flood, $this->user->g_report_flood - (time() - $this->user->last_report_sent)));
        }

        // Get the topic ID
        $topic = DB::for_table('posts')->select('topic_id')
                                                              ->where('id', $post_id)
                                                              ->find_one();

        if (!$topic) {
            message($lang_common['Bad request'], '404');
        }

        $select_report = array('subject', 'forum_id');

        // Get the subject and forum ID
        $report = DB::for_table('topics')->select_many($select_report)
            ->where('id', $topic['topic_id'])
            ->find_one();

        if (!$report) {
            message($lang_common['Bad request'], '404');
        }

        // Should we use the internal report handling?
        if ($this->config['o_report_method'] == '0' || $this->config['o_report_method'] == '2') {
            $insert_report = array(
                'post_id' => $post_id,
                'topic_id'  => $topic['topic_id'],
                'forum_id'  => $report['forum_id'],
                'reported_by'  => $this->user->id,
                'created'  => time(),
                'message'  => $reason,
            );

            // Insert the report
            DB::for_table('reports')
                ->create()
                ->set($insert_report)
                ->save();
        }

        // Should we email the report?
        if ($this->config['o_report_method'] == '1' || $this->config['o_report_method'] == '2') {
            // We send it to the complete mailing-list in one swoop
            if ($this->config['o_mailing_list'] != '') {
                // Load the "new report" template
                $mail_tpl = trim(file_get_contents(FEATHER_ROOT.'lang/'.$this->user->language.'/mail_templates/new_report.tpl'));

                // The first row contains the subject
                $first_crlf = strpos($mail_tpl, "\n");
                $mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
                $mail_message = trim(substr($mail_tpl, $first_crlf));

                $mail_subject = str_replace('<forum_id>', $report['forum_id'], $mail_subject);
                $mail_subject = str_replace('<topic_subject>', $report['subject'], $mail_subject);
                $mail_message = str_replace('<username>', $this->user->username, $mail_message);
                $mail_message = str_replace('<post_url>', get_link('post/'.$post_id.'/#p'.$post_id), $mail_message);
                $mail_message = str_replace('<reason>', $reason, $mail_message);
                $mail_message = str_replace('<board_mailer>', $this->config['o_board_title'], $mail_message);

                require FEATHER_ROOT.'include/email.php';

                pun_mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
            }
        }

        DB::for_table('users')->where('id', $this->user->id)
            ->find_one()
            ->set('last_report_sent', time())
            ->save();

        redirect(get_link('forum/'.$report['forum_id'].'/'.url_friendly($report['subject']).'/'), $lang_misc['Report redirect']);
    }

    public function get_info_report($post_id)
    {
        global $lang_common;

        $select_get_info_report = array('fid' => 'f.id', 'f.forum_name', 'tid' => 't.id', 't.subject');
        $where_get_info_report = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $cur_post = DB::for_table('posts')
            ->table_alias('p')
            ->select_many($select_get_info_report)
            ->inner_join('topics', array('t.id', '=', 'p.topic_id'), 't')
            ->inner_join('forums', array('f.id', '=', 't.forum_id'), 'f')
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($where_get_info_report)
            ->where('p.id', $post_id)
            ->find_one();

        if (!$cur_post) {
            message($lang_common['Bad request'], '404');
        }

        return $cur_post;
    }

    public function subscribe_topic($topic_id)
    {
        global $lang_common, $lang_misc;

        if ($this->config['o_topic_subscriptions'] != '1') {
            message($lang_common['No permission'], '403');
        }

        // Make sure the user can view the topic
        $where_subscribe_topic = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $authorized = DB::for_table('topics')
                    ->table_alias('t')
                    ->left_outer_join('forum_perms', array('fp.forum_id', '=', 't.forum_id'), 'fp')
                    ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
                    ->where_any_is($where_subscribe_topic)
                    ->where('t.id', $topic_id)
                    ->where_null('t.moved_to')
                    ->find_one();

        if (!$authorized) {
            message($lang_common['Bad request'], '404');
        }

        $is_subscribed = DB::for_table('topic_subscriptions')
                        ->where('user_id', $this->user->id)
                        ->where('topic_id', $topic_id)
                        ->find_one();

        if ($is_subscribed) {
            message($lang_misc['Already subscribed topic']);
        }

        $insert_subscribe_topic = array(
            'user_id' => $this->user->id,
            'topic_id'  => $topic_id
        );

        // Insert the subscription
        DB::for_table('topic_subscriptions')
            ->create()
            ->set($insert_subscribe_topic)
            ->save();

        redirect(get_link('topic/'.$topic_id.'/'), $lang_misc['Subscribe redirect']);
    }

    public function unsubscribe_topic($topic_id)
    {
        global $lang_common, $lang_misc;

        if ($this->config['o_topic_subscriptions'] != '1') {
            message($lang_common['No permission'], '403');
        }

        $is_subscribed = DB::for_table('topic_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('topic_id', $topic_id)
            ->find_one();

        if (!$is_subscribed) {
            message($lang_misc['Not subscribed topic']);
        }

        // Delete the subscription
        DB::for_table('topic_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('topic_id', $topic_id)
            ->delete_many();

        redirect(get_link('topic/'.$topic_id.'/'), $lang_misc['Unsubscribe redirect']);
    }

    public function unsubscribe_forum($forum_id)
    {
        global $lang_common, $lang_misc;

        if ($this->config['o_forum_subscriptions'] != '1') {
            message($lang_common['No permission'], '403');
        }

        $is_subscribed = DB::for_table('forum_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('forum_id', $forum_id)
            ->find_one();

        if (!$is_subscribed) {
            message($lang_misc['Not subscribed forum']);
        }

        // Delete the subscription
        DB::for_table('forum_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('forum_id', $forum_id)
            ->delete_many();

        redirect(get_link('forum/'.$forum_id.'/'), $lang_misc['Unsubscribe redirect']);
    }

    public function subscribe_forum($forum_id)
    {
        global $lang_common, $lang_misc;

        if ($this->config['o_forum_subscriptions'] != '1') {
            message($lang_common['No permission'], '403');
        }

        // Make sure the user can view the forum
        $where_subscribe_forum = array(
            array('fp.read_forum' => 'IS NULL'),
            array('fp.read_forum' => '1')
        );

        $authorized = DB::for_table('forums')
            ->table_alias('f')
            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
            ->left_outer_join('forum_perms', array('fp.group_id', '=', $this->user->g_id), null, true)
            ->where_any_is($where_subscribe_forum)
            ->where('f.id', $forum_id)
            ->find_one();

        if (!$authorized) {
            message($lang_common['Bad request'], '404');
        }

        $is_subscribed = DB::for_table('forum_subscriptions')
            ->where('user_id', $this->user->id)
            ->where('forum_id', $forum_id)
            ->find_one();

        if ($is_subscribed) {
            message($lang_misc['Already subscribed forum']);
        }

        $insert_subscribe_forum = array(
            'user_id' => $this->user->id,
            'forum_id'  => $forum_id
        );

        // Insert the subscription
        DB::for_table('forum_subscriptions')
            ->create()
            ->set($insert_subscribe_forum)
            ->save();

        redirect(get_link('forum/'.$forum_id.'/'), $lang_misc['Subscribe redirect']);
    }
}
