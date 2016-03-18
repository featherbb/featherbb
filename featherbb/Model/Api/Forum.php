<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Api;

use FeatherBB\Core\Error;

class Forum extends Api
{
    public function display($id)
    {
        $forum = new \FeatherBB\Model\Forum();

        Container::get('hooks')->bind('model.forum.get_info_forum_query', function ($cur_forum) {
            $cur_forum = $cur_forum->select('f.num_posts');
            return $cur_forum;
        });

        try {
            $data = $forum->get_forum_info($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->as_array();

        $data['moderators'] = unserialize($data['moderators']);

        return $data;
    }
}