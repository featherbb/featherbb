<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Interfaces\ForumEnv;
use FeatherBB\Core\Interfaces\ForumSettings;
use FeatherBB\Core\Interfaces\Hooks;
use FeatherBB\Core\Interfaces\Input;
use FeatherBB\Core\Interfaces\Lang;
use FeatherBB\Core\Interfaces\Router;
use FeatherBB\Core\Interfaces\User;
use FeatherBB\Core\Interfaces\View;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Topic
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Topic();
        Lang::load('topic');
        Lang::load('misc'); // To be removed
        Lang::load('post');
    }

    public function display($req, $res, $args)
    {
        if (!isset($args['page'])) {
            $args['page'] = null;
        }

        if (!isset($args['pid'])) {
            $args['pid'] = null;
        }

        if (!isset($args['name'])) {
            $args['name'] = null;
        }

        Container::get('hooks')->fire('controller.topic.display', $args['id'], $args['name'], $args['page'], $args['pid']);

        // Antispam feature
        $langAntispamQuestions = require ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/antispam.php';
        $indexQuestions = rand(0, count($langAntispamQuestions)-1);

        // Fetch some information about the topic
        $curTopic = $this->model->getInfoTopic($args['id']);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $modsArray = ($curTopic['moderators'] != '') ? unserialize($curTopic['moderators']) : [];
        $isAdmmod = (User::isAdmin() || (User::isAdminMod() && array_key_exists(User::get()->username, $modsArray))) ? true : false;

        // Can we or can we not post replies?
        $postLink = $this->model->postLink($args['id'], $curTopic['closed'], $curTopic['post_replies'], $isAdmmod);

        // Add/update this topic in our list of tracked topics
        if (!User::get()->is_guest) {
            $trackedTopics = Track::getTrackedTopics();
            $trackedTopics['topics'][$args['id']] = time();
            Track::setTrackedTopics($trackedTopics);
        }

        // Determine the post offset (based on $_gET['p'])
        $numPages = ceil(($curTopic['num_replies'] + 1) / User::getPref('disp.posts'));

        $p = (!isset($args['page']) || $args['page'] <= 1 || $args['page'] > $numPages) ? 1 : intval($args['page']);
        $startFrom = User::getPref('disp.posts') * ($p - 1);

        $urlTopic = Url::slug($curTopic['subject']);
        $urlForum = Url::slug($curTopic['forum_name']);

        // Generate paging links
        $pagingLinks = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($numPages, $p, 'topic/'.$args['id'].'/'.$urlTopic.'/#');

        if (ForumSettings::get('o_censoring') == '1') {
            $curTopic['subject'] = Utils::censor($curTopic['subject']);
        }

        $quickpost = $this->model->isQuickpost($curTopic['post_replies'], $curTopic['closed'], $isAdmmod);
        $subscraction = $this->model->getSubscraction(($curTopic['is_subscribed'] == User::get()->id), $args['id'], $args['name']);

        View::addAsset('canonical', Router::pathFor('Topic', ['id' => $args['id'], 'name' => $urlTopic]));
        if ($numPages > 1) {
            if ($p > 1) {
                View::addAsset('prev', Router::pathFor('Topic', ['id' => $args['id'], 'name' => $urlTopic, 'page' => intval($p-1)]));
            }
            if ($p < $numPages) {
                View::addAsset('next', Router::pathFor('Topic', ['id' => $args['id'], 'name' => $urlTopic, 'page' => intval($p+1)]));
            }
        }

        View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), Utils::escape($curTopic['forum_name']), Utils::escape($curTopic['subject'])],
            'active_page' => 'Topic',
            'page_number'  =>  $p,
            'paging_links'  =>  $pagingLinks,
            'is_indexed' => true,
            'id' => $args['id'],
            'pid' => $args['pid'],
            'tid' => $args['id'],
            'fid' => $curTopic['forum_id'],
            'post_data' => $this->model->printPosts($args['id'], $startFrom, $curTopic, $isAdmmod),
            'cur_topic'    =>    $curTopic,
            'subscraction'    =>    $subscraction,
            'post_link' => $postLink,
            'start_from' => $startFrom,
            'quickpost'        =>    $quickpost,
            'index_questions'        =>    $indexQuestions,
            'lang_antispam_questions'        =>    $langAntispamQuestions,
            'url_forum'        =>    $urlForum,
            'url_topic'        =>    $urlTopic,
            'is_admmod' => $isAdmmod,
        ])->addTemplate('@forum/topic')->display();

        // Increment "num_views" for topic
        $this->model->incrementViews($args['id']);
    }

    public function viewpost($req, $res, $args)
    {
        $args['pid'] = Container::get('hooks')->fire('controller.topic.viewpost', $args['pid']);

        $post = $this->model->redirectToPost($args['pid']);

        $args['id'] = $post['topic_id'];
        $args['page'] = $post['get_p'];

        $this->display($req, $res, $args);
    }

    public function subscribe($req, $res, $args)
    {
        $args['id'] = Container::get('hooks')->fire('controller.topic.subscribe', $args['id']);

        $this->model->subscribe($args['id']);
        return Router::redirect(Router::pathFor('Topic', ['id' => $args['id'], 'name' => $args['name']]), __('Subscribe redirect'));
    }

    public function unsubscribe($req, $res, $args)
    {
        $args['id'] = Container::get('hooks')->fire('controller.topic.unsubscribe', $args['id']);

        $this->model->unsubscribe($args['id']);
        return Router::redirect(Router::pathFor('Topic', ['id' => $args['id'], 'name' => $args['name']]), __('Unsubscribe redirect'));
    }

    public function close($req, $res, $args)
    {
        $args['id'] = Container::get('hooks')->fire('controller.topic.close', $args['id']);

        $topic = $this->model->setClosed($args['id'], 1);
        return Router::redirect(Router::pathFor('Topic', ['id' => $args['id'], 'name' => Url::slug($topic['subject'])]), __('Close topic redirect'));
    }

    public function open($req, $res, $args)
    {
        $args['id'] = Container::get('hooks')->fire('controller.topic.open', $args['id']);

        $topic = $this->model->setClosed($args['id'], 0);
        return Router::redirect(Router::pathFor('Topic', ['id' => $args['id'], 'name' => Url::slug($topic['subject'])]), __('Open topic redirect'));
    }

    public function stick($req, $res, $args)
    {
        $args['id'] = Container::get('hooks')->fire('controller.topic.stick', $args['id']);

        $topic = $this->model->setSticky($args['id'], 1);
        return Router::redirect(Router::pathFor('Topic', ['id' => $args['id'], 'name' => Url::slug($topic['subject'])]), __('Stick topic redirect'));
    }

    public function unstick($req, $res, $args)
    {
        $args['id'] = Container::get('hooks')->fire('controller.topic.unstick', $args['id']);

        $topic = $this->model->setSticky($args['id'], 0);
        return Router::redirect(Router::pathFor('Topic', ['id' => $args['id'], 'name' => Url::slug($topic['subject'])]), __('Unstick topic redirect'));
    }

    // Move a single topic
    public function move($req, $res, $args)
    {
        $args['id'] = Container::get('hooks')->fire('controller.topic.move', $args['id']);

        if ($newFid = Input::post('move_to_forum')) {
            $this->model->moveTo($args['fid'], $newFid, $args['id']);
            return Router::redirect(Router::pathFor('Topic', ['id' => $args['id'], 'name' => $args['name']]), __('Move topic redirect'));
        }

        // Check if there are enough forums to move the topic
        if (!$this->model->checkMove()) {
            throw new Error(__('Nowhere to move'), 403);
        }

        View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Moderate')],
                'active_page' => 'moderate',
                'action'    =>    'single',
                'topics'    =>    $args['id'],
                'list_forums'   => $this->model->getForumListMove($args['fid']),
            ]
        )->addTemplate('@forum/moderate/move_topics')->display();
    }

    public function moderate($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.topic.moderate');

        $curTopic = $this->model->getTopicInfo($args['fid'], $args['id']);

        // Determine the post offset (based on $_gET['p'])
        $numPages = ceil(($curTopic['num_replies'] + 1) / User::getPref('disp.posts'));

        $p = (!isset($args['page']) || $args['page'] <= 1 || $args['page'] > $numPages) ? 1 : intval($args['page']);

        $startFrom = User::getPref('disp.posts') * ($p - 1);

        // Delete one or more posts
        if (Input::post('delete_posts_comply')) {
            return $this->model->deletePosts($args['id'], $args['fid']);
        } elseif (Input::post('delete_posts')) {
            $posts = $this->model->deletePosts($args['id'], $args['fid']);

            return View::setPageInfo([
                        'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Moderate')],
                        'active_page' => 'moderate',
                        'posts' => $posts,
                    ]
                )->addTemplate('@forum/moderate/delete_posts')->display();
        } elseif (Input::post('split_posts_comply')) {
            return $this->model->splitPosts($args['id'], $args['fid'], $p);
        } elseif (Input::post('split_posts')) {
            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Moderate')],
                    'page' => $p,
                    'active_page' => 'moderate',
                    'id' => $args['id'],
                    'posts' => $this->model->splitPosts($args['id'], $args['fid'], $p),
                    'list_forums' => $this->model->getSplitForumList($args['fid']),
                ]
            )->addTemplate('@forum/moderate/split_posts')->display();
        } else {
            // Show the moderate posts view

            // Used to disable the Move and Delete buttons if there are no replies to this topic
            $buttonStatus = ($curTopic['num_replies'] == 0) ? ' disabled="disabled"' : '';

            /*if (isset($_gET['action']) && $_gET['action'] == 'all') {
                    User::getPref('disp.posts') = $curTopic['num_replies'] + 1;
            }*/

            if (ForumSettings::get('o_censoring') == '1') {
                $curTopic['subject'] = Utils::censor($curTopic['subject']);
            }

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), Utils::escape($curTopic['forum_name']), Utils::escape($curTopic['subject'])],
                    'page' => $p,
                    'active_page' => 'moderate',
                    'cur_topic' => $curTopic,
                    'url_topic' => Url::slug($curTopic['subject']),
                    'url_forum' => Url::slug($curTopic['forum_name']),
                    'fid' => $args['fid'],
                    'id' => $args['id'],
                    'paging_links' => '<span class="pages-label">' . __('Pages') . ' </span>' . Url::paginate($numPages, $p, 'topic/moderate/' . $args['id'] . '/forum/' . $args['fid'] . '/#'),
                    'post_data' => $this->model->moderateDisplayPosts($args['id'], $startFrom),
                    'button_status' => $buttonStatus,
                    'start_from' => $startFrom,
                ]
            )->addTemplate('@forum/moderate/posts_view')->display();
        }
    }

    public function action($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.topic.action');

        return $this->model->handleActions($args['id'], $args['name'], $args['action']);
    }
}
