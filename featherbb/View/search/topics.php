<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

foreach ($display['cur_search'] as $search) {
    ?>

    <tr class="<?php echo $search['item_status'] ?>">
        <td class="tcl">
            <div class="<?php echo $search['icon_type'] ?>">
                <div class="nosize"><?php echo Utils::forum_number_format($search['topic_count'] + $search['start_from']) ?></div>
            </div>
            <div class="tclcon">
                <div>
                    <?php echo $search['subject'] . "\n" ?>
                </div>
            </div>
        </td>
        <td class="tc2"><?php echo $search['forum'] ?></td>
        <td class="tc3"><?php echo Utils::forum_number_format($search['num_replies']) ?></td>
        <td class="tcr"><?php echo '<a href="' . $feather->urlFor('viewPost', ['pid' => $search['last_post_id']]) . '#p' . $search['last_post_id'] . '">' . $feather->utils->format_time($search['last_post']) . '</a> <span class="byuser">' . __('by') . ' ' . Utils::escape($search['last_poster']) ?></span></td>
    </tr>

    <?php
}
