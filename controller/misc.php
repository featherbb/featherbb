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
    public function rules()
    {
        global $feather, $lang_common, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_config['o_rules'] == '0' || ($feather_user['is_guest'] && $feather_user['g_read_board'] == '0' && $feather_config['o_regs_allow'] == '0')) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Load the register.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/register.php';

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_register['Forum rules']);

        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'rules');
        }

        $feather->render('header.php', array(
                'lang_common' => $lang_common,
                'page_title' => $page_title,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                '_SERVER'    =>    $_SERVER,
                'navlinks'        =>    $navlinks,
                'page_info'        =>    $page_info,
                'db'        =>    $db,
                'p'        =>    '',
                )
        );

        $feather->render('misc/rules.php', array(
                'lang_register' => $lang_register,
                'feather_config' => $feather_config,
                )
        );

        $feather->render('footer.php', array(
                'lang_common' => $lang_common,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                'feather_start' => $feather_start,
                'footer_style' => 'index',
                )
        );

        require FEATHER_ROOT.'include/footer.php';
    }

    public function markread()
    {
        global $feather, $lang_common, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['is_guest']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the misc.php model file
        require FEATHER_ROOT.'model/misc.php';

        update_last_visit();

        // Reset tracked topics
        set_tracked_topics(null);

        redirect(get_base_url(), $lang_misc['Mark read redirect']);
    }

    public function markforumread($id)
    {
        global $feather, $lang_common, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['is_guest']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the misc.php model file
        require FEATHER_ROOT.'model/misc.php';

        $tracked_topics = get_tracked_topics();
        $tracked_topics['forums'][$id] = time();
        set_tracked_topics($tracked_topics);

        redirect(get_link('forum/'.$id.'/'), $lang_misc['Mark forum read redirect']);
    }

    public function subscribeforum($id)
    {
        global $feather, $lang_common, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['is_guest']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the misc.php model file
        require FEATHER_ROOT.'model/misc.php';

        subscribe_forum($id);
    }

    public function subscribetopic($id)
    {
        global $feather, $lang_common, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['is_guest']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the misc.php model file
        require FEATHER_ROOT.'model/misc.php';

        subscribe_topic($id);
    }

    public function unsubscribeforum($id)
    {
        global $feather, $lang_common, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['is_guest']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the misc.php model file
        require FEATHER_ROOT.'model/misc.php';

        unsubscribe_forum($id);
    }

    public function unsubscribetopic($id)
    {
        global $feather, $lang_common, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['is_guest']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the misc.php model file
        require FEATHER_ROOT.'model/misc.php';

        unsubscribe_topic($id);
    }

    public function email($id)
    {
        global $feather, $lang_common, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['is_guest'] || $feather_user['g_send_email'] == '0') {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        if ($id < 2) {
            message($lang_common['Bad request'], false, '404 Not Found');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the misc.php model file
        require FEATHER_ROOT.'model/misc.php';

        $mail = get_info_mail($id);

        if ($mail['email_setting'] == 2 && !$feather_user['is_admmod']) {
            message($lang_misc['Form email disabled']);
        }


        if ($feather->request()->isPost()) {
            send_email($feather, $mail, $id);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_misc['Send email to'].' '.pun_htmlspecialchars($mail['recipient']));
        $required_fields = array('req_subject' => $lang_misc['Email subject'], 'req_message' => $lang_misc['Email message']);
        $focus_element = array('email', 'req_subject');

        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'email');
        }

        require FEATHER_ROOT.'include/header.php';

        $feather->render('header.php', array(
                'lang_common' => $lang_common,
                'page_title' => $page_title,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                '_SERVER'    =>    $_SERVER,
                'navlinks'        =>    $navlinks,
                'page_info'        =>    $page_info,
                'db'        =>    $db,
                'p'        =>    '',
                'required_fields'        =>    $required_fields,
                'focus_element'    =>    $focus_element,
                )
        );

        $feather->render('misc/email.php', array(
                'lang_misc' => $lang_misc,
                'id' => $id,
                'mail' => $mail,
                )
        );

        $feather->render('footer.php', array(
                'lang_common' => $lang_common,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                'feather_start' => $feather_start,
                'footer_style' => 'index',
                )
        );

        require FEATHER_ROOT.'include/footer.php';
    }

    public function report($id)
    {
        global $feather, $lang_common, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['is_guest']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        // Load the misc.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/misc.php';

        // Load the misc.php model file
        require FEATHER_ROOT.'model/misc.php';

        if ($feather->request()->isPost()) {
            insert_report($feather, $id);
        }

        // Fetch some info about the post, the topic and the forum
        $cur_post = get_info_report($id);

        if ($feather_config['o_censoring'] == '1') {
            $cur_post['subject'] = censor_words($cur_post['subject']);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_misc['Report post']);
        $required_fields = array('req_reason' => $lang_misc['Reason']);
        $focus_element = array('report', 'req_reason');

        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'report');
        }

        require FEATHER_ROOT.'include/header.php';

        $feather->render('header.php', array(
                'lang_common' => $lang_common,
                'page_title' => $page_title,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                '_SERVER'    =>    $_SERVER,
                'navlinks'        =>    $navlinks,
                'page_info'        =>    $page_info,
                'db'        =>    $db,
                'p'        =>    '',
                'required_fields'        =>    $required_fields,
                'focus_element'    =>    $focus_element,
                )
        );

        $feather->render('misc/report.php', array(
                'lang_misc' => $lang_misc,
                'id' => $id,
                'lang_common' => $lang_common,
                'cur_post' => $cur_post,
                )
        );

        $feather->render('footer.php', array(
                'lang_common' => $lang_common,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                'feather_start' => $feather_start,
                'footer_style' => 'index',
                )
        );

        require FEATHER_ROOT.'include/footer.php';
    }
}
