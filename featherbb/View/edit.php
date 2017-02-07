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
            <li><a href="<?= Url::base() ?>"><?= __('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Forum', ['id' => $cur_post['fid'], 'name' => Url::url_friendly($cur_post['forum_name'])]) ?>"><?= Utils::escape($cur_post['forum_name']) ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Topic', ['id' => $cur_post['tid'], 'name' => Url::url_friendly($cur_post['subject'])]) ?>"><?= Utils::escape($cur_post['subject']) ?></a></li>
            <li><span>»&#160;</span><strong><?= __('Edit post') ?></strong></li>
        </ul>
    </div>
</div>

<?php

// If there are errors, we display them
if (!empty($errors)) :
?>
<div id="posterror" class="block">
    <h2><span><?= __('Post errors') ?></span></h2>
    <div class="box">
        <div class="inbox error-info">
            <p><?= __('Post errors info') ?></p>
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
elseif (Input::post('preview')):
?>
<div id="postpreview" class="blockpost">
    <h2><span><?= __('Post preview') ?></span></h2>
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
    <h2><span><?= __('Edit post') ?></span></h2>
    <div class="box">
        <form id="edit" method="post" action="<?= Router::pathFor('editPost', ['id' => $id]) ?>">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <input type="hidden" name="topic_subject" value="<?= Url::url_friendly($cur_post['subject']) ?>">
            <div class="inform">
                <fieldset>
                    <legend><?= __('Edit post legend') ?></legend>
                    <input type="hidden" name="form_sent" value="1" />
                    <div class="infldset txtarea">
<?php if ($can_edit_subject): ?>
                        <label class="required"><strong><?= __('Subject') ?> <span><?= __('Required') ?></span></strong><br />
                        <input class="longinput" type="text" name="req_subject" size="80" maxlength="70" tabindex="<?= $cur_index++ ?>" value="<?= Input::post('req_subject', $cur_post['subject']) ?>" required="required" /><br /></label>
<?php endif; ?>
                        <label class="required"><strong><?= __('Message') ?> <span><?= __('Required') ?></span></strong><br />
                        <textarea name="req_message" id="req_message" rows="20" cols="95" tabindex="<?= $cur_index++ ?>" required="required" autofocus><?= Utils::escape(Input::post('req_message') ? $post['message'] : $cur_post['message']) ?></textarea><br /></label>
                        <ul class="bblinks">
                            <li><span><a href="<?= Router::pathFor('help').'#bbcode' ?>" onclick="window.open(this.href); return false;"><?= __('BBCode') ?></a> <?= (ForumSettings::get('p_message_bbcode') == '1') ? __('on') : __('off'); ?></span></li>
                            <li><span><a href="<?= Router::pathFor('help').'#url' ?>" onclick="window.open(this.href); return false;"><?= __('url tag') ?></a> <?= (ForumSettings::get('p_message_bbcode') == '1' && User::can('post.links')) ? __('on') : __('off'); ?></span></li>
                            <li><span><a href="<?= Router::pathFor('help').'#img' ?>" onclick="window.open(this.href); return false;"><?= __('img tag') ?></a> <?= (ForumSettings::get('p_message_bbcode') == '1' && ForumSettings::get('p_message_img_tag') == '1') ? __('on') : __('off'); ?></span></li>
                            <li><span><a href="<?= Router::pathFor('help').'#smilies' ?>" onclick="window.open(this.href); return false;"><?= __('Smilies') ?></a> <?= (ForumSettings::get('show.smilies') == '1') ? __('on') : __('off'); ?></span></li>
                        </ul>
                    </div>
                </fieldset>
            </div>
<?php
if (!empty($checkboxes)):
?>
            <div class="inform">
                <fieldset>
                    <legend><?= __('Options') ?></legend>
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
            <p class="buttons"><input type="submit" name="submit" value="<?= __('Submit') ?>" tabindex="<?= $cur_index++ ?>" accesskey="s" /> <input type="submit" name="preview" value="<?= __('Preview') ?>" tabindex="<?= $cur_index++ ?>" accesskey="p" /> <a href="javascript:history.go(-1)"><?= __('Go back') ?></a></p>
        </form>
    </div>
</div>
<?php
Container::get('hooks')->fire('view.edit.end');
