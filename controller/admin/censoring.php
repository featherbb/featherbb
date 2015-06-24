<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin{
    
    class Censoring{

        function display(){
			
			global $feather, $lang_common, $lang_admin_common, $lang_admin_censoring, $pun_config, $pun_user, $pun_start, $db;

			require PUN_ROOT.'include/common_admin.php';

            if ($pun_user['g_id'] != PUN_ADMIN) {
                message($lang_common['No permission'], false, '403 Forbidden');
            }

            define('PUN_ADMIN_CONSOLE', 1);

            // Load the admin_options.php language file
            require PUN_ROOT.'lang/'.$admin_language.'/censoring.php';

            // Load the categories.php model file
            require PUN_ROOT.'model/admin/censoring.php';
			
			// Add a censor word
			if (!empty($feather->request->post('add_word'))) {
				add_word($feather);
			}

			// Update a censor word
			elseif (!empty($feather->request->post('update'))) {
				update_word($feather);
			}

			// Remove a censor word
			elseif (!empty($feather->request->post('remove'))) {
				remove_word($feather);
			}

			$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Censoring']);
			$focus_element = array('censoring', 'new_search_for');
            if (!defined('PUN_ACTIVE_PAGE')) {
                define('PUN_ACTIVE_PAGE', 'admin');
            }
            require PUN_ROOT.'include/header.php';

            $feather->render('header.php', array(
                    'lang_common' => $lang_common,
                    'page_title' => $page_title,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    '_SERVER'	=>	$_SERVER,
                    'navlinks'		=>	$navlinks,
                    'page_info'		=>	$page_info,
                    'db'		=>	$db,
                    'p'		=>	'',
					'focus_element'	=>	$focus_element,
                )
            );

			generate_admin_menu('censoring');
			
            $feather->render('admin/censoring.php', array(
                    'lang_admin_censoring'	=>	$lang_admin_censoring,
                    'lang_admin_common'	=>	$lang_admin_common,
                    'pun_config'	=>	$pun_config,
                    'word_data'	=>	get_words(),
                )
            );

            $feather->render('footer.php', array(
                    'lang_common' => $lang_common,
                    'pun_user' => $pun_user,
                    'pun_config' => $pun_config,
                    'pun_start' => $pun_start,
                    'footer_style' => 'index',
                )
            );

            require PUN_ROOT.'include/footer.php';

		}
    }
}