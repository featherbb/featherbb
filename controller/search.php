<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class search
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
    }
    
    public function display()
    {
        global $lang_common, $lang_search, $feather_config, $feather_user, $feather_start, $db, $pd;

        // Load the search.php language file
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/search.php';
        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/forum.php';

        if ($feather_user['g_read_board'] == '0') {
            message($lang_common['No view'], false, '403 Forbidden');
        } elseif ($feather_user['g_search'] == '0') {
            message($lang_search['No search permission'], false, '403 Forbidden');
        }

        // Load the search.php model file
        require FEATHER_ROOT.'model/search.php';

        require FEATHER_ROOT.'include/search_idx.php';

        // Figure out what to do :-)
        if ($this->feather->request->get('action') || ($this->feather->request->get('search_id'))) {
            $search = get_search_results($this->feather);

                // We have results to display
                if ($search['is_result']) {
                    $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_search['Search results']);

                    if (!defined('PUN_ACTIVE_PAGE')) {
                        define('PUN_ACTIVE_PAGE', 'search');
                    }

                    require FEATHER_ROOT.'include/header.php';

                    $this->feather->render('header.php', array(
                                'lang_common' => $lang_common,
                                'page_title' => $page_title,
                                'p' => $p,
                                'feather_user' => $feather_user,
                                'feather_config' => $feather_config,
                                '_SERVER'    =>    $_SERVER,
                                'page_head'        =>    '',
                                'navlinks'        =>    $navlinks,
                                'page_info'        =>    $page_info,
                                'focus_element'    =>    '',
                                'db'        =>    $db,
                                )
                        );

                    $this->feather->render('search/header.php', array(
                                'lang_common' => $lang_common,
                                'lang_search' => $lang_search,
                                'search' => $search,
                                )
                        );

                    if ($search['show_as'] == 'posts') {
                        require FEATHER_ROOT.'lang/'.$feather_user['language'].'/topic.php';
                        require FEATHER_ROOT.'include/parser.php';
                    }

                    display_search_results($search, $this->feather);

                    $this->feather->render('search/footer.php', array(
                                'search' => $search,
                                )
                        );

                    $this->feather->render('footer.php', array(
                                'lang_common' => $lang_common,
                                'feather_user' => $feather_user,
                                'feather_config' => $feather_config,
                                'feather_start' => $feather_start,
                                'footer_style' => 'search',
                                'db' => $db,
                                )
                        );

                    require FEATHER_ROOT.'include/footer.php';
                } else {
                    message($lang_search['No hits']);
                }
        }

        $page_title = array(pun_htmlspecialchars($feather_config['o_board_title']), $lang_search['Search']);
        $focus_element = array('search', 'keywords');

        if (!defined('PUN_ACTIVE_PAGE')) {
            define('PUN_ACTIVE_PAGE', 'search');
        }

        require FEATHER_ROOT.'include/header.php';

        $this->feather->render('header.php', array(
                            'lang_common' => $lang_common,
                            'page_title' => $page_title,
                            'p' => $p,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            '_SERVER'    =>    $_SERVER,
                            'page_head'        =>    '',
                            'navlinks'        =>    $navlinks,
                            'page_info'        =>    $page_info,
                            'focus_element'    =>    $focus_element,
                            'db'        =>    $db,
                            )
                    );

        $this->feather->render('search/form.php', array(
                            'lang_common' => $lang_common,
                            'lang_search' => $lang_search,
                            'feather_config' => $feather_config,
                            'feather_user' => $feather_user,
                            )
                    );

        $this->feather->render('footer.php', array(
                            'lang_common' => $lang_common,
                            'feather_user' => $feather_user,
                            'feather_config' => $feather_config,
                            'feather_start' => $feather_start,
                            'footer_style' => 'search',
                            )
                    );

        require FEATHER_ROOT.'include/footer.php';
    }

    public function quicksearches($show)
    {
        redirect(get_link('search/?action=show_'.$show));
    }
}
