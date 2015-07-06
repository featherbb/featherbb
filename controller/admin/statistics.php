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
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_index, $feather_config, $feather_user, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if (!$feather_user['is_admmod']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_index.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/index.php';

        // Load the statistics.php model file
        require FEATHER_ROOT.'model/admin/statistics.php';

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Server statistics']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT.'include/header.php';

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

        require FEATHER_ROOT.'include/footer.php';
    }


    public function phpinfo()
    {
        global $lang_common, $lang_admin_common, $lang_admin_index, $feather_config, $feather_user, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        // Load the admin_index.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/index.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN) {
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
