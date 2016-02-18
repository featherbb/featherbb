<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.search.posts.start');


foreach ($display['cur_search'] as $search) {
    ?>
    <div
        class="blockpost<?= ($search['post_count'] % 2 == 0) ? ' roweven' : ' rowodd' ?><?php if ($search['pid'] == $search['first_post_id']) {
            echo ' firstpost';
        } ?><?php if ($search['post_count'] == 1) {
            echo ' blockpost1';
        } ?><?php if ($search['item_status'] != '') {
            echo ' ' . $search['item_status'];
        } ?>">
        <h2><span><span
                    class="conr">#<?php echo($search['post_count']) ?></span> <span><?php if ($search['pid'] != $search['first_post_id']) {
                        _e('Re'); echo  ' ';
                    } ?><?= $search['forum'] ?></span> <span>»&#160;<a
                        href="<?= Router::pathFor('Topic', ['id' => $search['tid'], 'name' => $search['url_topic']]) ?>"><?= Utils::escape($search['subject']) ?></a></span> <span>»&#160;<a
                        href="<?= Router::pathFor('viewPost', ['pid' => $search['pid']]) . '#p' . $search['pid'] ?>"><?= $feather->utils->format_time($search['pposted']) ?></a></span></span>
        </h2>

        <div class="box">
            <div class="inbox">
                <div class="postbody">
                    <div class="postleft">
                        <dl>
                            <dt><?= $search['pposter_disp'] ?></dt>
                            <?php if ($search['pid'] == $search['first_post_id']) : ?>
                                <dd>
                                    <span><?php _e('Replies') . ' ' . Utils::forum_number_format($search['num_replies']) ?></span>
                                </dd>
                            <?php endif; ?>
                            <dd>
                                <div class="<?= $search['icon_type'] ?>">
                                    <div class="nosize"><?= $search['icon_text'] ?></div>
                                </div>
                            </dd>
                        </dl>
                    </div>
                    <div class="postright">
                        <div class="postmsg">
                            <?= $search['message'] . "\n" ?>
                        </div>
                    </div>
                    <div class="clearer"></div>
                </div>
            </div>
            <div class="inbox">
                <div class="postfoot clearb">
                    <div class="postfootright">
                        <ul>
                            <li><span><a
                                        href="<?= Router::pathFor('Topic', ['id' => $search['tid'], 'name' => $search['url_topic']]) ?>"><?php _e('Go to topic') ?></a></span>
                            </li>
                            <li><span><a
                                        href="<?= Router::pathFor('viewPost', ['pid' => $search['pid']]) . '#p' . $search['pid'] ?>"><?php _e('Go to post') ?></a></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

Container::get('hooks')->fire('view.search.posts.end');
