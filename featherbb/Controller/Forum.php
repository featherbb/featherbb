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

class Forum
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Model\Forum();
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/forum.mo');
        load_textdomain('featherbb', Container::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/misc.mo');
    }

    public function display($fid, $name = null, $page = null)
    {
        Container::get('hooks')->fire('controller.forum.display');
        // Fetch some informations about the forum
        $cur_forum = $this->model->get_forum_info($fid);

        // Is this a redirect forum? In that case, redirect!
        if ($cur_forum['redirect_url'] != '') {
            Router::redirect(Router::pathFor('Forum', ['id' => $cur_forum['redirect_url']]));
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_forum['moderators'] != '') ? unserialize($cur_forum['moderators']) : array();
        $is_admmod = (Container::get('user')->g_id == Container::get('forum_env')['FEATHER_ADMIN'] || (Container::get('user')->g_moderator == '1' && array_key_exists(Container::get('user')->username, $mods_array))) ? true : false;

        $sort_by = $this->model->sort_forum_by($cur_forum['sort_by']);

        // Can we or can we not post new topics?
        if (($cur_forum['post_topics'] == '' && Container::get('user')->g_post_topics == '1') || $cur_forum['post_topics'] == '1' || $is_admmod) {
            $post_link = "\t\t\t".'<p class="postlink conr"><a href="'.$this->feather->urlFor('newTopic', ['fid' => $fid]).'">'.__('Post topic').'</a></p>'."\n";
        } else {
            $post_link = '';
        }

        // Determine the topic offset (based on $page)
        $num_pages = ceil($cur_forum['num_topics'] / Container::get('user')->disp_topics);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = Container::get('user')->disp_topics * ($p - 1);
        $url_forum = Url::url_friendly($cur_forum['forum_name']);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, 'forum/'.$fid.'/'.$url_forum.'/#');

        $forum_actions = $this->model->get_forum_actions($fid, $this->feather->forum_settings['o_forum_subscriptions'], $cur_forum['is_subscribed']);

        $this->feather->template->addAsset('canonical', $this->feather->urlFor('Forum', ['id' => $fid, 'name' => $url_forum]));
        if ($num_pages > 1) {
            if ($p > 1) {
                $this->feather->template->addAsset('prev', $this->feather->urlFor('ForumPaginate', ['id' => $fid, 'name' => $url_forum, 'page' => intval($p-1)]));
            }
            if ($p < $num_pages) {
                $this->feather->template->addAsset('next', $this->feather->urlFor('ForumPaginate', ['id' => $fid, 'name' => $url_forum, 'page' => intval($p+1)]));
            }
        }

        if ($this->feather->forum_settings['o_feed_type'] == '1') {
            $this->feather->template->addAsset('feed', 'extern.php?action=feed&amp;fid='.$fid.'&amp;type=rss', array('title' => __('RSS forum feed')));
        } elseif ($this->feather->forum_settings['o_feed_type'] == '2') {
            $this->feather->template->addAsset('feed', 'extern.php?action=feed&amp;fid='.$fid.'&amp;type=atom', array('title' => __('Atom forum feed')));
        }

        $this->feather->template->setPageInfo(array(
            'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), Utils::escape($cur_forum['forum_name'])),
            'active_page' => 'Forum',
            'page_number'  =>  $p,
            'paging_links'  =>  $paging_links,
            'is_indexed' => true,
            'id' => $fid,
            'fid' => $fid,
            'forum_data' => $this->model->print_topics($fid, $sort_by, $start_from),
            'cur_forum' => $cur_forum,
            'post_link' => $post_link,
            'start_from' => $start_from,
            'url_forum' => $url_forum,
            'forum_actions' => $forum_actions,
        ))->addTemplate('forum.php')->display();
    }

    public function moderate($id, $name = null, $page = null)
    {
        Container::get('hooks')->fire('controller.forum.moderate');

        // Make sure that only admmods allowed access this page
        $moderators = $this->model->get_moderators($id);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if (Container::get('user')->g_id != Container::get('forum_env')['FEATHER_ADMIN'] && (Container::get('user')->g_moderator == '0' || !array_key_exists(Container::get('user')->username, $mods_array))) {
            throw new Error(__('No permission'), 403);
        }

        // Fetch some info about the forum
        $cur_forum = $this->model->get_forum_info($id);

        // Is this a redirect forum? In that case, abort!
        if ($cur_forum['redirect_url'] != '') {
            throw new Error(__('Bad request'), '404');
        }

        $sort_by = $this->model->sort_forum_by($cur_forum['sort_by']);

        // Determine the topic offset (based on $_GET['p'])
        $num_pages = ceil($cur_forum['num_topics'] / Container::get('user')->disp_topics);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = Container::get('user')->disp_topics * ($p - 1);
        $url_forum = Url::url_friendly($cur_forum['forum_name']);

        $this->feather->template->setPageInfo(array(
            'title' => array(Utils::escape($this->feather->config['o_board_title']), Utils::escape($cur_forum['forum_name'])),
            'active_page' => 'moderate',
            'page' => $p,
            'id' => $id,
            'p' => $p,
            'url_forum' => $url_forum,
            'cur_forum' => $cur_forum,
            'paging_links' => '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, 'moderate/forum/'.$id.'/#'),
            'topic_data' => $this->model->display_topics_moderate($id, $sort_by, $start_from),
            'start_from' => $start_from,
            )
        )->addTemplate('moderate/moderator_forum.php')->display();
    }

    public function markread($id)
    {
        Container::get('hooks')->fire('controller.forum.markread');

        $tracked_topics = Track::get_tracked_topics();
        $tracked_topics['forums'][$id] = time();
        Track::set_tracked_topics($tracked_topics);

        Router::redirect(Router::pathFor('Forum', ['id' => $id]), __('Mark forum read redirect'));
    }

    public function subscribe($id)
    {
        Container::get('hooks')->fire('controller.forum.subscribe');

        $this->model->subscribe($id);
        Router::redirect(Router::pathFor('Forum', ['id' => $id]), __('Subscribe redirect'));
    }

    public function unsubscribe($id)
    {
        Container::get('hooks')->fire('controller.forum.unsubscribe');

        $this->model->unsubscribe($id);
        Router::redirect(Router::pathFor('Forum', ['id' => $id]), __('Unsubscribe redirect'));
    }

    public function dealposts($fid, $page)
    {
        Container::get('hooks')->fire('controller.forum.dealposts');

        // Make sure that only admmods allowed access this page
        $moderators = $this->model->get_moderators($fid);
        $mods_array = ($moderators != '') ? unserialize($moderators) : array();

        if (Container::get('user')->g_id != Container::get('forum_env')['FEATHER_ADMIN'] && (Container::get('user')->g_moderator == '0' || !array_key_exists(Container::get('user')->username, $mods_array))) {
            throw new Error(__('No permission'), 403);
        }

        $topicModel = new \FeatherBB\Model\Topic();

        // Move one or more topics
        if ($this->feather->request->post('move_topics') || $this->feather->request->post('move_topics_to')) {
            $topics = $this->feather->request->post('topics') ? $this->feather->request->post('topics') : array();
            if (empty($topics)) {
                throw new Error(__('No topics selected'), 400);
            }

            if ($new_fid = $this->feather->request->post('move_to_forum')) {
                $topics = explode(',', $topics);
                $topicModel->move_to($fid, $new_fid, $topics);
                Router::redirect(Router::pathFor('Forum', ['id' => $new_fid]), __('Move topics redirect'));
            }

            // Check if there are enough forums to move the topic
            if ( !$topicModel->check_move_possible() ) {
                throw new Error(__('Nowhere to move'), 403);
            }

            $this->feather->template->setPageInfo(array(
                    'action'    =>    'multi',
                    'title' => array(Utils::escape($this->feather->config['o_board_title']), __('Moderate')),
                    'active_page' => 'moderate',
                    'id'    =>    $fid,
                    'topics'    =>    implode(',', array_map('intval', array_keys($topics))),
                    'list_forums'   => $topicModel->get_forum_list_move($fid),
                )
            )->addTemplate('moderate/move_topics.php')->display();
        }

        // Merge two or more topics
        elseif ($this->feather->request->post('merge_topics') || $this->feather->request->post('merge_topics_comply')) {
            if ($this->feather->request->post('merge_topics_comply')) {
                $this->model->merge_topics($fid);
                Router::redirect(Router::pathFor('Forum', array('id' => $fid)), __('Merge topics redirect'));
            }

            $topics = $this->feather->request->post('topics') ? $this->feather->request->post('topics') : array();
            if (count($topics) < 2) {
                throw new Error(__('Not enough topics selected'), 400);
            }

            $this->feather->template->setPageInfo(array(
                    'title' => array(Utils::escape($this->feather->config['o_board_title']), __('Moderate')),
                    'active_page' => 'moderate',
                    'id'    =>    $fid,
                    'topics'    =>    $topics,
                )
            )->addTemplate('moderate/merge_topics.php')->display();
        }

        // Delete one or more topics
        elseif ($this->feather->request->post('delete_topics') || $this->feather->request->post('delete_topics_comply')) {
            $topics = $this->feather->request->post('topics') ? $this->feather->request->post('topics') : array();
            if (empty($topics)) {
                throw new Error(__('No topics selected'), 400);
            }

            if ($this->feather->request->post('delete_topics_comply')) {
                $this->model->delete_topics($topics, $fid);
                Router::redirect(Router::pathFor('Forum', array('id' => $fid)), __('Delete topics redirect'));
            }

            $this->feather->template->setPageInfo(array(
                    'title' => array(Utils::escape($this->feather->config['o_board_title']), __('Moderate')),
                    'active_page' => 'moderate',
                    'id'    =>    $fid,
                    'topics'    =>    $topics,
                )
            )->addTemplate('moderate/delete_topics.php')->display();
        }


        // Open or close one or more topics
        elseif ($this->feather->request->post('open') || $this->feather->request->post('close')) {
            $action = ($this->feather->request->post('open')) ? 0 : 1;

            // There could be an array of topic IDs in $_POST
            if ($this->feather->request->post('open') || $this->feather->request->post('close')) {
                $topics = $this->feather->request->post('topics') ? @array_map('intval', @array_keys($this->feather->request->post('topics'))) : array();
                if (empty($topics)) {
                    throw new Error(__('No topics selected'), 400);
                }

                $this->model->close_multiple_topics($action, $topics);

                $redirect_msg = ($action) ? __('Close topics redirect') : __('Open topics redirect');
                Router::redirect(Router::pathFor('moderateForum', array('fid' => $fid, 'page' => $page)), $redirect_msg);
            }
        }
    }
}
