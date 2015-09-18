<?php

/**
* Copyright (C) 2015 FeatherBB
* based on code by (C) 2008-2015 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher.
*/
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

?>

    <div class="linkst">
    	<div class="inbox crumbsplus">
    		<ul class="crumbs">
    			<li><a href="#">Index</a></li>
    			<li><span>»&#160;</span><a href="#"><?= _e('PMs', 'private_messages') ?></a></li>
    			<li><span>»&#160;</span><strong>Reply to conversation</strong></li>
                <li><span>»&#160;</span><strong><?= (isset($conv['subject']) ? $conv['subject'] : '')?></strong></li>
    		</ul>
    		<div class="pagepost"></div>
    		<div class="clearer"></div>
    	</div>
    </div>
<?php if (!empty($msg_data)) {
    $count = 1;
    foreach ($msg_data as $msg) { ?>
    <div class="block">
        <div id="p<?=$msg['id']?>" class="blockpost<?=($count % 2 == 0) ? ' roweven' : ' rowodd'.(($count == 1) ? ' firstpost' : '').(($count == 1) ? ' blockpost1' : '')?>">
            <h2><span><span class="conr">#<?=$count?></span> <a href="#"><?= $feather->utils->format_time($msg['sent'])?></a></span></h2>
            <div class="box">
                <div class="inbox">
                    <div class="postbody">
                        <div class="postleft">
                            <dl>
                                <dt><strong><a href="<?= $feather->urlFor('userProfile', ['id' => $msg['poster_id']]) ?>"><?= $msg['poster']?></strong></a></dt>
                                <!-- <dd class="usertitle"><strong>{user_title}</strong></dd>
                                {user_avatar}{group_image}{user_info} -->
                            </dl>
                        </div>
                        <div class="postright">
                            <div class="postmsg">
                                <?= Utils::escape($msg['message']) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="inbox">
                    <div class="postfoot clearb">
                        <!-- <div class="postfootleft"></div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php ++$count; }
} ?>
	<div id="postform" class="blockform">
		<h2><span>Reply to a conversation</span></h2>
		<div class="box">
			<form id="post" method="post" action="" onsubmit="return process_form(this)">
				<div class="inform">
					<fieldset>
						<legend>Compose your message</legend>
						<div class="infldset txtarea">
<?php if ($csrf_key) echo "\t\t\t\t\t\t\t".'<input type="hidden" name="'.$csrf_key.'" value="'.$csrf_token.'"/>'."\n" ?>
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
								<label><input type="checkbox" name="smilies" value="1" tabindex="3" />Hide smilies<br /></label>
							</div>
						</div>
					</fieldset>
				</div>
                <p class="buttons"><input type="submit" name="submit" value="<?php _e('Submit') ?>" tabindex="4" accesskey="s" /> <input type="submit" name="preview" value="<?php _e('Preview') ?>" tabindex="5" accesskey="p" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
			</form>
		</div>
	</div>
</div>
