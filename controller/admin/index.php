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
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function remove_install_folder($directory)
    {
        foreach(glob("{$directory}/*") as $file)
        {
            if(is_dir($file)) {
                $this->remove_install_folder($file);
            } else {
                unlink($file);
            }
        }
        $deleted = rmdir($directory);

        return $deleted;
    }
    
    public function display($action = null)
    {
        global $lang_common, $lang_admin_common, $lang_admin_index;

        require FEATHER_ROOT.'include/common_admin.php';

        if (!$this->user->is_admmod) {
            message($lang_common['No permission'], '403');
        }

        // Load the admin_index.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/index.php';

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Check for upgrade
        if ($action == 'check_upgrade') {
            if (!ini_get('allow_url_fopen')) {
                message($lang_admin_index['fopen disabled message']);
            }

            $latest_version = trim(@file_get_contents('http://featherbb.org/latest_version'));
            if (empty($latest_version)) {
                message($lang_admin_index['Upgrade check failed message']);
            }

            if (version_compare($this->config['o_cur_version'], $latest_version, '>=')) {
                message($lang_admin_index['Running latest version message']);
            } else {
                message(sprintf($lang_admin_index['New version available message'], '<a href="http://featherbb.org/">FeatherBB.org</a>'));
            }
        }
        // Remove /install
        elseif ($action == 'remove_install_file') {
            $deleted = $this->remove_install_folder(FEATHER_ROOT.'install');

            if ($deleted) {
                redirect(get_link('admin/'), $lang_admin_index['Deleted install.php redirect']);
            } else {
                message($lang_admin_index['Delete install.php failed']);
            }
        }

        $install_folder_exists = is_dir(FEATHER_ROOT.'install');

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Index']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->setTitle($page_title)->display();

        generate_admin_menu('index');

        $this->feather->render('admin/index.php', array(
                            'lang_admin_index'    =>    $lang_admin_index,
                            'install_file_exists'    =>    $install_folder_exists,
                            'feather_config'    =>    $this->config,
                            )
                    );

        $this->footer->display();
    }
}
