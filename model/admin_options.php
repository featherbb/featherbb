<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
function admin_options($post_data)
{
	global $db, $lang_admin_options, $lang_common;
	
    confirm_referrer('admin_options.php', $lang_admin_options['Bad HTTP Referer message']);

    $form = array(
        'board_title'            => pun_trim($post_data['form']['board_title']),
        'board_desc'            => pun_trim($post_data['form']['board_desc']),
        'base_url'                => pun_trim($post_data['form']['base_url']),
        'default_timezone'        => floatval($post_data['form']['default_timezone']),
        'default_dst'            => $post_data['form']['default_dst'] != '1' ? '0' : '1',
        'default_lang'            => pun_trim($post_data['form']['default_lang']),
        'default_style'            => pun_trim($post_data['form']['default_style']),
        'time_format'            => pun_trim($post_data['form']['time_format']),
        'date_format'            => pun_trim($post_data['form']['date_format']),
        'timeout_visit'            => (intval($post_data['form']['timeout_visit']) > 0) ? intval($post_data['form']['timeout_visit']) : 1,
        'timeout_online'        => (intval($post_data['form']['timeout_online']) > 0) ? intval($post_data['form']['timeout_online']) : 1,
        'redirect_delay'        => (intval($post_data['form']['redirect_delay']) >= 0) ? intval($post_data['form']['redirect_delay']) : 0,
        'show_version'            => $post_data['form']['show_version'] != '1' ? '0' : '1',
        'show_user_info'        => $post_data['form']['show_user_info'] != '1' ? '0' : '1',
        'show_post_count'        => $post_data['form']['show_post_count'] != '1' ? '0' : '1',
        'smilies'                => $post_data['form']['smilies'] != '1' ? '0' : '1',
        'smilies_sig'            => $post_data['form']['smilies_sig'] != '1' ? '0' : '1',
        'make_links'            => $post_data['form']['make_links'] != '1' ? '0' : '1',
        'topic_review'            => (intval($post_data['form']['topic_review']) >= 0) ? intval($post_data['form']['topic_review']) : 0,
        'disp_topics_default'    => intval($post_data['form']['disp_topics_default']),
        'disp_posts_default'    => intval($post_data['form']['disp_posts_default']),
        'indent_num_spaces'        => (intval($post_data['form']['indent_num_spaces']) >= 0) ? intval($post_data['form']['indent_num_spaces']) : 0,
        'quote_depth'            => (intval($post_data['form']['quote_depth']) > 0) ? intval($post_data['form']['quote_depth']) : 1,
        'quickpost'                => $post_data['form']['quickpost'] != '1' ? '0' : '1',
        'users_online'            => $post_data['form']['users_online'] != '1' ? '0' : '1',
        'censoring'                => $post_data['form']['censoring'] != '1' ? '0' : '1',
        'signatures'            => $post_data['form']['signatures'] != '1' ? '0' : '1',
        'show_dot'                => $post_data['form']['show_dot'] != '1' ? '0' : '1',
        'topic_views'            => $post_data['form']['topic_views'] != '1' ? '0' : '1',
        'quickjump'                => $post_data['form']['quickjump'] != '1' ? '0' : '1',
        'gzip'                    => $post_data['form']['gzip'] != '1' ? '0' : '1',
        'search_all_forums'        => $post_data['form']['search_all_forums'] != '1' ? '0' : '1',
        'additional_navlinks'    => pun_trim($post_data['form']['additional_navlinks']),
        'feed_type'                => intval($post_data['form']['feed_type']),
        'feed_ttl'                => intval($post_data['form']['feed_ttl']),
        'report_method'            => intval($post_data['form']['report_method']),
        'mailing_list'            => pun_trim($post_data['form']['mailing_list']),
        'avatars'                => $post_data['form']['avatars'] != '1' ? '0' : '1',
        'avatars_dir'            => pun_trim($post_data['form']['avatars_dir']),
        'avatars_width'            => (intval($post_data['form']['avatars_width']) > 0) ? intval($post_data['form']['avatars_width']) : 1,
        'avatars_height'        => (intval($post_data['form']['avatars_height']) > 0) ? intval($post_data['form']['avatars_height']) : 1,
        'avatars_size'            => (intval($post_data['form']['avatars_size']) > 0) ? intval($post_data['form']['avatars_size']) : 1,
        'admin_email'            => strtolower(pun_trim($post_data['form']['admin_email'])),
        'webmaster_email'        => strtolower(pun_trim($post_data['form']['webmaster_email'])),
        'forum_subscriptions'    => $post_data['form']['forum_subscriptions'] != '1' ? '0' : '1',
        'topic_subscriptions'    => $post_data['form']['topic_subscriptions'] != '1' ? '0' : '1',
        'smtp_host'                => pun_trim($post_data['form']['smtp_host']),
        'smtp_user'                => pun_trim($post_data['form']['smtp_user']),
        'smtp_ssl'                => $post_data['form']['smtp_ssl'] != '1' ? '0' : '1',
        'regs_allow'            => $post_data['form']['regs_allow'] != '1' ? '0' : '1',
        'regs_verify'            => $post_data['form']['regs_verify'] != '1' ? '0' : '1',
        'regs_report'            => $post_data['form']['regs_report'] != '1' ? '0' : '1',
        'rules'                    => $post_data['form']['rules'] != '1' ? '0' : '1',
        'rules_message'            => pun_trim($post_data['form']['rules_message']),
        'default_email_setting'    => intval($post_data['form']['default_email_setting']),
        'announcement'            => $post_data['form']['announcement'] != '1' ? '0' : '1',
        'announcement_message'    => pun_trim($post_data['form']['announcement_message']),
        'maintenance'            => $post_data['form']['maintenance'] != '1' ? '0' : '1',
        'maintenance_message'    => pun_trim($post_data['form']['maintenance_message']),
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


    require PUN_ROOT.'include/email.php';

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
    if (isset($post_data['form']['smtp_change_pass'])) {
        $smtp_pass1 = isset($post_data['form']['smtp_pass1']) ? pun_trim($post_data['form']['smtp_pass1']) : '';
        $smtp_pass2 = isset($post_data['form']['smtp_pass2']) ? pun_trim($post_data['form']['smtp_pass2']) : '';

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
        if (array_key_exists('o_'.$key, $pun_config) && $pun_config['o_'.$key] != $input) {
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
        require PUN_ROOT.'include/cache.php';
    }

    generate_config_cache();
    clear_feed_cache();

    redirect('admin_options.php', $lang_admin_options['Options updated redirect']);
}

function get_styles()
{
	$styles = forum_list_styles();

	foreach ($styles as $temp) {
		if ($pun_config['o_default_style'] == $temp) {
			echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
		} else {
			echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
		}
	}
}

function get_times()
{
	$times = array(5, 15, 30, 60);

	foreach ($times as $time) {
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$time.'"'.($pun_config['o_feed_ttl'] == $time ? ' selected="selected"' : '').'>'.sprintf($lang_admin_options['Minutes'], $time).'</option>'."\n";
	}
}