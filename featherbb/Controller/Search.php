<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Search
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = Container::get('user');
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Search();
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/userlist.mo');
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/search.mo');
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/topic.mo');
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/forum.mo');
    }


    public function display()
    {
        Container::get('hooks')->fire('controller.search.display');

        if ($this->user->g_search == '0') {
            throw new Error(__('No search permission'), 403);
        }

        // Figure out what to do :-)
        if ($this->request->get('action') || ($this->request->get('search_id'))) {

            $search = $this->model->get_search_results();

            // We have results to display
            if (isset($search['is_result'])) {

                View::setPageInfo(array(
                    'title' => array(Utils::escape($this->config['o_board_title']), __('Search results')),
                    'active_page' => 'search',
                    'search' => $search,
                    'footer' => $search,
                ));

                $display = $this->model->display_search_results($search, $this->feather);

                View::setPageInfo(array(
                        'display' => $display,
                    )
                );

                View::addTemplate('search/header.php', 1);

                if ($search['show_as'] == 'posts') {
                    View::addTemplate('search/posts.php', 5);
                }
                else {
                    View::addTemplate('search/topics.php', 5);
                }

                View::addTemplate('search/footer.php', 10)->display();

            } else {
                Router::redirect(Router::pathFor('search'), __('No hits'));
            }
        }
        // Display the form
        else {
            View::setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Search')),
                'active_page' => 'search',
                'focus_element' => array('search', 'keywords'),
                'is_indexed' => true,
                'forums' => $this->model->get_list_forums(),
            ))->addTemplate('search/form.php')->display();
        }
    }

    public function quicksearches($show)
    {
        Container::get('hooks')->fire('controller.search.quicksearches');

        Router::redirect(Router::pathFor('search').'?action=show_'.$show);
    }
}
