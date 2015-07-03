<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class statistics
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_index, $feather_config, $feather_user, $feather_start, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if (!$feather_user['is_admmod']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('PUN_ADMIN_CONSOLE', 1);

        // Load the admin_index.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/index.php';

        // Load the statistics.php model file
        require FEATHER_ROOT.'model/admin/statistics.php';

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Server statistics']);
        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'admin');
        }
        require FEATHER_ROOT.'include/header.php';

        $this->feather->render('header.php', array(
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

        generate_admin_menu('index');

        $this->feather->render('admin/statistics.php', array(
                'lang_admin_common'    =>    $lang_admin_common,
                'lang_admin_index'    =>    $lang_admin_index,
                'feather_config'    =>    $feather_config,
                'server_load'    =>    get_server_load(),
                'num_online'    =>    get_num_online(),
                'total_size'    =>    get_total_size(),
                'php_accelerator'    =>    get_php_accelerator(),
            )
        );

        $this->feather->render('footer.php', array(
                'lang_common' => $lang_common,
                'feather_user' => $feather_user,
                'feather_config' => $feather_config,
                'feather_start' => $feather_start,
                'footer_style' => 'index',
            )
        );

        require FEATHER_ROOT.'include/footer.php';
    }


    public function phpinfo()
    {
        global $lang_common, $lang_admin_common, $lang_admin_index, $feather_config, $feather_user, $feather_start, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        // Load the admin_index.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/index.php';

        if ($feather_user['g_id'] != PUN_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

                    // Show phpinfo() output
                    // Is phpinfo() a disabled function?
                    if (strpos(strtolower((string) ini_get('disable_functions')), 'phpinfo') !== false) {
                        message($lang_admin_index['PHPinfo disabled message']);
                    }

        phpinfo();
        $this->feather->stop();
    }
}
