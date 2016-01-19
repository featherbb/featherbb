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

$feather->hooks->fire('view.userlist.start');
?>

<div class="blockform">
    <h2><span><?php _e('User search') ?></span></h2>
    <div class="box">
        <form id="userlist" method="get" action="">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('User find legend') ?></legend>
                    <div class="infldset">
<?php if ($feather->user->g_search_users == '1'): ?>                        <label class="conl"><?php _e('Username') ?><br /><input type="text" name="username" value="<?= Utils::escape($username) ?>" size="25" maxlength="25" /><br /></label>
<?php endif; ?>                        <label class="conl"><?php _e('User group')."\n" ?>
                        <br /><select name="show_group">
                            <option value="-1"<?php if ($show_group == -1) {
    echo ' selected="selected"';
} ?>><?php _e('All users') ?></option>
<?= $dropdown_menu ?>
                        </select>
                        <br /></label>
                        <label class="conl"><?php _e('Sort by')."\n" ?>
                        <br /><select name="sort_by">
                            <option value="username"<?php if ($sort_by == 'username') {
    echo ' selected="selected"';
} ?>><?php _e('Username') ?></option>
                            <option value="registered"<?php if ($sort_by == 'registered') {
    echo ' selected="selected"';
} ?>><?php _e('Registered') ?></option>
<?php if ($show_post_count): ?>                            <option value="num_posts"<?php if ($sort_by == 'num_posts') {
    echo ' selected="selected"';
} ?>><?php _e('No of posts') ?></option>
<?php endif; ?>                        </select>
                        <br /></label>
                        <label class="conl"><?php _e('Sort order')."\n" ?>
                        <br /><select name="sort_dir">
                            <option value="ASC"<?php if ($sort_dir == 'ASC') {
    echo ' selected="selected"';
} ?>><?php _e('Ascending') ?></option>
                            <option value="DESC"<?php if ($sort_dir == 'DESC') {
    echo ' selected="selected"';
} ?>><?php _e('Descending') ?></option>
                        </select>
                        <br /></label>
                        <p class="clearb"><?php echo($feather->user->g_search_users == '1' ? __('User search info').' ' : '').__('User sort info'); ?></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="search" value="<?php _e('Submit') ?>" accesskey="s" /></p>
        </form>
    </div>
</div>

<div class="linkst">
    <div class="inbox">
        <p class="pagelink"><?= $paging_links ?></p>
        <div class="clearer"></div>
    </div>
</div>

<div id="users1" class="blocktable">
    <h2><span><?php _e('User list') ?></span></h2>
    <div class="box">
        <div class="inbox">
            <table>
            <thead>
                <tr>
                    <th class="tcl" scope="col"><?php _e('Username') ?></th>
                    <th class="tc2" scope="col"><?php _e('Title') ?></th>
<?php if ($show_post_count): ?>                    <th class="tc3" scope="col"><?php _e('Posts') ?></th>
<?php endif; ?>                    <th class="tcr" scope="col"><?php _e('Registered') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($userlist_data as $user) {
                ?>
                    <tr>
                        <td class="tcl"><?= '<a href="'.$feather->urlFor('userProfile', ['id' => $user['id']]).'">'.Utils::escape($user['username']).'</a>' ?></td>
                        <td class="tc2"><?= Utils::get_title($user) ?></td>
    <?php if ($show_post_count): ?>                    <td class="tc3"><?= Utils::forum_number_format($user['num_posts']) ?></td>
    <?php endif;
                ?>
                        <td class="tcr"><?= $feather->utils->format_time($user['registered'], true) ?></td>
                    </tr>
            <?php

            }
            if (empty($userlist_data)) {
                echo "\t\t\t".'<tr>'."\n\t\t\t\t\t".'<td class="tcl" colspan="'.(($show_post_count) ? 4 : 3).'">'.__('No hits').'</td></tr>'."\n";
            }
            ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<div class="linksb">
    <div class="inbox">
        <p class="pagelink"><?= $paging_links ?></p>
        <div class="clearer"></div>
    </div>
</div>
<?php
$feather->hooks->fire('view.userlist.end');
