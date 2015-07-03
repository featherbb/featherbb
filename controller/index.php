<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

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
    
    public function display()
    {
        global $lang_common;
        
        // Backward compatibility - to be removed
        $db = $this->db;
        $feather_config = $this->config;
        $feather_user = $this->user;

        if ($this->user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        }

        // Load the index.php language file
        require FEATHER_ROOT.'lang/'.$this->user['language'].'/index.php';

        $page_title = array(pun_htmlspecialchars($this->config['o_board_title']));
        define('PUN_ALLOW_INDEX', 1);

        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'index');
        }

        require FEATHER_ROOT.'include/header.php';

        // Load the index.php model file
        require FEATHER_ROOT.'model/index.php';

        $this->feather->render('header.php', array(
                            'lang_common' => $lang_common,
                            'page_title' => $page_title,
                            'p' => $p,
                            'feather_user' => $this->user,
                            'feather_config' => $this->config,
                            '_SERVER'    =>    $_SERVER,
                            'page_head'        =>    get_page_head(),
                            'navlinks'        =>    $navlinks,
                            'page_info'        =>    $page_info,
                            'db'        =>    $this->db,
                            )
                    );

        $this->feather->render('index.php', array(
                            'index_data' => print_categories_forums(),
                            'lang_common' => $lang_common,
                            'lang_index' => $lang_index,
                            'stats' => collect_stats(),
                            'feather_config' => $this->config,
                            'online'    =>    fetch_users_online(),
                            'forum_actions'        =>    get_forum_actions(),
                            )
                    );

        $this->feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'feather_user' => $this->user,
                            'feather_config' => $this->config,
                            'feather_start' => $this->start,
                            'footer_style' => 'index',
                            )
                    );

        require FEATHER_ROOT.'include/footer.php';
    }
}
