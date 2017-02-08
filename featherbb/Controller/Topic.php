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
use FeatherBB\Model\Forum;

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
        $lang_antispam_questions = require ForumEnv::get('FEATHER_ROOT').'featherbb/lang/'.User::getPref('language').'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // Fetch some information about the topic
        $cur_topic = $this->model->getInfoTopic($args['id']);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : [];
        $is_admmod = (User::isAdmin() || (User::isAdminMod() && array_key_exists(User::get()->username, $mods_array))) ? true : false;

        // Can we or can we not post replies?
        $post_link = $this->model->postLink($args['id'], $cur_topic['closed'], $cur_topic['post_replies'], $is_admmod);

        // Add/update this topic in our list of tracked topics
        if (!User::get()->is_guest) {
            $tracked_topics = Track::getTrackedTopics();
            $tracked_topics['topics'][$args['id']] = time();
            Track::setTrackedTopics($tracked_topics);
        }

        // Determine the post offset (based on $_GET['p'])
        $num_pages = ceil(($cur_topic['num_replies'] + 1) / User::getPref('disp.posts'));

        $p = (!isset($args['page']) || $args['page'] <= 1 || $args['page'] > $num_pages) ? 1 : intval($args['page']);
        $start_from = User::getPref('disp.posts') * ($p - 1);

        $url_topic = Url::slug($cur_topic['subject']);
        $url_forum = Url::slug($cur_topic['forum_name']);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, 'topic/'.$args['id'].'/'.$url_topic.'/#');

        if (ForumSettings::get('o_censoring') == '1') {
            $cur_topic['subject'] = Utils::censor($cur_topic['subject']);
        }

        $quickpost = $this->model->isQuickpost($cur_topic['post_replies'], $cur_topic['closed'], $is_admmod);
        $subscraction = $this->model->getSubscraction(($cur_topic['is_subscribed'] == User::get()->id), $args['id'], $args['name']);

        View::addAsset('canonical', Router::pathFor('Topic', ['id' => $args['id'], 'name' => $url_topic]));
        if ($num_pages > 1) {
            if ($p > 1) {
                View::addAsset('prev', Router::pathFor('Topic', ['id' => $args['id'], 'name' => $url_topic, 'page' => intval($p-1)]));
            }
            if ($p < $num_pages) {
                View::addAsset('next', Router::pathFor('Topic', ['id' => $args['id'], 'name' => $url_topic, 'page' => intval($p+1)]));
            }
        }

        View::setPageInfo([
            'title' => [Utils::escape(ForumSettings::get('o_board_title')), Utils::escape($cur_topic['forum_name']), Utils::escape($cur_topic['subject'])],
            'active_page' => 'Topic',
            'page_number'  =>  $p,
            'paging_links'  =>  $paging_links,
            'is_indexed' => true,
            'id' => $args['id'],
            'pid' => $args['pid'],
            'tid' => $args['id'],
            'fid' => $cur_topic['forum_id'],
            'post_data' => $this->model->printPosts($args['id'], $start_from, $cur_topic, $is_admmod),
            'cur_topic'    =>    $cur_topic,
            'subscraction'    =>    $subscraction,
            'post_link' => $post_link,
            'start_from' => $start_from,
            'quickpost'        =>    $quickpost,
            'index_questions'        =>    $index_questions,
            'lang_antispam_questions'        =>    $lang_antispam_questions,
            'url_forum'        =>    $url_forum,
            'url_topic'        =>    $url_topic,
            'is_admmod' => $is_admmod,
        ])->addTemplate('topic.php')->display();

        // Increment "num_views" for topic
        $this->model->increment_views($args['id']);
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

        if ($new_fid = Input::post('move_to_forum')) {
            $this->model->moveTo($args['fid'], $new_fid, $args['id']);
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
        )->addTemplate('moderate/move_topics.php')->display();
    }

    public function moderate($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.topic.moderate');

        $cur_topic = $this->model->getTopicInfo($args['fid'], $args['id']);

        // Determine the post offset (based on $_GET['p'])
        $num_pages = ceil(($cur_topic['num_replies'] + 1) / User::getPref('disp.posts'));

        $p = (!isset($args['page']) || $args['page'] <= 1 || $args['page'] > $num_pages) ? 1 : intval($args['page']);

        $start_from = User::getPref('disp.posts') * ($p - 1);

        // Delete one or more posts
        if (Input::post('delete_posts_comply')) {
            return $this->model->delete_posts($args['id'], $args['fid']);
        } elseif (Input::post('delete_posts')) {
            $posts = $this->model->delete_posts($args['id'], $args['fid']);

            return View::setPageInfo([
                        'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Moderate')],
                        'active_page' => 'moderate',
                        'posts' => $posts,
                    ]
                )->addTemplate('moderate/delete_posts.php')->display();
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
            )->addTemplate('moderate/split_posts.php')->display();
        } else {
            // Show the moderate posts view

            // Used to disable the Move and Delete buttons if there are no replies to this topic
            $button_status = ($cur_topic['num_replies'] == 0) ? ' disabled="disabled"' : '';

            /*if (isset($_GET['action']) && $_GET['action'] == 'all') {
                    User::getPref('disp.posts') = $cur_topic['num_replies'] + 1;
            }*/

            if (ForumSettings::get('o_censoring') == '1') {
                $cur_topic['subject'] = Utils::censor($cur_topic['subject']);
            }

            return View::setPageInfo([
                    'title' => [Utils::escape(ForumSettings::get('o_board_title')), Utils::escape($cur_topic['forum_name']), Utils::escape($cur_topic['subject'])],
                    'page' => $p,
                    'active_page' => 'moderate',
                    'cur_topic' => $cur_topic,
                    'url_topic' => Url::slug($cur_topic['subject']),
                    'url_forum' => Url::slug($cur_topic['forum_name']),
                    'fid' => $args['fid'],
                    'id' => $args['id'],
                    'paging_links' => '<span class="pages-label">' . __('Pages') . ' </span>' . Url::paginate($num_pages, $p, 'topic/moderate/' . $args['id'] . '/forum/' . $args['fid'] . '/#'),
                    'post_data' => $this->model->moderateDisplayPosts($args['id'], $start_from),
                    'button_status' => $button_status,
                    'start_from' => $start_from,
                ]
            )->addTemplate('moderate/posts_view.php')->display();
        }
    }

    public function action($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.topic.action');

        return $this->model->handleActions($args['id'], $args['name'], $args['action']);
    }
}
