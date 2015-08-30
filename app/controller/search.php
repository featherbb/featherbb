<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace app\controller;

class search
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \app\model\search();
        load_textdomain('featherbb', FEATHER_ROOT.'app/lang/'.$this->user->language.'/userlist.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'app/lang/'.$this->user->language.'/search.mo');
        load_textdomain('featherbb', FEATHER_ROOT.'app/lang/'.$this->user->language.'/forum.mo');
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function display()
    {
        global $pd;

        if ($this->user->g_read_board == '0') {
            throw new \FeatherBB\Error(__('No view'), 403);
        } elseif ($this->user->g_search == '0') {
            throw new \FeatherBB\Error(__('No search permission'), 403);
        }

        // Figure out what to do :-)
        if ($this->request->get('action') || ($this->request->get('search_id'))) {

            $search = $this->model->get_search_results();
                // We have results to display
                if (isset($search['is_result'])) {

                    if ($search['show_as'] == 'posts') {
                        require FEATHER_ROOT.'include/parser.php';
                    }

                    $this->feather->view2->setPageInfo(array(
                        'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Search results')),
                        'active_page' => 'search',
                    ));

                    $this->model->display_search_results($search, $this->feather);

                    $this->feather->view2->setPageInfo(array(
                        'search' => $search,
                    ));

                    $this->feather->view2->addTemplate('search/header.php', 1);

                    if ($search['show_as'] == 'posts') {
                        $this->feather->view2->addTemplate('search/posts.php', 5);
                    }
                    else {
                        $this->feather->view2->addTemplate('search/topics.php', 5);
                    }

                    $this->feather->view2->addTemplate('search/footer.php', 10)->display();

                } else {
                    redirect($this->feather->url->get('search/'), __('No hits'));
                }
        }
        // Display the form
        else {
            $this->feather->view2->setPageInfo(array(
                'title' => array($this->feather->utils->escape($this->config['o_board_title']), __('Search')),
                'active_page' => 'search',
                'focus_element' => array('search', 'keywords'),
                'is_indexed' => true,
                'forums' => $this->model->get_list_forums(),
            ))->addTemplate('search/form.php')->display();
        }
    }

    public function quicksearches($show)
    {
        redirect($this->feather->url->get('search/?action=show_'.$show));
    }
}
