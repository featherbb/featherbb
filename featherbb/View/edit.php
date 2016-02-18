<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
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

$cur_index = 1;

Container::get('hooks')->fire('view.edit.start');
?>

<div class="linkst">
    <div class="inbox">
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>"><?php _e('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= $feather->urlFor('Forum', ['id' => $cur_post['fid'], 'name' => Url::url_friendly($cur_post['forum_name'])]) ?>"><?= Utils::escape($cur_post['forum_name']) ?></a></li>
            <li><span>»&#160;</span><a href="<?= $feather->urlFor('Topic', ['id' => $cur_post['tid'], 'name' => Url::url_friendly($cur_post['subject'])]) ?>"><?= Utils::escape($cur_post['subject']) ?></a></li>
            <li><span>»&#160;</span><strong><?php _e('Edit post') ?></strong></li>
        </ul>
    </div>
</div>

<?php

// If there are errors, we display them
if (!empty($errors)) :
?>
<div id="posterror" class="block">
    <h2><span><?php _e('Post errors') ?></span></h2>
    <div class="box">
        <div class="inbox error-info">
            <p><?php _e('Post errors info') ?></p>
            <ul class="error-list">
<?php
    foreach ($errors as $cur_error) {
        echo "\t\t\t\t".'<li><strong>'.$cur_error.'</strong></li>'."\n";
    }
?>
            </ul>
        </div>
    </div>
</div>
<?php
elseif ($feather->request->post('preview')):
?>
<div id="postpreview" class="blockpost">
    <h2><span><?php _e('Post preview') ?></span></h2>
    <div class="box">
        <div class="inbox">
            <div class="postbody">
                <div class="postright">
                    <div class="postmsg">
                        <?= $preview_message."\n" ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
endif;
?>
<div id="editform" class="blockform">
    <h2><span><?php _e('Edit post') ?></span></h2>
    <div class="box">
        <form id="edit" method="post" action="<?= $feather->urlFor('editPost', ['id' => $id]) ?>" onsubmit="return process_form(this)">
            <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Edit post legend') ?></legend>
                    <input type="hidden" name="form_sent" value="1" />
                    <div class="infldset txtarea">
<?php if ($can_edit_subject): ?>                        <label class="required"><strong><?php _e('Subject') ?> <span><?php _e('Required') ?></span></strong><br />
                        <input class="longinput" type="text" name="req_subject" size="80" maxlength="70" tabindex="<?= $cur_index++ ?>" value="<?= Utils::escape($feather->request->post('req_subject') ? $feather->request->post('req_subject') : $cur_post['subject']) ?>" /><br /></label>
<?php endif; ?>                        <label class="required"><strong><?php _e('Message') ?> <span><?php _e('Required') ?></span></strong><br />
                        <script>postEditorToolbar('req_message');</script>
                        <textarea name="req_message" id="req_message" rows="20" cols="95" tabindex="<?= $cur_index++ ?>"><?= Utils::escape($feather->request->post('req_message') ? $post['message'] : $cur_post['message']) ?></textarea><br /></label>
                        <ul class="bblinks">
                            <li><span><a href="<?= $feather->urlFor('help').'#bbcode' ?>" onclick="window.open(this.href); return false;"><?php _e('BBCode') ?></a> <?php echo($feather->forum_settings['p_message_bbcode'] == '1') ? __('on') : __('off'); ?></span></li>
                            <li><span><a href="<?= $feather->urlFor('help').'#url' ?>" onclick="window.open(this.href); return false;"><?php _e('url tag') ?></a> <?php echo($feather->forum_settings['p_message_bbcode'] == '1' && $feather->user->g_post_links == '1') ? __('on') : __('off'); ?></span></li>
                            <li><span><a href="<?= $feather->urlFor('help').'#img' ?>" onclick="window.open(this.href); return false;"><?php _e('img tag') ?></a> <?php echo($feather->forum_settings['p_message_bbcode'] == '1' && $feather->forum_settings['p_message_img_tag'] == '1') ? __('on') : __('off'); ?></span></li>
                            <li><span><a href="<?= $feather->urlFor('help').'#smilies' ?>" onclick="window.open(this.href); return false;"><?php _e('Smilies') ?></a> <?php echo($feather->forum_settings['o_smilies'] == '1') ? __('on') : __('off'); ?></span></li>
                        </ul>
                    </div>
                </fieldset>
            </div>
<?php
if (!empty($checkboxes)):
?>
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Options') ?></legend>
                    <div class="infldset">
                        <div class="rbox">
                            <?= implode("\n\t\t\t\t\t\t\t", $checkboxes)."\n" ?>
                        </div>
                    </div>
                </fieldset>
            </div>
<?php
endif;
?>
            <p class="buttons"><input type="submit" name="submit" value="<?php _e('Submit') ?>" tabindex="<?= $cur_index++ ?>" accesskey="s" /> <input type="submit" name="preview" value="<?php _e('Preview') ?>" tabindex="<?= $cur_index++ ?>" accesskey="p" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
        </form>
    </div>
</div>
<?php
Container::get('hooks')->fire('view.edit.end');
