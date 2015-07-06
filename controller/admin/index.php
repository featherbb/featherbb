<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class index
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
    }
    
    public function display($action = null)
    {
        global $lang_common, $lang_admin_common, $feather_config, $feather_user, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if (!$feather_user['is_admmod']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

                    // Load the admin_index.php language file
                    require FEATHER_ROOT.'lang/'.$admin_language.'/index.php';

                    // Check for upgrade
                    if ($action == 'check_upgrade') {
                        if (!ini_get('allow_url_fopen')) {
                            message($lang_admin_index['fopen disabled message']);
                        }

                        $latest_version = trim(@file_get_contents('http://featherbb.org/latest_version'));
                        if (empty($latest_version)) {
                            message($lang_admin_index['Upgrade check failed message']);
                        }

                        if (version_compare($feather_config['o_cur_version'], $latest_version, '>=')) {
                            message($lang_admin_index['Running latest version message']);
                        } else {
                            message(sprintf($lang_admin_index['New version available message'], '<a href="http://featherbb.org/">FeatherBB.org</a>'));
                        }
                    }
                    // Remove install.php
                    elseif ($action == 'remove_install_file') {
                        $deleted = @unlink(FEATHER_ROOT.'install.php');

                        if ($deleted) {
                            redirect(get_link('admin/'), $lang_admin_index['Deleted install.php redirect']);
                        } else {
                            message($lang_admin_index['Delete install.php failed']);
                        }
                    }

        $install_file_exists = is_file(FEATHER_ROOT.'install.php');

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Index']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        require FEATHER_ROOT.'include/header.php';

        generate_admin_menu('index');

        $this->feather->render('admin/index.php', array(
                            'lang_admin_index'    =>    $lang_admin_index,
                            'install_file_exists'    =>    $install_file_exists,
                            'feather_config'    =>    $feather_config,
                            )
                    );

        require FEATHER_ROOT.'include/footer.php';
    }
}
