<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class options
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
        global $lang_common, $lang_admin_common, $feather_config, $feather_user, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/options.php';

        // Load the admin_options.php model file
        require FEATHER_ROOT.'model/admin/options.php';

        if ($this->feather->request->isPost()) {
            update_options($this->feather);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Options']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT.'include/header.php';

        generate_admin_menu('options');

        $this->feather->render('admin/options.php', array(
                'lang_admin_options'    =>    $lang_admin_options,
                'feather_config'    =>    $feather_config,
                'feather_user'    =>    $feather_user,
            )
        );

        require FEATHER_ROOT.'include/footer.php';
    }
}
