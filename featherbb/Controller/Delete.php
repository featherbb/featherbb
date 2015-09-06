<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

class Delete
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Model\Delete();
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/delete.mo');
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->feather->user->language.'/post.mo');
    }

    public function deletepost($id)
    {
        // Fetch some informations about the post, the topic and the forum
        $cur_post = $this->model->get_info_delete($id);

        if ($this->feather->forum_settings['o_censoring'] == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
        $is_admmod = ($this->feather->user->g_id == FEATHER_ADMIN || ($this->feather->user->g_moderator == '1' && array_key_exists($this->feather->user->username, $mods_array))) ? true : false;

        $is_topic_post = ($id == $cur_post['first_post_id']) ? true : false;

        // Do we have permission to edit this post?
        if (($this->feather->user->g_delete_posts == '0' ||
                ($this->feather->user->g_delete_topics == '0' && $is_topic_post) ||
                $cur_post['poster_id'] != $this->feather->user->id ||
                $cur_post['closed'] == '1') &&
                !$is_admmod) {
            throw new Error(__('No permission'), 403);
        }

        if ($is_admmod && $this->feather->user->g_id != FEATHER_ADMIN && in_array($cur_post['poster_id'], Utils::get_admin_ids())) {
            throw new Error(__('No permission'), 403);
        }

        if ($this->feather->request()->isPost()) {
            $this->model->handle_deletion($is_topic_post, $id, $cur_post['tid'], $cur_post['fid']);
        }

        $cur_post['message'] = $this->feather->parser->parse_message($cur_post['message'], $cur_post['hide_smilies']);

        $this->feather->template->setPageInfo(array(
            'title' => array(Utils::escape($this->feather->forum_settings['o_board_title']), __('Delete post')),
            'active_page' => 'delete',
            'cur_post' => $cur_post,
            'id' => $id,
            'is_topic_post' => $is_topic_post
        ))->addTemplate('delete.php')->display();
    }
}
