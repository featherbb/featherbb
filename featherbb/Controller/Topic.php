<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

class Topic
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Model\Topic();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/topic.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/post.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/bbeditor.mo');
    }

    public function display($id = null, $name = null, $page = null, $pid = null)
    {
        // Antispam feature
        require $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/antispam.php';
        $index_questions = rand(0, count($lang_antispam_questions)-1);

        // Fetch some informations about the topic
        $cur_topic = $this->model->get_info_topic($id);

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
        $is_admmod = ($this->feather->user->g_id == FEATHER_ADMIN || ($this->feather->user->g_moderator == '1' && array_key_exists($this->feather->user->username, $mods_array))) ? true : false;
        if ($is_admmod) {
            $admin_ids = get_admin_ids();
        }

        // Can we or can we not post replies?
        $post_link = $this->model->get_post_link($id, $cur_topic['closed'], $cur_topic['post_replies'], $is_admmod);

        // Add/update this topic in our list of tracked topics
        if (!$this->feather->user->is_guest) {
            $tracked_topics = get_tracked_topics();
            $tracked_topics['topics'][$id] = time();
            set_tracked_topics($tracked_topics);
        }

        // Determine the post offset (based on $_GET['p'])
        $num_pages = ceil(($cur_topic['num_replies'] + 1) / $this->feather->user->disp_posts);

        $p = (!isset($page) || $page <= 1 || $page > $num_pages) ? 1 : intval($page);
        $start_from = $this->feather->user->disp_posts * ($p - 1);

        $url_topic = Url::url_friendly($cur_topic['subject']);
        $url_forum = Url::url_friendly($cur_topic['forum_name']);

        // Generate paging links
        $paging_links = '<span class="pages-label">'.__('Pages').' </span>'.Url::paginate($num_pages, $p, 'topic/'.$id.'/'.$url_topic.'/#');

        if ($this->feather->forum_settings['o_censoring'] == '1') {
            $cur_topic['subject'] = censor_words($cur_topic['subject']);
        }

        $quickpost = $this->model->is_quickpost($cur_topic['post_replies'], $cur_topic['closed'], $is_admmod);
        $subscraction = $this->model->get_subscraction($cur_topic['is_subscribed'], $id);

        $lang_bbeditor = array(
            'btnBold' => __('btnBold'),
            'btnItalic' => __('btnItalic'),
            'btnUnderline' => __('btnUnderline'),
            'btnColor' => __('btnColor'),
            'btnLeft' => __('btnLeft'),
            'btnRight' => __('btnRight'),
            'btnJustify' => __('btnJustify'),
            'btnCenter' => __('btnCenter'),
            'btnLink' => __('btnLink'),
            'btnPicture' => __('btnPicture'),
            'btnList' => __('btnList'),
            'btnQuote' => __('btnQuote'),
            'btnCode' => __('btnCode'),
            'promptImage' => __('promptImage'),
            'promptUrl' => __('promptUrl'),
            'promptQuote' => __('promptQuote')
        );

        $this->feather->template->addAsset('canonical', $this->feather->urlFor('Forum', ['id' => $id, 'name' => $url_forum, 'page' => $p]));
        if ($num_pages > 1) {
            if ($p > 1) {
                $this->feather->template->addAsset('prev', $this->feather->urlFor('Forum', ['id' => $id, 'name' => $url_forum, 'page' => intval($p-1)]));
            }
            if ($p < $num_pages) {
                $this->feather->template->addAsset('next', $this->feather->urlFor('Forum', ['id' => $id, 'name' => $url_forum, 'page' => intval($p+1)]));
            }
        }

        if ($this->feather->forum_settings['o_feed_type'] == '1') {
            $this->feather->template->addAsset('feed', 'extern.php?action=feed&amp;fid='.$id.'&amp;type=rss', array('title' => __('RSS forum feed')));
        } elseif ($this->feather->forum_settings['o_feed_type'] == '2') {
            $this->feather->template->addAsset('feed', 'extern.php?action=feed&amp;fid='.$id.'&amp;type=atom', array('title' => __('Atom forum feed')));
        }

        $this->feather->template->setPageInfo(array(
            'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), Utils::escape($cur_topic['forum_name']), Utils::escape($cur_topic['subject'])),
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
            'lang_bbeditor'    =>    $lang_bbeditor,
            'url_forum'        =>    $url_forum,
            'url_topic'        =>    $url_topic,
        ))->addTemplate('Topic.php')->display();

        // Increment "num_views" for topic
        $this->model->increment_views($id);
    }

    public function viewpost($pid)
    {
        $post = $this->model->redirect_to_post($pid);

        return $this->display($post['topic_id'], null, $post['get_p'], $pid);
    }

    public function action($id, $action)
    {
        $this->model->handle_actions($id, $action);
    }
}
