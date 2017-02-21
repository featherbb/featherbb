<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
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
        $this->model = new \FeatherBB\Model\Search();
        Lang::load('userlist');
        Lang::load('search');
        Lang::load('topic');
        Lang::load('forum');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.search.display');

        if (!User::can('search.topics')) {
            throw new Error(__('No search permission'), 403);
        }

        // Figure out what to do :-)
        if (Input::query('action') || isset($args['search_id'])) {
            $search = (isset($args['search_id'])) ? $this->model->getSearchResults($args['search_id']) : $this->model->getSearchResults();

            if (is_object($search)) {
                // $search is most likely a Router::redirect() to search page (no hits or other error) or to a search_id
                return $search;
            } else {

                // No results to display, redirect with message
                if (!isset($search['is_result'])) {
                    return Router::redirect(Router::pathFor('search'), ['error', __('No hits')]);
                }

                // We have results to display
                View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Search results')],
                    'active_page' => 'search',
                    'search' => $search,
                    'footer' => $search,
                ]);

                $display = $this->model->displaySearchResults($search);

                View::setPageInfo([
                    'display' => $display,
                ]);

                if ($search['show_as'] == 'posts') {
                    View::addTemplate('@forum/search/posts', 5)->display();
                } else {
                    View::addTemplate('@forum/search/topics', 5)->display();
                }
            }
        }
        // Display the form
        else {
            View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Search')],
                'active_page' => 'search',
                'is_indexed' => true,
                'forums' => $this->model->forumsList(),
            ])->addTemplate('@forum/search/form')->display();
        }
    }

    public function quicksearches($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.search.quicksearches');

        return Router::redirect(Router::pathFor('search').'?action=show_'.$args['show']);
    }
}
