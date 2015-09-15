<?php

/**
 * Copyright (C) 2015 FeatherBB
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

?>

        <div class="linkst">
        	<div class="inbox">
                <ul class="crumbs">
                    <li><a href="<?= Url::base() ?>"><?php _e('Index') ?></a></li>
                    <li><span>»&#160;</span><a href="<?= $feather->urlFor('Conversations') ?>"><?= _e('PMs', 'private_messages') ?></a></li>
                    <li><span>»&#160;</span><a href="<?= $feather->urlFor('Conversations', ['id' => $inbox->id]) ?>"><?= Utils::escape($inbox->name) ?></a></li>
                    <li><span>»&#160;</span><strong><?php _e('My conversations', 'private_messages') ?></strong></li>
                    <li class="postlink actions conr"><span><a href="<?= $feather->urlFor('newConversation') ?>"><?php _e('Send message', 'private_messages') ?></a></span></li>
                </ul>

                <div class="pagepost"></div>
                <div class="clearer"></div>
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

        <div class="block2col">
        	<div class="blockmenu">
        		<h2><span>Folders</span></h2>
        		<div class="box">
        			<div class="inbox">
        				<ul>

        					<li><a href="http://localhost/panther/pms_inbox.php?id=1">New (1)</a></li>
        					<li class="isactive"><a href="http://localhost/panther/pms_inbox.php?id=2">Inbox (1)</a></li>
        					<li><a href="http://localhost/panther/pms_inbox.php?id=3">Archived</a></li>
        					<li><a href="http://localhost/panther/pms_inbox.php?id=4">Test</a></li>

        				</ul>
        			</div>
        		</div>
        		<br />
        		<h2><span>Storage</span></h2>
        		<div class="box">
        			<div class="inbox">
        				<ul>
        					<li>Inbox: 0% full</li>
        					<li><div id="pm_bar_style" style="width:0px;"></div></li>
        					<li>Quota: 0 / &infin;</li>
        				</ul>
        			</div>
        		</div>
        		<br />
        		<h2><span>Options</span></h2>
        		<div class="box">
        			<div class="inbox">
        				<ul>
        					<li><a href="http://localhost/panther/pms_misc.php?action=blocked">Blocked Users</a></li>
        					<li><a href="http://localhost/panther/pms_misc.php?action=folders">My Folders</a></li>
        				</ul>
        			</div>
        		</div>
        	</div>
        	<div class="block">
        		<form method="post" action="http://localhost/panther/pms_inbox.php" id="topics" name="posttopic">
        		<input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
        		<input type="hidden" name="p" value="1" />
        		<div id="vf" class="blocktable">
        			<div class="box">
        				<div class="inbox">
        					<table>
        					<thead>
        						<tr>
        							<th class="tcl" scope="col">Messages</th>
        							<th class="tc2" scope="col">Sender</th>
        							<th class="tc2" scope="col">Receiver</th>
        							<th class="tc2" scope="col">Replies</th>
        							<th class="tcr" scope="col">Last Post</th>
        							<th class="tcmod" scope="col"><input type="checkbox" onclick="javascript:select_checkboxes('topics', this, '')" /></th>
        						</tr>
        					</thead>
        					<tbody>
        						<tr class="rowodd inew">
        							<td class="tcl">
        								<div class="icon icon-new"><div class="nosize">1</div></div>
        								<div class="tclcon">
        									<div>
        										 <strong><a href="http://localhost/panther/pms_view.php?tid=1">test</a></strong> <span class="newtext">[ <a href="http://localhost/panther/pms_view.php?tid=1&action=new" title="Go to the first new post in this topic.">New posts</a> ]</span>
        									</div>
        								</div>
        							</td>
        							<td class="tcl"><a href="http://localhost/panther/profile.php?id=2"><span class="gid1">hooger</span></a></td>
        							<td class="tc2"><a href="http://localhost/panther/profile.php?id=2"><span class="gid1">hooger</span></a></td>
        							<td class="tc2">1</td>
        							<td class="tcr"><span class="byuser_avatar"><img src="https://www.gravatar.com/avatar.php?gravatar_id=f52f8e0527efe38014d71baa938ab708&amp;size=32" width="32" height="32" alt="" /></span><a href="http://localhost/panther/pms_view.php?pid=0#p0">Never</a> <span class="byuser">by <a href="http://localhost/panther/profile.php?id=2"><span class="gid1">hooger</span></a></span></td>
        							<td class="tcmod"><input type="checkbox" name="topics[]" value="1" /></td>
        						</tr>
        					</tbody>
        					</table>
        				</div>
        			</div>
        		</div>
        		<div class="pagepost">
        			<p class="pagelink conl"><span class="pages-label">Pages: </span><strong class="item1">1</strong></p>
        			<p class="postlink conr"><input type="submit" name="move" value="Move" />&#160;<input type="submit" name="delete" value="Delete" /></p>
        		</div>
        		</form>
        	</div>
        	<div class="clearer"></div>
        </div>



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
<?php if ($can_edit_subject): ?>						<label class="required"><strong><?php _e('Subject') ?> <span><?php _e('Required') ?></span></strong><br />
						<input class="longinput" type="text" name="req_subject" size="80" maxlength="70" tabindex="<?= $cur_index++ ?>" value="<?= Utils::escape($feather->request->post('req_subject') ? $feather->request->post('req_subject') : $cur_post['subject']) ?>" /><br /></label>
<?php endif; ?>						<label class="required"><strong><?php _e('Message') ?> <span><?php _e('Required') ?></span></strong><br />
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
