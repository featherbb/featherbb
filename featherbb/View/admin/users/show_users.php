<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.users.show_users.start');
?>

<div class="linkst">
    <div class="inbox crumbsplus">
        <ul class="crumbs">
            <li><a href="<?= Router::pathFor('adminIndex') ?>"><?= __('Admin'); echo ' '; __('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('adminUsers') ?>"><?= __('Users') ?></a></li>
            <li><span>»&#160;</span><strong><?= __('Results head') ?></strong></li>
        </ul>
        <div class="pagepost">
            <p class="pagelink"><?= $paging_links ?></p>
        </div>
        <div class="clearer"></div>
    </div>
</div>

<div id="users2" class="blocktable">
    <h2><span><?= __('Results head') ?></span></h2>
    <div class="box">
        <div class="inbox">
            <table>
            <thead>
                <tr>
                    <th class="tcl" scope="col"><?= __('Results username head') ?></th>
                    <th class="tc2" scope="col"><?= __('Results e-mail head') ?></th>
                    <th class="tc3" scope="col"><?= __('Results title head') ?></th>
                    <th class="tc4" scope="col"><?= __('Results posts head') ?></th>
                    <th class="tc5" scope="col"><?= __('Results admin note head') ?></th>
                    <th class="tcr" scope="col"><?= __('Results actions head') ?></th>
                </tr>
            </thead>
            <tbody>
<?php
    if ($info['num_posts']) {

        // Loop through users and print out some info
        foreach ($info['posters'] as $cur_poster) {
            if (isset($info['user_data'][$cur_poster['poster_id']])) {
                ?>
                <tr>
                    <td class="tcl"><?= '<a href="'.Router::pathFor('userProfile', ['id' => $info['user_data'][$cur_poster['poster_id']]['id']]).'">'.Utils::escape($info['user_data'][$cur_poster['poster_id']]['username']).'</a>' ?></td>
                    <td class="tc2"><a href="mailto:<?= Utils::escape($info['user_data'][$cur_poster['poster_id']]['email']) ?>"><?= Utils::escape($info['user_data'][$cur_poster['poster_id']]['email']) ?></a></td>
                    <td class="tc3"><?= Utils::get_title($info['user_data'][$cur_poster['poster_id']]) ?></td>
                    <td class="tc4"><?= Utils::forum_number_format($info['user_data'][$cur_poster['poster_id']]['num_posts']) ?></td>
                    <td class="tc5"><?php echo($info['user_data'][$cur_poster['poster_id']]['admin_note'] != '') ? Utils::escape($info['user_data'][$cur_poster['poster_id']]['admin_note']) : '&#160;' ?></td>
                    <td class="tcr"><?= '<a href="'.Router::pathFor('usersIpStats', ['id' => $info['user_data'][$cur_poster['poster_id']]['id']]).'">'.__('Results view IP link').'</a> | <a href="'.Router::pathFor('search').'?action=show_user_posts&amp;user_id='.$info['user_data'][$cur_poster['poster_id']]['id'].'">'.__('Results show posts link').'</a>' ?></td>
                </tr>
<?php

            } else {
                ?>
                <tr>
                    <td class="tcl"><?= Utils::escape($cur_poster['poster']) ?></td>
                    <td class="tc2">&#160;</td>
                    <td class="tc3"><?= __('Results guest') ?></td>
                    <td class="tc4">&#160;</td>
                    <td class="tc5">&#160;</td>
                    <td class="tcr">&#160;</td>
                </tr>
<?php

            }
        }
    } else {
        echo "\t\t\t\t".'<tr><td class="tcl" colspan="6">'.__('Results no IP found').'</td></tr>'."\n";
    }

    ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<div class="linksb">
    <div class="inbox crumbsplus">
        <div class="pagepost">
            <p class="pagelink"><?= $paging_links ?></p>
        </div>
        <ul class="crumbs">
            <li><a href="<?= Router::pathFor('adminIndex') ?>"><?= __('Admin'); echo ' '; __('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('adminUsers') ?>"><?= __('Users') ?></a></li>
            <li><span>»&#160;</span><strong><?= __('Results head') ?></strong></li>
        </ul>
        <div class="clearer"></div>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.admin.users.show_users.end');
