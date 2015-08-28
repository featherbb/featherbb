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
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'lang/'.$this->user->language.'/admin/index.mo');
    }

    public function __autoload($class_name)
    {
        require $this->feather->forum_env['FEATHER_ROOT'] . $class_name . '.php';
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
        if (!$this->user->is_admmod) {
            message(__('No permission'), '403');
        }

        // Check for upgrade
        if ($action == 'check_upgrade') {
            if (!ini_get('allow_url_fopen')) {
                message(__('fopen disabled message'));
            }

            $latest_version = trim(@file_get_contents('http://featherbb.org/latest_version'));
            if (empty($latest_version)) {
                message(__('Upgrade check failed message'));
            }

            if (version_compare($this->config['o_cur_version'], $latest_version, '>=')) {
                message(__('Running latest version message'));
            } else {
                message(sprintf(__('New version available message'), '<a href="http://featherbb.org/">FeatherBB.org</a>'));
            }
        }
        // Remove /install
        elseif ($action == 'remove_install_file') {
            $deleted = $this->remove_install_folder($this->feather->forum_env['FEATHER_ROOT'].'install');

            if ($deleted) {
                redirect(get_link('admin/'), __('Deleted install.php redirect'));
            } else {
                message(__('Delete install.php failed'));
            }
        }

        \FeatherBB\AdminUtils::generateAdminMenu('index');

        $this->feather->view2->setPageInfo(array(
                            'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), __('Index')),
                            'active_page' => 'admin',
                            'admin_console' => true,
                            'install_file_exists'    =>   is_dir($this->feather->forum_env['FEATHER_ROOT'].'install'),
                            )
                    )->addTemplate('admin/index.php')->display();
    }
}
