<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Forum
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Forum();
        Lang::load('forum');
        Lang::load('misc');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.forum.display');
        // Fetch some information about the forum
        $curForum = $this->model->getForumInfo($args['id']);

        // Is this a redirect forum? In that case, redirect!
        if ($curForum['redirect_url'] != '') {
            return Router::redirect(Router::pathFor('Forum', ['id' => $curForum['redirect_url'], 'name' => $curForum['forum_url']]));
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $modsArray = ($curForum['moderators'] != '') ? unserialize($curForum['moderators']) : [];
        $isAdmmod = (User::isAdmin() || (User::isAdminMod() && array_key_exists(User::get()->username, $modsArray))) ? true : false;

        $sortBy = $this->model->sortForumBy($curForum['sort_by']);

        // Can we or can we not post new topics?
        if (($curForum['post_topics'] == '' && User::can('topic.post')) || $curForum['post_topics'] == '1' || $isAdmmod) {
            $postLink = "\t\t\t".'<p class="postlink conr"><a href="'.Router::pathFor('newTopic', ['fid' => $args['id']]).'">'.__('Post topic').'</a></p>'."\n";
        } else {
            $postLink = '';
        }

        // Determine the topic offset (based on $args['page'])
        $numPages = ceil($curForum['num_topics'] / User::getPref('disp.topics'));

        $p = (!isset($args['page']) || $args['page'] <= 1 || $args['page'] > $numPages) ? 1 : intval($args['page']);
        $startFrom = User::getPref('disp.topics') * ($p - 1);
        $urlForum = Url::slug($curForum['forum_name']);

        // Generate paging links
        $pagingLinks = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($numPages, $p, 'forum/'.$args['id'].'/'.$urlForum.'/#');

        $forumActions = $this->model->getForumActions($args['id'], $urlForum, ($curForum['is_subscribed'] == User::get()->id));

        View::addAsset('canonical', Router::pathFor('Forum', ['id' => $args['id'], 'name' => $args['name']]));
        if ($numPages > 1) {
            if ($p > 1) {
                View::addAsset('prev', Router::pathFor('Forum', ['id' => $args['id'], 'name' => $args['name'], 'page' => intval($p-1)]));
            }
            if ($p < $numPages) {
                View::addAsset('next', Router::pathFor('Forum', ['id' => $args['id'], 'name' => $args['name'], 'page' => intval($p+1)]));
            }
        }

        View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), Utils::escape($curForum['forum_name'])],
            'active_page' => 'Forum',
            'page_number'  =>  $p,
            'paging_links'  =>  $pagingLinks,
            'is_indexed' => true,
            'id' => $args['id'],
            'fid' => $args['id'],
            'forum_data' => $this->model->printTopics($args['id'], $sortBy, $startFrom),
            'cur_forum' => $curForum,
            'post_link' => $postLink,
            'start_from' => $startFrom,
            'url_forum' => $urlForum,
            'forum_actions' => $forumActions,
            'is_admmod' => $isAdmmod,
        ])->addTemplate('forum.php')->display();
    }

    public function moderate($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.forum.moderate');

        // Make sure that only admmods allowed access this page
        $moderators = $this->model->getModerators($args['id']);
        $modsArray = ($moderators != '') ? unserialize($moderators) : [];

        if (!User::isAdmin() && (!User::isAdminMod() || !array_key_exists(User::get()->username, $modsArray))) {
            throw new Error(__('No permission'), 403);
        }

        // Fetch some info about the forum
        $curForum = $this->model->getForumInfo($args['id']);

        // Is this a redirect forum? In that case, abort!
        if ($curForum['redirect_url'] != '') {
            throw new Error(__('Bad request'), '404');
        }

        $sortBy = $this->model->sortForumBy($curForum['sort_by']);

        // Determine the topic offset (based on $_gET['p'])
        $numPages = ceil($curForum['num_topics'] / User::getPref('disp.topics'));

        $p = (!isset($args['page']) || $args['page'] <= 1 || $args['page'] > $numPages) ? 1 : intval($args['page']);
        $startFrom = User::getPref('disp.topics') * ($p - 1);
        $urlForum = Url::slug($curForum['forum_name']);

        View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), Utils::escape($curForum['forum_name'])],
            'active_page' => 'moderate',
            'page' => $p,
            'id' => $args['id'],
            'p' => $p,
            'url_forum' => $urlForum,
            'cur_forum' => $curForum,
            'paging_links' => '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($numPages, $p, 'forum/'.$args['id'].'/'.$urlForum.'/moderate/#'),
            'topic_data' => $this->model->displayTopicsModerate($args['id'], $sortBy, $startFrom),
            'start_from' => $startFrom,
            ]
        )->addTemplate('moderate/moderator_forum.php')->display();
    }

    public function markread($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.forum.markread');

        $trackedTopics = Track::getTrackedTopics();
        $trackedTopics['forums'][$args['id']] = time();
        Track::setTrackedTopics($trackedTopics);

        return Router::redirect(Router::pathFor('Forum', ['id' => $args['id'], 'name' => $args['name']]), __('Mark forum read redirect'));
    }

    public function subscribe($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.forum.subscribe');

        $this->model->subscribe($args['id']);
        return Router::redirect(Router::pathFor('Forum', ['id' => $args['id'], 'name' => $args['name']]), __('Subscribe redirect'));
    }

    public function unsubscribe($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.forum.unsubscribe');

        $this->model->unsubscribe($args['id']);
        return Router::redirect(Router::pathFor('Forum', ['id' => $args['id'], 'name' => $args['name']]), __('Unsubscribe redirect'));
    }

    public function dealposts($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.forum.dealposts');

        // Make sure that only admmods allowed access this page
        $moderators = $this->model->getModerators($args['id']);
        $modsArray = ($moderators != '') ? unserialize($moderators) : [];

        if (!User::isAdmin() && (!User::isAdminMod() || !array_key_exists(User::get()->username, $modsArray))) {
            throw new Error(__('No permission'), 403);
        }

        $topicModel = new \FeatherBB\Model\Topic();

        // Move one or more topics
        if (Input::post('move_topics') || Input::post('move_topics_to')) {
            $topics = Input::post('topics') ? Input::post('topics') : [];
            if (empty($topics)) {
                throw new Error(__('No topics selected'), 400);
            }

            if ($newFid = Input::post('move_to_forum')) {
                $topics = explode(',', $topics);
                $topicModel->moveTo($args['id'], $newFid, $topics);
                return Router::redirect(Router::pathFor('Forum', ['id' => $newFid, 'name' => $args['name']]), __('Move topics redirect'));
            }

            // Check if there are enough forums to move the topic
            if (!$topicModel->checkMove()) {
                throw new Error(__('Nowhere to move'), 403);
            }

            return View::setPageInfo([
                    'action'    =>    'multi',
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Moderate')],
                    'active_page' => 'moderate',
                    'id'    =>    $args['id'],
                    'topics'    =>    implode(',', array_map('intval', array_keys($topics))),
                    'list_forums'   => $topicModel->getForumListMove($args['id']),
                ]
            )->addTemplate('moderate/move_topics.php')->display();
        }

        // Merge two or more topics
        elseif (Input::post('merge_topics') || Input::post('merge_topics_comply')) {
            if (Input::post('merge_topics_comply')) {
                $this->model->merge($args['id']);
                return Router::redirect(Router::pathFor('Forum', ['id' => $args['id'], 'name' => $args['name']]), __('Merge topics redirect'));
            }

            $topics = Input::post('topics') ? Input::post('topics') : [];
            if (count($topics) < 2) {
                throw new Error(__('Not enough topics selected'), 400);
            }

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Moderate')],
                    'active_page' => 'moderate',
                    'id'    =>    $args['id'],
                    'topics'    =>    $topics,
                ]
            )->addTemplate('moderate/merge_topics.php')->display();
        }

        // Delete one or more topics
        elseif (Input::post('delete_topics') || Input::post('delete_topics_comply')) {
            $topics = Input::post('topics') ? Input::post('topics') : [];
            if (empty($topics)) {
                throw new Error(__('No topics selected'), 400);
            }

            if (Input::post('delete_topics_comply')) {
                $this->model->delete($topics, $args['id']);
                return Router::redirect(Router::pathFor('Forum', ['id' => $args['id'], 'name' => $args['name']]), __('Delete topics redirect'));
            }

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Moderate')],
                    'active_page' => 'moderate',
                    'id'    =>    $args['id'],
                    'topics'    =>    $topics,
                ]
            )->addTemplate('moderate/delete_topics.php')->display();
        }

        // Open or close one or more topics
        elseif (Input::post('open') || Input::post('close')) {
            $action = (Input::post('open')) ? 0 : 1;

            // There could be an array of topic IDs in $_pOST
            if (Input::post('open') || Input::post('close')) {
                $topics = Input::post('topics') ? @array_map('intval', @array_keys(Input::post('topics'))) : [];
                if (empty($topics)) {
                    throw new Error(__('No topics selected'), 400);
                }

                $this->model->closeMultiple($action, $topics);

                $redirectMsg = ($action) ? __('Close topics redirect') : __('Open topics redirect');
                return Router::redirect(Router::pathFor('moderateForum', ['id' => $args['id'], 'name' => $args['name'], 'page' => $args['page']]), $redirectMsg);
            }
        }

        // Stick or unstick one or more topics
        elseif (Input::post('stick') || Input::post('unstick')) {
            $action = (Input::post('stick')) ? 1 : 0;

            // There could be an array of topic IDs in $_pOST
            if (Input::post('stick') || Input::post('unstick')) {
                $topics = Input::post('topics') ? @array_map('intval', @array_keys(Input::post('topics'))) : [];
                if (empty($topics)) {
                    throw new Error(__('No topics selected'), 400);
                }

                $this->model->stickMultiple($action, $topics);

                $redirectMsg = ($action) ? __('Stick topics redirect') : __('Unstick topics redirect');
                return Router::redirect(Router::pathFor('moderateForum', ['id' => $args['id'], 'name' => $args['name'], 'page' => $args['page']]), $redirectMsg);
            }
        }
    }
}
