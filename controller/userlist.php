<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class userlist
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
        $this->model = new \model\userlist();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common;

        if ($this->user->g_read_board == '0') {
            message($lang_common['No view'], '403');
        } elseif ($this->user->g_view_users == '0') {
            message($lang_common['No permission'], '403');
        }

        // Load the userlist.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/userlist.php';

        // Load the search.php language file
        require FEATHER_ROOT.'lang/'.$this->user->language.'/search.php';


        // Determine if we are allowed to view post counts
        $show_post_count = ($this->config['o_show_post_count'] == '1' || $this->user->is_admmod) ? true : false;

        $username = $this->request->get('username') && $this->user->g_search_users == '1' ? feather_trim($this->request->get('username')) : '';
        $show_group = $this->request->get('show_group') ? intval($this->request->get('show_group')) : -1;
        $sort_by = $this->request->get('sort_by') && (in_array($this->request->get('sort_by'), array('username', 'registered')) || ($this->request->get('sort_by') == 'num_posts' && $show_post_count)) ? $this->request->get('sort_by') : 'username';
        $sort_dir = $this->request->get('sort_dir') && $this->request->get('sort_dir') == 'DESC' ? 'DESC' : 'ASC';

        $num_users = $this->model->fetch_user_count($username, $show_group);

        // Determine the user offset (based on $page)
        $num_pages = ceil($num_users / 50);

        $p = (!$this->request->get('p') || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = 50 * ($p - 1);

        $page_title = array(feather_escape($this->config['o_board_title']), $lang_common['User list']);
        if ($this->user->g_search_users == '1') {
            $focus_element = array('userlist', 'username');
        }

        // Generate paging links
        $paging_links = '<span class="pages-label">'.$lang_common['Pages'].' </span>'.paginate_old($num_pages, $p, '?username='.urlencode($username).'&amp;show_group='.$show_group.'&amp;sort_by='.$sort_by.'&amp;sort_dir='.$sort_dir);

        define('FEATHER_ALLOW_INDEX', 1);

        define('FEATHER_ACTIVE_PAGE', 'userlist');

        $this->header->setTitle($page_title)->setPage($p)->setFocusElement($focus_element)->setPagingLinks($paging_links)->display();

        $this->feather->render('userlist.php', array(
                            'lang_common' => $lang_common,
                            'lang_search' => $lang_search,
                            'lang_ul' => $lang_ul,
                            'feather' => $this->feather,
                            'username' => $username,
                            'show_group' => $show_group,
                            'sort_by' => $sort_by,
                            'sort_dir' => $sort_dir,
                            'show_post_count' => $show_post_count,
                            'paging_links' => $paging_links,
                            'feather_config' => $this->config,
                            'dropdown_menu' => $this->model->generate_dropdown_menu($show_group),
                            'userlist_data' => $this->model->print_users($username, $start_from, $sort_by, $sort_dir, $show_group),
                            )
                    );

        $this->footer->display();
    }
}
