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

Container::get('hooks')->fire('view.search.topics.start');

foreach ($display['cur_search'] as $search) {
    ?>

    <tr class="<?= $search['item_status'] ?>">
        <td class="tcl">
            <div class="<?= $search['icon_type'] ?>">
                <div class="nosize"><?= Utils::forum_number_format($search['topic_count'] + $search['start_from']) ?></div>
            </div>
            <div class="tclcon">
                <div>
                    <?= $search['subject'] . "\n" ?>
                </div>
            </div>
        </td>
        <td class="tc2"><?= $search['forum'] ?></td>
        <td class="tc3"><?= Utils::forum_number_format($search['num_replies']) ?></td>
        <td class="tcr"><?= '<a href="' . Router::pathFor('viewPost', ['id' => $search['tid'], 'name' => $search['url_topic'], 'pid' => $search['last_post_id']]) . '#p' . $search['last_post_id'] . '">' . Utils::format_time($search['last_post']) . '</a> <span class="byuser">' . __('by') . ' ' . Utils::escape($search['last_poster']) ?></span></td>
    </tr>

    <?php
}

Container::get('hooks')->fire('view.search.topics.end');
