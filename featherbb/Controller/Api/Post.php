<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Api;

use FeatherBB\Core\Error;
use FeatherBB\Core\Utils;

class Post extends Api
{
    private $model;
    
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Api\Post();
    }

    public function display($req, $res, $args)
    {
        return json_encode($this->model->display($args['id']), JSON_PRETTY_PRINT);
    }

    public function delete($req, $res, $args)
    {
        // Fetch some information about the post, the topic and the forum
        $cur_post = \FeatherBB\Model\Post::get_info_delete($args['id']);

        if (ForumSettings::get('o_censoring') == '1') {
            $cur_post['subject'] = Utils::censor($cur_post['subject']);
        }

        // Sort out who the moderators are and if we are currently a moderator (or an admin)
        $is_topic_post = $this->model->getPermissions($cur_post, $args);

        \FeatherBB\Model\Post::handle_deletion($is_topic_post, $args['id'], $cur_post);

        return json_encode("Success", JSON_PRETTY_PRINT);
    }
}