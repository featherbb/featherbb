<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

?>
<div id="profile" class="block2col">
	<div class="blockmenu">
		<h2><span><?php echo $lang_profile['Profile menu'] ?></span></h2>
		<div class="box">
			<div class="inbox">
				<ul>
					<li<?php if ($page == 'essentials') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('user/'.$id.'/section/essentials/') ?>"><?php echo $lang_profile['Section essentials'] ?></a></li>
					<li<?php if ($page == 'personal') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('user/'.$id.'/section/personal/') ?>"><?php echo $lang_profile['Section personal'] ?></a></li>
					<li<?php if ($page == 'messaging') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('user/'.$id.'/section/messaging/') ?>"><?php echo $lang_profile['Section messaging'] ?></a></li>
<?php if ($feather_config['o_avatars'] == '1' || $feather_config['o_signatures'] == '1'): ?>					<li<?php if ($page == 'personality') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('user/'.$id.'/section/personality/') ?>"><?php echo $lang_profile['Section personality'] ?></a></li>
<?php endif;
    ?>					<li<?php if ($page == 'display') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('user/'.$id.'/section/display/') ?>"><?php echo $lang_profile['Section display'] ?></a></li>
					<li<?php if ($page == 'privacy') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('user/'.$id.'/section/privacy/') ?>"><?php echo $lang_profile['Section privacy'] ?></a></li>
<?php if ($feather->user->g_id == FEATHER_ADMIN || ($feather->user->g_moderator == '1' && $feather->user->g_mod_ban_users == '1')): ?>					<li<?php if ($page == 'admin') {
    echo ' class="isactive"';
}
    ?>><a href="<?php echo get_link('user/'.$id.'/section/admin/') ?>"><?php echo $lang_profile['Section admin'] ?></a></li>
<?php endif;
    ?>				</ul>
			</div>
		</div>
	</div>
