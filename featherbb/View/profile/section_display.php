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

Container::get('hooks')->fire('view.profile.section_display.start');
?>

                <div class="blockform">
                    <h2><span><?= Utils::escape($user['username']).' - '.__('Section display') ?></span></h2>
                    <div class="box">
                        <form id="profile5" method="post" action="<?= Router::pathFor('profileSection', ['id' => $id, 'section' => 'display']) ?>">
                            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                            <input type="hidden" name="form_sent" value="1" />
<?php

$styles = \FeatherBB\Core\Lister::getStyles();

// Only display the style selection box if there's more than one style available
if (count($styles) == 1) {
    echo "\t\t\t\t\t\t\t".'<input type="hidden" name="form_style" value="'.$styles[0].'" />'."\n";
} elseif (count($styles) > 1) {
    ?>
                            <div class="inform">
                                <fieldset>
                                    <legend><?= __('Style legend') ?></legend>
                                    <div class="infldset">
                                        <label><?= __('Styles') ?><br />
                                        <select name="form_style">
<?php

    foreach ($styles as $temp) {
        if (User::getPref('style', $user) == $temp) {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
        } else {
            echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
        }
    } ?>
                                        </select>
                                        <br /></label>
                                    </div>
                                </fieldset>
                            </div>
<?php

}

?>
<?php if (ForumSettings::get('show.smilies') == '1' || ForumSettings::get('show.smilies.sig') == '1' || ForumSettings::get('o_signatures') == '1' || ForumSettings::get('o_avatars') == '1' || (ForumSettings::get('p_message_bbcode') == '1' && ForumSettings::get('p_message_img_tag') == '1')): ?>
                            <div class="inform">
                                <fieldset>
                                    <legend><?= __('Post display legend') ?></legend>
                                    <div class="infldset">
                                        <p><?= __('Post display info') ?></p>
                                        <div class="rbox">
<?php if (ForumSettings::get('show.smilies') == '1' || ForumSettings::get('show.smilies.sig') == '1'): ?>
                                            <label><input type="checkbox" name="form_show_smilies" value="1"<?php if (User::getPref('show.smilies', $user) == '1') {
    echo ' checked="checked"';
} ?> /><?= __('Show smilies') ?><br /></label>
<?php endif; if (ForumSettings::get('o_signatures') == '1'): ?>
                                            <label><input type="checkbox" name="form_show_sig" value="1"<?php if (User::getPref('show.sig', $user) == '1') {
    echo ' checked="checked"';
} ?> /><?= __('Show sigs') ?><br /></label>
<?php endif; if (ForumSettings::get('o_avatars') == '1'): ?>
                                            <label><input type="checkbox" name="form_show_avatars" value="1"<?php if (User::getPref('show.avatars', $user) == '1') {
    echo ' checked="checked"';
} ?> /><?= __('Show avatars') ?><br /></label>
<?php endif; if (ForumSettings::get('p_message_bbcode') == '1' && ForumSettings::get('p_message_img_tag') == '1'): ?>
                                            <label><input type="checkbox" name="form_show_img" value="1"<?php if (User::getPref('show.img', $user) == '1') {
    echo ' checked="checked"';
} ?> /><?= __('Show images') ?><br /></label>
<?php endif; if (ForumSettings::get('o_signatures') == '1' && ForumSettings::get('p_sig_bbcode') == '1' && ForumSettings::get('p_sig_img_tag') == '1'): ?>
                                            <label><input type="checkbox" name="form_show_img_sig" value="1"<?php if (User::getPref('show.img.sig', $user) == '1') {
    echo ' checked="checked"';
} ?> /><?= __('Show images sigs') ?><br /></label>
<?php endif; ?>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
<?php endif; ?>
                            <div class="inform">
                                <fieldset>
                                    <legend><?= __('Pagination legend') ?></legend>
                                    <div class="infldset">
                                        <label class="conl"><?= __('Topics per page') ?><br /><input type="text" name="form_disp_topics" value="<?= (User::getPref('disp.topics', $user) != ForumSettings::get('disp.topics')) ? User::getPref('disp.topics', $user) : '' ?>" size="6" maxlength="2" /><br /></label>
                                        <label class="conl"><?= __('Posts per page') ?><br /><input type="text" name="form_disp_posts" value="<?= (User::getPref('disp.posts', $user) != ForumSettings::get('disp.posts')) ? User::getPref('disp.posts', $user) : '' ?>" size="6" maxlength="2" /><br /></label>
                                        <p class="clearb"><?= __('Paginate info') ?> <?= __('Leave blank') ?></p>
                                    </div>
                                </fieldset>
                            </div>
                            <p class="buttons"><input type="submit" name="update" value="<?= __('Submit') ?>" /> <?= __('Instructions') ?></p>
                        </form>
                    </div>
                </div>
                <div class="clearer"></div>
            </div>

<?php
Container::get('hooks')->fire('view.profile.section_display.end');
