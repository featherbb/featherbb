<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class help
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
    }
    
    public function display()
    {
        global $lang_common, $feather_config, $feather_user, $feather_start, $db;

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        }


        // Load the help.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/help.php';


        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_help['Help']);

        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'help');
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

        $this->feather->render('help.php', array(
                            'lang_help' => $lang_help,
                            'lang_common' => $lang_common,
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
