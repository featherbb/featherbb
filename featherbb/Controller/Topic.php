<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Track;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Topic
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Model\Topic();
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/topic.mo');
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/misc.mo'); // To be removed
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/post.mo');
    }

    public function display($id = null, $name = null, $page = null, $pid = null)
    {
        Container::get('hooks')->fire('controller.topic.display', $id, $name, $page, $pid);

        // Antispam feature
        require Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // Fetch some informations about the topic
        $cur_topic = $this->model->get_info_topic($id);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
        $is_admmod = (Container::get('user')->g_id == Config::get('forum_env')['FEATHER_ADMIN'] || (Container::get('user')->g_moderator == '1' && array_key_exists(Container::get('user')->username, $mods_array))) ? true : false;

        // Can we or can we not post replies?
        $post_link = $this->model->get_post_link($id, $cur_topic['closed'], $cur_topic['post_replies'], $is_admmod);

        // Add/update this topic in our list of tracked topics
        if (!Container::get('user')->is_guest) {
            $tracked_topics = Track::get_tracked_topics();
            $tracked_topics['topics'][$id] = time();
            Track::set_tracked_topics($tracked_topics);
        }

        // Determine the post offset (based on $_GET['p'])
        $num_pages = ceil(($cur_topic['num_replies'] + 1) / Container::get('user')->disp_posts);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = Container::get('user')->disp_posts * ($p - 1);

        $url_topic = Url::url_friendly($cur_topic['subject']);
        $url_forum = Url::url_friendly($cur_topic['forum_name']);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, 'topic/'.$id.'/'.$url_topic.'/#');

        if (Config::get('forum_settings')['o_censoring'] == '1') {
            $cur_topic['subject'] = Utils::censor($cur_topic['subject']);
        }

        $quickpost = $this->model->is_quickpost($cur_topic['post_replies'], $cur_topic['closed'], $is_admmod);
        $subscraction = $this->model->get_subscraction($cur_topic['is_subscribed'], $id);

        View::addAsset('canonical', Router::pathFor('Forum', ['id' => $id, 'name' => $url_forum]));
        if ($num_pages > 1) {
            if ($p > 1) {
                View::addAsset('prev', Router::pathFor('ForumPaginate', ['id' => $id, 'name' => $url_forum, 'page' => intval($p-1)]));
            }
            if ($p < $num_pages) {
                View::addAsset('next', Router::pathFor('ForumPaginate', ['id' => $id, 'name' => $url_forum, 'page' => intval($p+1)]));
            }
        }

        if (Config::get('forum_settings')['o_feed_type'] == '1') {
            View::addAsset('feed', 'extern.php?action=feed&amp;fid='.$id.'&amp;type=rss', array('title' => __('RSS forum feed')));
        } elseif (Config::get('forum_settings')['o_feed_type'] == '2') {
            View::addAsset('feed', 'extern.php?action=feed&amp;fid='.$id.'&amp;type=atom', array('title' => __('Atom forum feed')));
        }

        View::setPageInfo(array(
            'title' => array(Utils::escape(Config::get('forum_settings')['o_board_title']), Utils::escape($cur_topic['forum_name']), Utils::escape($cur_topic['subject'])),
            'active_page' => 'Topic',
            'page_number'  =>  $p,
            'paging_links'  =>  $paging_links,
            'is_indexed' => true,
            'id' => $id,
            'pid' => $pid,
            'tid' => $id,
            'fid' => $cur_topic['forum_id'],
            'post_data' => $this->model->print_posts($id, $start_from, $cur_topic, $is_admmod),
            'cur_topic'    =>    $cur_topic,
            'subscraction'    =>    $subscraction,
            'post_link' => $post_link,
            'start_from' => $start_from,
            'lang_antispam' => $lang_antispam,
            'quickpost'        =>    $quickpost,
            'index_questions'        =>    $index_questions,
            'lang_antispam_questions'        =>    $lang_antispam_questions,
            'url_forum'        =>    $url_forum,
            'url_topic'        =>    $url_topic,
        ))->addTemplate('topic.php')->display();

        // Increment "num_views" for topic
        $this->model->increment_views($id);
    }

    public function viewpost($pid)
    {
        $pid = Container::get('hooks')->fire('controller.topic.viewpost', $pid);

        $post = $this->model->redirect_to_post($pid);

        return $this->display($post['topic_id'], null, $post['get_p'], $pid);
    }

    public function subscribe($id, $name = '')
    {
        $id = Container::get('hooks')->fire('controller.topic.subscribe', $id);

        $this->model->subscribe($id);
    }

    public function unsubscribe($id, $name = '')
    {
        $id = Container::get('hooks')->fire('controller.topic.unsubscribe', $id);

        $this->model->unsubscribe($id);
    }

    public function close($id, $name = '')
    {
        $id = Container::get('hooks')->fire('controller.topic.close', $id);

        $topic = $this->model->setClosed($id, 1);
        Router::redirect(Router::pathFor('Topic', ['id' => $id, 'name' => Url::url_friendly($topic['subject'])]), __('Close topic redirect'));
    }

    public function open($id, $name = '')
    {
        $id = Container::get('hooks')->fire('controller.topic.open', $id);

        $topic = $this->model->setClosed($id, 0);
        Router::redirect(Router::pathFor('Topic', ['id' => $id, 'name' => Url::url_friendly($topic['subject'])]), __('Open topic redirect'));
    }

    public function stick($id, $name = '')
    {
        $id = Container::get('hooks')->fire('controller.topic.stick', $id);

        $topic = $this->model->setSticky($id, 1);
        Router::redirect(Router::pathFor('Topic', ['id' => $id, 'name' => Url::url_friendly($topic['subject'])]), __('Stick topic redirect'));
    }

    public function unstick($id, $name = '')
    {
        $id = Container::get('hooks')->fire('controller.topic.unstick', $id);

        $topic = $this->model->setSticky($id, 0);
        Router::redirect(Router::pathFor('Topic', ['id' => $id, 'name' => Url::url_friendly($topic['subject'])]), __('Unstick topic redirect'));
    }

    // Move a single topic
    public function move($tid, $name = '', $fid)
    {
        $tid = Container::get('hooks')->fire('controller.topic.move', $tid);

        if ($new_fid = $this->feather->request->post('move_to_forum')) {
            $this->model->move_to($fid, $new_fid, $tid);
            Router::redirect(Router::pathFor('Topic', array('id' => $tid, 'name' => $name)), __('Move topic redirect'));
        }

        // Check if there are enough forums to move the topic
        if ( !$this->model->check_move_possible() ) {
            throw new Error(__('Nowhere to move'), 403);
        }

        View::setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), __('Moderate')),
                'active_page' => 'moderate',
                'action'    =>    'single',
                'topics'    =>    $tid,
                'list_forums'   => $this->model->get_forum_list_move($fid),
            )
        )->addTemplate('moderate/move_topics.php')->display();
    }

    public function moderate($id = null, $fid = null, $page = null)
    {
        Container::get('hooks')->fire('controller.topic.moderate');

        // Make sure that only admmods allowed access this page
        $forumModel = new \FeatherBB\Model\Forum();
        $moderators = $forumModel->get_moderators($id);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if (Container::get('user')->g_id != Config::get('forum_env')['FEATHER_ADMIN'] && (Container::get('user')->g_moderator == '0' || !array_key_exists(Container::get('user')->username, $mods_array))) {
            throw new Error(__('No permission'), 403);
        }

        $cur_topic = $this->model->get_topic_info($fid, $id);

        // Determine the post offset (based on $_GET['p'])
        $num_pages = ceil(($cur_topic['num_replies'] + 1) / Container::get('user')->disp_posts);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);

        $start_from = Container::get('user')->disp_posts * ($p - 1);

        // Delete one or more posts
        if ($this->feather->request->post('delete_posts') || $this->feather->request->post('delete_posts_comply')) {
            $posts = $this->model->delete_posts($id, $fid);

            View::setPageInfo(array(
                    'title' => array(Utils::escape($this->feather->config['o_board_title']), __('Moderate')),
                    'active_page' => 'moderate',
                    'posts' => $posts,
                )
            )->addTemplate('moderate/delete_posts.php')->display();
        }
        if ($this->feather->request->post('split_posts') || $this->feather->request->post('split_posts_comply')) {

            View::setPageInfo(array(
                    'title' => array(Utils::escape($this->feather->config['o_board_title']), __('Moderate')),
                    'focus_element' => array('subject','new_subject'),
                    'page' => $p,
                    'active_page' => 'moderate',
                    'id' => $id,
                    'posts' => $this->model->split_posts($id, $fid, $p),
                    'list_forums' => $this->model->get_forum_list_split($fid),
                )
            )->addTemplate('moderate/split_posts.php')->display();

        }

        // Show the moderate posts view

        // Used to disable the Move and Delete buttons if there are no replies to this topic
        $button_status = ($cur_topic['num_replies'] == 0) ? ' disabled="disabled"' : '';

        /*if (isset($_GET['action']) && $_GET['action'] == 'all') {
                Container::get('user')->disp_posts = $cur_topic['num_replies'] + 1;
        }*/

        if ($this->feather->config['o_censoring'] == '1') {
            $cur_topic['subject'] = Utils::censor($cur_topic['subject']);
        }

        View::setPageInfo(array(
                'title' => array(Utils::escape($this->feather->config['o_board_title']), Utils::escape($cur_topic['forum_name']), Utils::escape($cur_topic['subject'])),
                'page' => $p,
                'active_page' => 'moderate',
                'cur_topic' => $cur_topic,
                'url_topic' => Url::url_friendly($cur_topic['subject']),
                'url_forum' => Url::url_friendly($cur_topic['forum_name']),
                'fid' => $fid,
                'id' => $id,
                'paging_links' => '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, 'moderate/topic/'.$id.'/forum/'.$fid.'/action/moderate/#'),
                'post_data' => $this->model->display_posts_moderate($id, $start_from),
                'button_status' => $button_status,
                'start_from' => $start_from,
            )
        )->addTemplate('moderate/posts_view.php')->display();
    }

    public function action($id, $action)
    {
        Container::get('hooks')->fire('controller.topic.action');

        $this->model->handle_actions($id, $action);
    }
}
