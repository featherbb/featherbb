<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class censoring
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
        global $lang_common, $lang_admin_common, $lang_admin_censoring, $feather_config, $feather_user, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($feather_user['g_id'] != FEATHER_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/censoring.php';

        // Load the categories.php model file
        require FEATHER_ROOT.'model/admin/censoring.php';

        // Add a censor word
        if ($this->feather->request->post('add_word')) {
            add_word($this->feather);
        }

        // Update a censor word
        elseif ($this->feather->request->post('update')) {
            update_word($this->feather);
        }

        // Remove a censor word
        elseif ($this->feather->request->post('remove')) {
            remove_word($this->feather);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Censoring']);
        $focus_element = array('censoring', 'new_search_for');

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT.'include/header.php';

        generate_admin_menu('censoring');

        $this->feather->render('admin/censoring.php', array(
                'lang_admin_censoring'    =>    $lang_admin_censoring,
                'lang_admin_common'    =>    $lang_admin_common,
                'feather_config'    =>    $feather_config,
                'word_data'    =>    get_words(),
            )
        );

        require FEATHER_ROOT.'include/footer.php';
    }
}
