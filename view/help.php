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

<h2><span><?php echo __('BBCode') ?></span></h2>
<div class="box">
	<div class="inbox">
		<p><a name="bbcode"></a><?php echo __('BBCode info 1') ?></p>
		<p><?php echo __('BBCode info 2') ?></p>
	</div>
</div>
<h2><span><?php echo __('Text style') ?></span></h2>
<div class="box">
	<div class="inbox">
		<p><?php echo __('Text style info') ?></p>
		<p><code>[b]<?php echo __('Bold text') ?>[/b]</code> <?php echo __('produces') ?> <samp><strong><?php echo __('Bold text') ?></strong></samp></p>
		<p><code>[u]<?php echo __('Underlined text') ?>[/u]</code> <?php echo __('produces') ?> <samp><span class="bbu"><?php echo __('Underlined text') ?></span></samp></p>
		<p><code>[i]<?php echo __('Italic text') ?>[/i]</code> <?php echo __('produces') ?> <samp><em><?php echo __('Italic text') ?></em></samp></p>
		<p><code>[s]<?php echo __('Strike-through text') ?>[/s]</code> <?php echo __('produces') ?> <samp><span class="bbs"><?php echo __('Strike-through text') ?></span></samp></p>
		<p><code>[del]<?php echo __('Deleted text') ?>[/del]</code> <?php echo __('produces') ?> <samp><del><?php echo __('Deleted text') ?></del></samp></p>
		<p><code>[ins]<?php echo __('Inserted text') ?>[/ins]</code> <?php echo __('produces') ?> <samp><ins><?php echo __('Inserted text') ?></ins></samp></p>
		<p><code>[em]<?php echo __('Emphasised text') ?>[/em]</code> <?php echo __('produces') ?> <samp><em><?php echo __('Emphasised text') ?></em></samp></p>
		<p><code>[color=#FF0000]<?php echo __('Red text') ?>[/color]</code> <?php echo __('produces') ?> <samp><span style="color: #ff0000"><?php echo __('Red text') ?></span></samp></p>
		<p><code>[color=blue]<?php echo __('Blue text') ?>[/color]</code> <?php echo __('produces') ?> <samp><span style="color: blue"><?php echo __('Blue text') ?></span></samp></p>
		<p><code>[h]<?php echo __('Heading text') ?>[/h]</code> <?php echo __('produces') ?></p> <div class="postmsg"><h5><?php echo __('Heading text') ?></h5></div>
	</div>
</div>
<h2><span><?php echo __('Links and images') ?></span></h2>
<div class="box">
	<div class="inbox">
		<p><?php echo __('Links info') ?></p>
		<p><a name="url"></a><code>[url=<?php echo feather_escape(get_base_url(true).'/') ?>]<?php echo feather_escape($feather_config['o_board_title']) ?>[/url]</code> <?php echo __('produces') ?> <samp><a href="<?php echo feather_escape(get_base_url(true).'/') ?>"><?php echo feather_escape($feather_config['o_board_title']) ?></a></samp></p>
		<p><code>[url]<?php echo feather_escape(get_base_url(true).'/') ?>[/url]</code> <?php echo __('produces') ?> <samp><a href="<?php echo feather_escape(get_base_url(true).'/') ?>"><?php echo feather_escape(get_base_url(true).'/') ?></a></samp></p>
		<p><code>[url=/help/]<?php echo __('This help page') ?>[/url]</code> <?php echo __('produces') ?> <samp><a href="<?php echo get_link('/help') ?>"><?php echo __('This help page') ?></a></samp></p>
		<p><code>[email]myname@example.com[/email]</code> <?php echo __('produces') ?> <samp><a href="mailto:myname@example.com">myname@example.com</a></samp></p>
		<p><code>[email=myname@example.com]<?php echo __('My email address') ?>[/email]</code> <?php echo __('produces') ?> <samp><a href="mailto:myname@example.com"><?php echo __('My email address') ?></a></samp></p>
		<p><code>[topic=1]<?php echo __('Test topic') ?>[/topic]</code> <?php echo __('produces') ?> <samp><a href="<?php echo get_link('topic/1/') ?>"><?php echo __('Test topic') ?></a></samp></p>
		<p><code>[topic]1[/topic]</code> <?php echo __('produces') ?> <samp><a href="<?php echo get_link('topic/1/') ?>"><?php echo get_link('topic/1/') ?></a></samp></p>
		<p><code>[post=1]<?php echo __('Test post') ?>[/post]</code> <?php echo __('produces') ?> <samp><a href="<?php echo get_link('post/1/#p1') ?>"><?php echo __('Test post') ?></a></samp></p>
		<p><code>[post]1[/post]</code> <?php echo __('produces') ?> <samp><a href="<?php echo get_link('post/1/#p1') ?>"><?php echo get_link('post/1/#p1') ?></a></samp></p>
		<p><code>[forum=1]<?php echo __('Test forum') ?>[/forum]</code> <?php echo __('produces') ?> <samp><a href="<?php echo get_link('topic/1/') ?>"><?php echo __('Test forum') ?></a></samp></p>
		<p><code>[forum]1[/forum]</code> <?php echo __('produces') ?> <samp><a href="<?php echo get_link('forum/1/') ?>"><?php echo get_link('forum/1/') ?></a></samp></p>
		<p><code>[user=2]<?php echo __('Test user') ?>[/user]</code> <?php echo __('produces') ?> <samp><a href="<?php echo get_link('user/2/') ?>"><?php echo __('Test user') ?></a></samp></p>
		<p><code>[user]2[/user]</code> <?php echo __('produces') ?> <samp><a href="<?php echo get_link('user/2/') ?>"><?php echo get_link('user/2/') ?></a></samp></p>
	</div>
	<div class="inbox">
		<p><a name="img"></a><?php echo __('Images info') ?></p>
		<p><code>[img=<?php echo __('FluxBB bbcode test') ?>]<?php echo feather_escape(get_base_url(true)) ?>/img/test.png[/img]</code> <?php echo __('produces') ?> <samp><img style="height: 21px" src="<?php echo feather_escape(get_base_url(true)) ?>/img/test.png" alt="<?php echo __('FluxBB bbcode test') ?>" /></samp></p>
	</div>
</div>
<h2><span><?php echo __('Quotes') ?></span></h2>
<div class="box">
	<div class="inbox">
		<p><?php echo __('Quotes info') ?></p>
		<p><code>[quote=James]<?php echo __('Quote text') ?>[/quote]</code></p>
		<p><?php echo __('produces quote box') ?></p>
		<div class="postmsg">
			<div class="quotebox"><cite>James <?php echo __('wrote') ?></cite><blockquote><div><p><?php echo __('Quote text') ?></p></div></blockquote></div>
		</div>
		<p><?php echo __('Quotes info 2') ?></p>
		<p><code>[quote]<?php echo __('Quote text') ?>[/quote]</code></p>
		<p><?php echo __('produces quote box') ?></p>
		<div class="postmsg">
			<div class="quotebox"><blockquote><div><p><?php echo __('Quote text') ?></p></div></blockquote></div>
		</div>
		<p><?php echo __('quote note') ?></p>
	</div>
</div>
<h2><span><?php echo __('Code') ?></span></h2>
<div class="box">
	<div class="inbox">
		<p><?php echo __('Code info') ?></p>
		<p><code>[code]<?php echo __('Code text') ?>[/code]</code></p>
		<p><?php echo __('produces code box') ?></p>
		<div class="postmsg">
			<div class="codebox"><pre><code><?php echo __('Code text') ?></code></pre></div>
		</div>
	</div>
</div>
<h2><span><?php echo __('Lists') ?></span></h2>
<div class="box">
	<div class="inbox">
		<p><a name="lists"></a><?php echo __('List info') ?></p>
		<p><code>[list][*]<?php echo __('List text 1') ?>[/*][*]<?php echo __('List text 2') ?>[/*][*]<?php echo __('List text 3') ?>[/*][/list]</code>
		<br /><span><?php echo __('produces list') ?></span></p>
		<div class="postmsg">
			<ul><li><p><?php echo __('List text 1') ?></p></li><li><p><?php echo __('List text 2') ?></p></li><li><p><?php echo __('List text 3') ?></p></li></ul>
		</div>
		<p><code>[list=1][*]<?php echo __('List text 1') ?>[/*][*]<?php echo __('List text 2') ?>[/*][*]<?php echo __('List text 3') ?>[/*][/list]</code>
		<br /><span><?php echo __('produces decimal list') ?></span></p>
		<div class="postmsg">
			<ol class="decimal"><li><p><?php echo __('List text 1') ?></p></li><li><p><?php echo __('List text 2') ?></p></li><li><p><?php echo __('List text 3') ?></p></li></ol>
		</div>
		<p><code>[list=a][*]<?php echo __('List text 1') ?>[/*][*]<?php echo __('List text 2') ?>[/*][*]<?php echo __('List text 3') ?>[/*][/list]</code>
		<br /><span><?php echo __('produces alpha list') ?></span></p>
		<div class="postmsg">
			<ol class="alpha"><li><p><?php echo __('List text 1') ?></p></li><li><p><?php echo __('List text 2') ?></p></li><li><p><?php echo __('List text 3') ?></p></li></ol>
		</div>
	</div>
</div>
<h2><span><?php echo __('Nested tags') ?></span></h2>
<div class="box">
	<div class="inbox">
		<p><?php echo __('Nested tags info') ?></p>
		<p><code>[b][u]<?php echo __('Bold, underlined text') ?>[/u][/b]</code> <?php echo __('produces') ?> <samp><strong><span class="bbu"><?php echo __('Bold, underlined text') ?></span></strong></samp></p>
	</div>
</div>
<h2><span><?php echo __('Smilies') ?></span></h2>
<div class="box">
	<div class="inbox">
		<p><a name="smilies"></a><?php echo __('Smilies info') ?></p>
<?php

// Display the smiley set
require FEATHER_ROOT.'include/parser.php';

$smiley_groups = array();

foreach ($pd['smilies'] as $smiley_text => $smiley_data) {
    $smiley_groups[$smiley_data['file']][] = $smiley_text;
}

foreach ($smiley_groups as $smiley_img => $smiley_texts) {
    echo "\t\t<p><code>". implode('</code> ' .__('and'). ' <code>', $smiley_texts).'</code> <span>' .__('produces'). '</span> <samp>'.$pd['smilies'][$smiley_texts[0]]['html'] .'</samp></p>'."\n";
}

?>
	</div>
</div>