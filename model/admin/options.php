<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function update_options($feather)
{
    global $db, $lang_admin_options, $lang_common, $feather_config;
    
    confirm_referrer(get_link_r('admin/options/'), $lang_admin_options['Bad HTTP Referer message']);

    $form = array(
        'board_title'            => pun_trim($feather->request->post('form_board_title')),
        'board_desc'            => pun_trim($feather->request->post('form_board_desc')),
        'base_url'                => pun_trim($feather->request->post('form_base_url')),
        'default_timezone'        => floatval($feather->request->post('form_default_timezone')),
        'default_dst'            => $feather->request->post('form_default_dst') != '1' ? '0' : '1',
        'default_lang'            => pun_trim($feather->request->post('form_default_lang')),
        'default_style'            => pun_trim($feather->request->post('form_default_style')),
        'time_format'            => pun_trim($feather->request->post('form_time_format')),
        'date_format'            => pun_trim($feather->request->post('form_date_format')),
        'timeout_visit'            => (intval($feather->request->post('form_timeout_visit')) > 0) ? intval($feather->request->post('form_timeout_visit')) : 1,
        'timeout_online'        => (intval($feather->request->post('form_timeout_online')) > 0) ? intval($feather->request->post('form_timeout_online')) : 1,
        'redirect_delay'        => (intval($feather->request->post('form_redirect_delay')) >= 0) ? intval($feather->request->post('form_redirect_delay')) : 0,
        'show_version'            => $feather->request->post('form_show_version') != '1' ? '0' : '1',
        'show_user_info'        => $feather->request->post('form_show_user_info') != '1' ? '0' : '1',
        'show_post_count'        => $feather->request->post('form_show_post_count') != '1' ? '0' : '1',
        'smilies'                => $feather->request->post('form_smilies') != '1' ? '0' : '1',
        'smilies_sig'            => $feather->request->post('form_smilies_sig') != '1' ? '0' : '1',
        'make_links'            => $feather->request->post('form_make_links') != '1' ? '0' : '1',
        'topic_review'            => (intval($feather->request->post('form_topic_review')) >= 0) ? intval($feather->request->post('form_topic_review')) : 0,
        'disp_topics_default'    => intval($feather->request->post('form_disp_topics_default')),
        'disp_posts_default'    => intval($feather->request->post('form_disp_posts_default')),
        'indent_num_spaces'        => (intval($feather->request->post('form_indent_num_spaces')) >= 0) ? intval($feather->request->post('form_indent_num_spaces')) : 0,
        'quote_depth'            => (intval($feather->request->post('form_quote_depth')) > 0) ? intval($feather->request->post('form_quote_depth')) : 1,
        'quickpost'                => $feather->request->post('form_quickpost') != '1' ? '0' : '1',
        'users_online'            => $feather->request->post('form_users_online') != '1' ? '0' : '1',
        'censoring'                => $feather->request->post('form_censoring') != '1' ? '0' : '1',
        'signatures'            => $feather->request->post('form_signatures') != '1' ? '0' : '1',
        'show_dot'                => $feather->request->post('form_show_dot') != '1' ? '0' : '1',
        'topic_views'            => $feather->request->post('form_topic_views') != '1' ? '0' : '1',
        'quickjump'                => $feather->request->post('form_quickjump') != '1' ? '0' : '1',
        'gzip'                    => $feather->request->post('form_gzip') != '1' ? '0' : '1',
        'search_all_forums'        => $feather->request->post('form_search_all_forums') != '1' ? '0' : '1',
        'additional_navlinks'    => pun_trim($feather->request->post('form_additional_navlinks')),
        'feed_type'                => intval($feather->request->post('form_feed_type')),
        'feed_ttl'                => intval($feather->request->post('form_feed_ttl')),
        'report_method'            => intval($feather->request->post('form_report_method')),
        'mailing_list'            => pun_trim($feather->request->post('form_mailing_list')),
        'avatars'                => $feather->request->post('form_avatars') != '1' ? '0' : '1',
        'avatars_dir'            => pun_trim($feather->request->post('form_avatars_dir')),
        'avatars_width'            => (intval($feather->request->post('form_avatars_width')) > 0) ? intval($feather->request->post('form_avatars_width')) : 1,
        'avatars_height'        => (intval($feather->request->post('form_avatars_height')) > 0) ? intval($feather->request->post('form_avatars_height')) : 1,
        'avatars_size'            => (intval($feather->request->post('form_avatars_size')) > 0) ? intval($feather->request->post('form_avatars_size')) : 1,
        'admin_email'            => strtolower(pun_trim($feather->request->post('form_admin_email'))),
        'webmaster_email'        => strtolower(pun_trim($feather->request->post('form_webmaster_email'))),
        'forum_subscriptions'    => $feather->request->post('form_forum_subscriptions') != '1' ? '0' : '1',
        'topic_subscriptions'    => $feather->request->post('form_topic_subscriptions') != '1' ? '0' : '1',
        'smtp_host'                => pun_trim($feather->request->post('form_smtp_host')),
        'smtp_user'                => pun_trim($feather->request->post('form_smtp_user')),
        'smtp_ssl'                => $feather->request->post('form_smtp_ssl') != '1' ? '0' : '1',
        'regs_allow'            => $feather->request->post('form_regs_allow') != '1' ? '0' : '1',
        'regs_verify'            => $feather->request->post('form_regs_verify') != '1' ? '0' : '1',
        'regs_report'            => $feather->request->post('form_regs_report') != '1' ? '0' : '1',
        'rules'                    => $feather->request->post('form_rules') != '1' ? '0' : '1',
        'rules_message'            => pun_trim($feather->request->post('form_rules_message')),
        'default_email_setting'    => intval($feather->request->post('form_default_email_setting')),
        'announcement'            => $feather->request->post('form_announcement') != '1' ? '0' : '1',
        'announcement_message'    => pun_trim($feather->request->post('form_announcement_message')),
        'maintenance'            => $feather->request->post('form_maintenance') != '1' ? '0' : '1',
        'maintenance_message'    => pun_trim($feather->request->post('form_maintenance_message')),
    );

    if ($form['board_title'] == '') {
        message($lang_admin_options['Must enter title message']);
    }

    // Make sure base_url doesn't end with a slash
    if (substr($form['base_url'], -1) == '/') {
        $form['base_url'] = substr($form['base_url'], 0, -1);
    }
        
    // Convert IDN to Punycode if needed
    if (preg_match('/[^\x00-\x7F]/', $form['base_url'])) {
        if (!function_exists('idn_to_ascii')) {
            message($lang_admin_options['Base URL problem']);
        } else {
            $form['base_url'] = idn_to_ascii($form['base_url']);
        }
    }

    $languages = forum_list_langs();
    if (!in_array($form['default_lang'], $languages)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    $styles = forum_list_styles();
    if (!in_array($form['default_style'], $styles)) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    if ($form['time_format'] == '') {
        $form['time_format'] = 'H:i:s';
    }

    if ($form['date_format'] == '') {
        $form['date_format'] = 'Y-m-d';
    }


    require FEATHER_ROOT.'include/email.php';

    if (!is_valid_email($form['admin_email'])) {
        message($lang_admin_options['Invalid e-mail message']);
    }

    if (!is_valid_email($form['webmaster_email'])) {
        message($lang_admin_options['Invalid webmaster e-mail message']);
    }

    if ($form['mailing_list'] != '') {
        $form['mailing_list'] = strtolower(preg_replace('%\s%S', '', $form['mailing_list']));
    }

    // Make sure avatars_dir doesn't end with a slash
    if (substr($form['avatars_dir'], -1) == '/') {
        $form['avatars_dir'] = substr($form['avatars_dir'], 0, -1);
    }

    if ($form['additional_navlinks'] != '') {
        $form['additional_navlinks'] = pun_trim(pun_linebreaks($form['additional_navlinks']));
    }

    // Change or enter a SMTP password
    if ($feather->request->post('form_smtp_change_pass')) {
        $smtp_pass1 = $feather->request->post('form_smtp_pass1') ? pun_trim($feather->request->post('form_smtp_pass1')) : '';
        $smtp_pass2 = $feather->request->post('form_smtp_pass2') ? pun_trim($feather->request->post('form_smtp_pass2')) : '';

        if ($smtp_pass1 == $smtp_pass2) {
            $form['smtp_pass'] = $smtp_pass1;
        } else {
            message($lang_admin_options['SMTP passwords did not match']);
        }
    }

    if ($form['announcement_message'] != '') {
        $form['announcement_message'] = pun_linebreaks($form['announcement_message']);
    } else {
        $form['announcement_message'] = $lang_admin_options['Enter announcement here'];
        $form['announcement'] = '0';
    }

    if ($form['rules_message'] != '') {
        $form['rules_message'] = pun_linebreaks($form['rules_message']);
    } else {
        $form['rules_message'] = $lang_admin_options['Enter rules here'];
        $form['rules'] = '0';
    }

    if ($form['maintenance_message'] != '') {
        $form['maintenance_message'] = pun_linebreaks($form['maintenance_message']);
    } else {
        $form['maintenance_message'] = $lang_admin_options['Default maintenance message'];
        $form['maintenance'] = '0';
    }

    // Make sure the number of displayed topics and posts is between 3 and 75
    if ($form['disp_topics_default'] < 3) {
        $form['disp_topics_default'] = 3;
    } elseif ($form['disp_topics_default'] > 75) {
        $form['disp_topics_default'] = 75;
    }

    if ($form['disp_posts_default'] < 3) {
        $form['disp_posts_default'] = 3;
    } elseif ($form['disp_posts_default'] > 75) {
        $form['disp_posts_default'] = 75;
    }

    if ($form['feed_type'] < 0 || $form['feed_type'] > 2) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    if ($form['feed_ttl'] < 0) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    if ($form['report_method'] < 0 || $form['report_method'] > 2) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    if ($form['default_email_setting'] < 0 || $form['default_email_setting'] > 2) {
        message($lang_common['Bad request'], false, '404 Not Found');
    }

    if ($form['timeout_online'] >= $form['timeout_visit']) {
        message($lang_admin_options['Timeout error message']);
    }

    foreach ($form as $key => $input) {
        // Only update values that have changed
        if (array_key_exists('o_'.$key, $feather_config) && $feather_config['o_'.$key] != $input) {
            if ($input != '' || is_int($input)) {
                $value = '\''.$db->escape($input).'\'';
            } else {
                $value = 'NULL';
            }

            $db->query('UPDATE '.$db->prefix.'config SET conf_value='.$value.' WHERE conf_name=\'o_'.$db->escape($key).'\'') or error('Unable to update board config', __FILE__, __LINE__, $db->error());
        }
    }

    // Regenerate the config cache
    if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
        require FEATHER_ROOT.'include/cache.php';
    }

    generate_config_cache();
    clear_feed_cache();

    redirect(get_link('admin/options/'), $lang_admin_options['Options updated redirect']);
}

function get_styles()
{
    global $feather_config;

    $styles = forum_list_styles();

    foreach ($styles as $temp) {
        if ($feather_config['o_default_style'] == $temp) {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
        } else {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
        }
    }
}

function get_times()
{
    global $feather_config, $lang_admin_options;

    $times = array(5, 15, 30, 60);

    foreach ($times as $time) {
        echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$time.'"'.($feather_config['o_feed_ttl'] == $time ? ' selected="selected"' : '').'>'.sprintf($lang_admin_options['Minutes'], $time).'</option>'."\n";
    }
}
