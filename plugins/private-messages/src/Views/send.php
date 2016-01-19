<?php

/**
* Copyright (C) 2015-2016 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
*/

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

if (isset($parsed_message)) : ?>
        <div id="postpreview" class="blockpost">
            <h2><span>Preview</span></h2>
            <div class="box">
                <div class="inbox">
                    <div class="postbody">
                        <div class="postright">
                            <div class="postmsg">
                                <?= $parsed_message ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php endif; ?>
        <div id="postform" class="blockform">
            <h2><span><?= __('Send', 'private_messages') ?></span></h2>
            <div class="box">
                <form id="post" method="post" action="" onsubmit="return process_form(this)">
                    <div class="inform">
                        <fieldset>
                            <legend><?= __('Write message legend') ?></legend>
                            <div class="infldset txtarea">
                                <?php if ($csrf_key) echo "\t\t\t\t\t\t\t".'<input type="hidden" name="'.$csrf_key.'" value="'.$csrf_token.'"/>'."\n" ?>
                                <label class="required"><strong>Send to <span><?= __('Required') ?></span></strong><br /></label>
                                <input type="text" name="username" placeholder="Username" <?= (isset($username) ? 'value="'.$username.'"' : '')?> size="25" tabindex="1" required autofocus/><br />
                                <div class="clearer"></div>
                                <label class="required"><strong>Subject <span><?= __('Required') ?></span></strong><br /></label>
                                <input class="longinput" type="text" name="subject" placeholder="Subject" <?= (isset($subject) ? 'value="'.$subject.'"' : '')?> size="80" maxlength="70" tabindex="2" required/><br />
                                <label class="required"><strong><?php _e('Message') ?> <span><?= __('Required') ?></span></strong><br /></label>
                                <textarea name="req_message" id="req_message" rows="20" cols="95" tabindex="2" required><?= (isset($message) ? $message : '')?></textarea><br />
                                <ul class="bblinks">
                                    <li><span><a href="<?= $feather->urlFor('help').'#bbcode' ?>" onclick="window.open(this.href); return false;"><?php _e('BBCode') ?>ok</a> <?php echo($feather->forum_settings['p_message_bbcode'] == '1') ? __('on') : __('off'); ?></span></li>
                                    <li><span><a href="<?= $feather->urlFor('help').'#url' ?>" onclick="window.open (this.href); return false;"><?php _e('url tag') ?></a> <?php echo($feather->forum_settings['p_message_bbcode'] == '1' && $feather->user->g_post_links == '1') ? __('on') : __('off'); ?></span></li>
                                    <li><span><a href="<?= $feather->urlFor('help').'#img' ?>" onclick="window.open(this.href); return false;"><?php _e('img tag') ?></a> <?php echo($feather->forum_settings['p_message_bbcode'] == '1' && $feather->forum_settings['p_message_img_tag'] == '1') ? __('on') : __('off'); ?></span></li>
                                    <li><span><a href="<?= $feather->urlFor('help').'#smilies' ?>" onclick="window.open(this.href); return false;"><?php _e('Smilies') ?></a> <?php echo($feather->forum_settings['o_smilies'] == '1') ? __('on') : __('off'); ?></span></li>
                                </ul>
                            </div>
                        </fieldset>
                    </div>
                    <div class="inform">
                        <fieldset>
                            <legend><?php _e('Options') ?></legend>
                            <div class="infldset">
                                <div class="rbox">
                                    <label><input type="checkbox" name="smilies" value="1" tabindex="3" /><?= __('Hide smilies') ?><br /></label>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <p class="buttons"><input type="submit" name="submit" value="<?php _e('Submit') ?>" tabindex="4" accesskey="s" /> <input type="submit" name="preview" value="<?php _e('Preview') ?>" tabindex="5" accesskey="p" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
                </form>
            </div>
        </div>
