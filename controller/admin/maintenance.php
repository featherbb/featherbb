<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class maintenance
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_maintenance, $lang_admin_common, $feather_config, $feather_user, $feather_start, $db;

        require FEATHER_ROOT.'include/common_admin.php';

        if ($feather_user['g_id'] != PUN_ADMIN) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('PUN_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/maintenance.php';

        // Load the admin_options.php model file
        require FEATHER_ROOT.'model/admin/maintenance.php';

        $action = '';
        if ($this->feather->request->post('action')) {
            $action = $this->feather->request->post('action');
        } elseif ($this->feather->request->get('action')) {
            $action = $this->feather->request->get('action');
        }

        if ($action == 'rebuild') {
            rebuild($this->feather);

            $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_maintenance['Rebuilding search index']);

            $this->feather->render('admin/maintenance/rebuild.php', array(
                    'lang_admin_maintenance'    =>    $lang_admin_maintenance,
                    'page_title'    =>    $page_title,
                )
            );

            $query_str = get_query_str($this->feather);

            exit('<script type="text/javascript">window.location="'.get_link('admin/maintenance/').$query_str.'"</script><hr /><p>'.sprintf($lang_admin_maintenance['Javascript redirect failed'], '<a href="'.get_link('admin/maintenance/').$query_str.'">'.$lang_admin_maintenance['Click here'].'</a>').'</p>');
        }

        if ($action == 'prune') {
            $prune_from = pun_trim($this->feather->request->post('prune_from'));
            $prune_sticky = intval($this->feather->request->post('prune_sticky'));

            if ($this->feather->request->post('prune_comply')) {
                prune_comply($this->feather, $prune_from, $prune_sticky);
            }

            $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Prune']);
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

            generate_admin_menu('maintenance');

            $this->feather->render('admin/maintenance/prune.php', array(
                    'lang_admin_maintenance'    =>    $lang_admin_maintenance,
                    'lang_admin_common'    =>    $lang_admin_common,
                    'prune_sticky'    =>    $prune_sticky,
                    'prune_from'    =>    $prune_from,
                    'prune' => get_info_prune($this->feather, $prune_sticky, $prune_from),
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

        // Get the first post ID from the db
        $first_id = '';
        $result = $db->query('SELECT id FROM '.$db->prefix.'posts ORDER BY id ASC LIMIT 1') or error('Unable to fetch topic info', __FILE__, __LINE__, $db->error());
        if ($db->num_rows($result)) {
            $first_id = $db->result($result);
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Maintenance']);
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

        generate_admin_menu('maintenance');

        $this->feather->render('admin/maintenance/admin_maintenance.php', array(
                'lang_admin_maintenance'    =>    $lang_admin_maintenance,
                'lang_admin_common'    =>    $lang_admin_common,
                'first_id' => $first_id,
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
}
