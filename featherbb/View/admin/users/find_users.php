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

Container::get('hooks')->fire('view.admin.users.find_users.start');
?>

<div class="linkst">
    <div class="inbox crumbsplus">
        <ul class="crumbs">
            <li><a href="<?= Router::pathFor('adminIndex') ?>"><?php _e('Admin'); echo ' '; _e('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('adminUsers') ?>"><?php _e('Users') ?></a></li>
            <li><span>»&#160;</span><strong><?php _e('Results head') ?></strong></li>
        </ul>
        <div class="pagepost">
            <p class="pagelink"><?= $paging_links ?></p>
        </div>
        <div class="clearer"></div>
    </div>
</div>


<form id="search-users-form" action="<?= Router::pathFor('adminUsers') ?>" method="post">
<input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
<div id="users2" class="blocktable">
    <h2><span><?php _e('Results head') ?></span></h2>
    <div class="box">
        <div class="inbox">
            <table>
            <thead>
                <tr>
                    <th class="tcl" scope="col"><?php _e('Results username head') ?></th>
                    <th class="tc2" scope="col"><?php _e('Results e-mail head') ?></th>
                    <th class="tc3" scope="col"><?php _e('Results title head') ?></th>
                    <th class="tc4" scope="col"><?php _e('Results posts head') ?></th>
                    <th class="tc5" scope="col"><?php _e('Results admin note head') ?></th>
                    <th class="tcr" scope="col"><?php _e('Results actions head') ?></th>
<?php if ($can_action): ?>                    <th class="tcmod" scope="col"><?php _e('Select') ?></th>
<?php endif;
    ?>
                </tr>
            </thead>
            <tbody>
<?php
    if (!empty($user_data)) {
        foreach ($user_data as $user) {
            ?>
                <tr>
                    <td class="tcl"><?= '<a href="'.Router::pathFor('userProfile', ['id' => $user['id']]).'">'.Utils::escape($user['username']).'</a>' ?></td>
                    <td class="tc2"><a href="mailto:<?= Utils::escape($user['email']) ?>"><?= Utils::escape($user['email']) ?></a></td>
                    <td class="tc3"><?= $user['user_title'] ?></td>
                    <td class="tc4"><?= Utils::forum_number_format($user['num_posts']) ?></td>
                    <td class="tc5"><?php echo($user['admin_note'] != '') ? Utils::escape($user['admin_note']) : '&#160;' ?></td>
                    <td class="tcr"><?= '<a href="'.Router::pathFor('usersIpStats', ['id' => $user['id']]).'">'.__('Results view IP link').'</a> | <a href="'.Router::pathFor('search').'?action=show_user_posts&amp;user_id='.$user['id'].'">'.__('Results show posts link').'</a>' ?></td>
<?php if ($can_action): ?>                    <td class="tcmod"><input type="checkbox" name="users[<?= $user['id'] ?>]" value="1" /></td>
<?php endif;
            ?>
                </tr>
<?php

        }
    } else {
        echo "\t\t\t\t".'<tr><td class="tcl" colspan="6">'.__('No match').'</td></tr>'."\n";
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
<?php if ($can_action): ?>            <p class="conr modbuttons"><a href="#" onclick="return select_checkboxes('search-users-form', this, '<?php _e('Unselect all') ?>')"><?php _e('Select all') ?></a> <?php if ($can_ban) : ?><input type="submit" name="ban_users" value="<?php _e('Ban') ?>" /><?php endif;
    if ($can_delete) : ?><input type="submit" name="delete_users" value="<?php _e('Delete') ?>" /><?php endif;
    if ($can_move) : ?><input type="submit" name="move_users" value="<?php _e('Change group') ?>" /><?php endif;
    ?></p>
<?php endif;
    ?>
        </div>
        <ul class="crumbs">
            <li><a href="<?= Router::pathFor('adminIndex') ?>"><?php _e('Admin'); echo ' '; _e('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('adminUsers') ?>"><?php _e('Users') ?></a></li>
            <li><span>»&#160;</span><strong><?php _e('Results head') ?></strong></li>
        </ul>
        <div class="clearer"></div>
    </div>
</div>
</form>

<?php
Container::get('hooks')->fire('view.admin.users.find_users.end');
