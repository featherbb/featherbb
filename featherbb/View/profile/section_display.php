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

Container::get('hooks')->fire('view.profile.section_display.start');
?>

<div class="blockform">
    <h2><span><?= Utils::escape($user['username']).' - '.__('Section display') ?></span></h2>
    <div class="box">
        <form id="profile5" method="post" action="<?= Router::pathFor('profileSection', ['id' => $id, 'section' => 'display']) ?>">
            <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
            <div><input type="hidden" name="form_sent" value="1" /></div>
<?php

    $styles = \FeatherBB\Core\Lister::getStyles();

    // Only display the style selection box if there's more than one style available
    if (count($styles) == 1) {
        echo "\t\t\t".'<div><input type="hidden" name="form_style" value="'.$styles[0].'" /></div>'."\n";
    } elseif (count($styles) > 1) {
        ?>
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Style legend') ?></legend>
                    <div class="infldset">
                        <label><?php _e('Styles') ?><br />
                        <select name="form_style">
<?php

        foreach ($styles as $temp) {
            if ($user['style'] == $temp) {
                echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'" selected="selected">'.str_replace('_', ' ', $temp).'</option>'."\n";
            } else {
                echo "\t\t\t\t\t\t\t\t".'<option value="'.$temp.'">'.str_replace('_', ' ', $temp).'</option>'."\n";
            }
        }

        ?>
                        </select>
                        <br /></label>
                    </div>
                </fieldset>
            </div>
<?php

    }

?>
<?php if (Config::get('forum_settings')['o_smilies'] == '1' || Config::get('forum_settings')['o_smilies_sig'] == '1' || Config::get('forum_settings')['o_signatures'] == '1' || Config::get('forum_settings')['o_avatars'] == '1' || (Config::get('forum_settings')['p_message_bbcode'] == '1' && Config::get('forum_settings')['p_message_img_tag'] == '1')): ?>
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Post display legend') ?></legend>
                    <div class="infldset">
                        <p><?php _e('Post display info') ?></p>
                        <div class="rbox">
<?php if (Config::get('forum_settings')['o_smilies'] == '1' || Config::get('forum_settings')['o_smilies_sig'] == '1'): ?>                                <label><input type="checkbox" name="form_show_smilies" value="1"<?php if ($user['show_smilies'] == '1') {
    echo ' checked="checked"';
} ?> /><?php _e('Show smilies') ?><br /></label>
<?php endif; if (Config::get('forum_settings')['o_signatures'] == '1'): ?>                                <label><input type="checkbox" name="form_show_sig" value="1"<?php if ($user['show_sig'] == '1') {
    echo ' checked="checked"';
} ?> /><?php _e('Show sigs') ?><br /></label>
<?php endif; if (Config::get('forum_settings')['o_avatars'] == '1'): ?>                                <label><input type="checkbox" name="form_show_avatars" value="1"<?php if ($user['show_avatars'] == '1') {
    echo ' checked="checked"';
} ?> /><?php _e('Show avatars') ?><br /></label>
<?php endif; if (Config::get('forum_settings')['p_message_bbcode'] == '1' && Config::get('forum_settings')['p_message_img_tag'] == '1'): ?>                                <label><input type="checkbox" name="form_show_img" value="1"<?php if ($user['show_img'] == '1') {
    echo ' checked="checked"';
} ?> /><?php _e('Show images') ?><br /></label>
<?php endif; if (Config::get('forum_settings')['o_signatures'] == '1' && Config::get('forum_settings')['p_sig_bbcode'] == '1' && Config::get('forum_settings')['p_sig_img_tag'] == '1'): ?>                                <label><input type="checkbox" name="form_show_img_sig" value="1"<?php if ($user['show_img_sig'] == '1') {
    echo ' checked="checked"';
} ?> /><?php _e('Show images sigs') ?><br /></label>
<?php endif; ?>
                        </div>
                    </div>
                </fieldset>
            </div>
<?php endif; ?>
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Pagination legend') ?></legend>
                    <div class="infldset">
                        <label class="conl"><?php _e('Topics per page') ?><br /><input type="text" name="form_disp_topics" value="<?= $user['disp_topics'] ?>" size="6" maxlength="2" /><br /></label>
                        <label class="conl"><?php _e('Posts per page') ?><br /><input type="text" name="form_disp_posts" value="<?= $user['disp_posts'] ?>" size="6" maxlength="2" /><br /></label>
                        <p class="clearb"><?php _e('Paginate info') ?> <?php _e('Leave blank') ?></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="update" value="<?php _e('Submit') ?>" /> <?php _e('Instructions') ?></p>
        </form>
    </div>
</div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.profile.section_display.end');
