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
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\search();
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/userlist.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/search.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/forum.mo');
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $pd;

        if ($this->user->g_read_board == '0') {
            message(__('No view'), '403');
        } elseif ($this->user->g_search == '0') {
            message(__('No search permission'), false, '403 Forbidden');
        }

        require FEATHER_ROOT.'include/search_idx.php';

        // Figure out what to do :-)
        if ($this->request->get('action') || ($this->request->get('search_id'))) {
            $search = $this->model->get_search_results();

                // We have results to display
                if (isset($search['is_result'])) {
                    $page_title = array(feather_escape($this->config['o_board_title']), __('Search results'));

                    define('FEATHER_ACTIVE_PAGE', 'search');

                    $this->header->setTitle($page_title)->display();

                    $this->feather->render('search/header.php', array(
                                'search' => $search,
                                )
                        );

                    if ($search['show_as'] == 'posts') {
                        require FEATHER_ROOT.'include/parser.php';
                    }

                    $this->model->display_search_results($search, $this->feather);

                    $this->feather->render('search/footer.php', array(
                                'search' => $search,
                                )
                        );

                    $this->footer->display();
                } else {
                    message(__('No hits'));
                }
        }

        $page_title = array(feather_escape($this->config['o_board_title']), __('Search'));
        $focus_element = array('search', 'keywords');

        define('FEATHER_ACTIVE_PAGE', 'search');

        $this->header->setTitle($page_title)->setFocusElement($focus_element)->display();

        $this->feather->render('search/form.php', array(
                            'feather_config' => $this->config,
                            'feather' => $this->feather,
                            'forums' => $this->model->get_list_forums(),
                            )
                    );

        $this->footer->display();
    }

    public function quicksearches($show)
    {
        redirect(get_link('search/?action=show_'.$show));
    }
}
