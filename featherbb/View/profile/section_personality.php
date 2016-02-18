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

Container::get('hooks')->fire('view.profile.section_personality.start');
?>
<div class="blockform">
    <h2><span><?= Utils::escape($user['username']).' - '.__('Section personality') ?></span></h2>
    <div class="box">
        <form id="profile4" method="post" action="<?= $feather->urlFor('profileSection', ['id' => $id, 'section' => 'personality']) ?>">
            <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
            <div><input type="hidden" name="form_sent" value="1" /></div>
<?php if ($feather->forum_settings['o_avatars'] == '1'): ?>                <div class="inform">
                <fieldset id="profileavatar">
                    <legend><?php _e('Avatar legend') ?></legend>
                    <div class="infldset">
<?php if ($user_avatar): ?>                            <div class="useravatar"><?= $user_avatar ?></div>
<?php endif; ?>                            <p><?php _e('Avatar info') ?></p>
                        <p class="clearb actions"><?= $avatar_field ?></p>
                    </div>
                </fieldset>
            </div>
<?php endif; if ($feather->forum_settings['o_signatures'] == '1'): ?>                <div class="inform">
                <fieldset>
                    <legend><?php _e('Signature legend') ?></legend>
                    <div class="infldset">
                        <p><?php _e('Signature info') ?></p>
                        <div class="txtarea">
                            <label><?php printf(__('Sig max size'), Utils::forum_number_format($feather->forum_settings['p_sig_length']), $feather->forum_settings['p_sig_lines']) ?><br />
                            <textarea name="signature" rows="4" cols="65"><?= Utils::escape($user['signature']) ?></textarea><br /></label>
                        </div>
                        <ul class="bblinks">
                            <li><span><a href="<?= $feather->urlFor('help').'#bbcode' ?>" onclick="window.open(this.href); return false;"><?php _e('BBCode') ?></a> <?php echo($feather->forum_settings['p_sig_bbcode'] == '1') ? __('on') : __('off'); ?></span></li>
                            <li><span><a href="<?= $feather->urlFor('help').'#url' ?>" onclick="window.open(this.href); return false;"><?php _e('url tag') ?></a> <?php echo($feather->forum_settings['p_sig_bbcode'] == '1' && $feather->user->g_post_links == '1') ? __('on') : __('off'); ?></span></li>
                            <li><span><a href="<?= $feather->urlFor('help').'#img' ?>" onclick="window.open(this.href); return false;"><?php _e('img tag') ?></a> <?php echo($feather->forum_settings['p_sig_bbcode'] == '1' && $feather->forum_settings['p_sig_img_tag'] == '1') ? __('on') : __('off'); ?></span></li>
                            <li><span><a href="<?= $feather->urlFor('help').'#smilies' ?>" onclick="window.open(this.href); return false;"><?php _e('Smilies') ?></a> <?php echo($feather->forum_settings['o_smilies_sig'] == '1') ? __('on') : __('off'); ?></span></li>
                        </ul>
                        <?= $signature_preview ?>
                    </div>
                </fieldset>
            </div>
<?php endif; ?>                <p class="buttons"><input type="submit" name="update" value="<?php _e('Submit') ?>" /> <?php _e('Instructions') ?></p>
        </form>
    </div>
</div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.profile.section_personality.end');
