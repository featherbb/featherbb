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

Container::get('hooks')->fire('view.help.start');
?>

<h2><span><?php _e('BBCode') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><a name="bbcode"></a><?php _e('BBCode info 1') ?></p>
        <p><?php _e('BBCode info 2') ?></p>
    </div>
</div>
<h2><span><?php _e('Text style') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><?php _e('Text style info') ?></p>
        <p><code>[b]<?php _e('Bold text') ?>[/b]</code> <?php _e('produces') ?> <samp><strong><?php _e('Bold text') ?></strong></samp></p>
        <p><code>[u]<?php _e('Underlined text') ?>[/u]</code> <?php _e('produces') ?> <samp><span class="bbu"><?php _e('Underlined text') ?></span></samp></p>
        <p><code>[i]<?php _e('Italic text') ?>[/i]</code> <?php _e('produces') ?> <samp><em><?php _e('Italic text') ?></em></samp></p>
        <p><code>[s]<?php _e('Strike-through text') ?>[/s]</code> <?php _e('produces') ?> <samp><span class="bbs"><?php _e('Strike-through text') ?></span></samp></p>
        <p><code>[del]<?php _e('Deleted text') ?>[/del]</code> <?php _e('produces') ?> <samp><del><?php _e('Deleted text') ?></del></samp></p>
        <p><code>[ins]<?php _e('Inserted text') ?>[/ins]</code> <?php _e('produces') ?> <samp><ins><?php _e('Inserted text') ?></ins></samp></p>
        <p><code>[em]<?php _e('Emphasised text') ?>[/em]</code> <?php _e('produces') ?> <samp><em><?php _e('Emphasised text') ?></em></samp></p>
        <p><code>[color=#FF0000]<?php _e('Red text') ?>[/color]</code> <?php _e('produces') ?> <samp><span style="color: #ff0000"><?php _e('Red text') ?></span></samp></p>
        <p><code>[color=blue]<?php _e('Blue text') ?>[/color]</code> <?php _e('produces') ?> <samp><span style="color: blue"><?php _e('Blue text') ?></span></samp></p>
        <p><code>[h]<?php _e('Heading text') ?>[/h]</code> <?php _e('produces') ?></p> <div class="postmsg"><h5><?php _e('Heading text') ?></h5></div>
    </div>
</div>
<h2><span><?php _e('Links and images') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><?php _e('Links info') ?></p>
        <p><a name="url"></a><code>[url=<?= Utils::escape(Url::base(true).'/') ?>]<?= Utils::escape(Config::get('forum_settings')['o_board_title']) ?>[/url]</code> <?php _e('produces') ?> <samp><a href="<?= Utils::escape(Url::base(true).'/') ?>"><?= Utils::escape(Config::get('forum_settings')['o_board_title']) ?></a></samp></p>
        <p><code>[url]<?= Utils::escape(Url::base(true).'/') ?>[/url]</code> <?php _e('produces') ?> <samp><a href="<?= Utils::escape(Url::base(true).'/') ?>"><?= Utils::escape(Url::base(true).'/') ?></a></samp></p>
        <p><code>[url=/help/]<?php _e('This help page') ?>[/url]</code> <?php _e('produces') ?> <samp><a href="<?= $feather->urlFor('help') ?>"><?php _e('This help page') ?></a></samp></p>
        <p><code>[email]myname@example.com[/email]</code> <?php _e('produces') ?> <samp><a href="mailto:myname@example.com">myname@example.com</a></samp></p>
        <p><code>[email=myname@example.com]<?php _e('My email address') ?>[/email]</code> <?php _e('produces') ?> <samp><a href="mailto:myname@example.com"><?php _e('My email address') ?></a></samp></p>
        <p><code>[topic=1]<?php _e('Test topic') ?>[/topic]</code> <?php _e('produces') ?> <samp><a href="<?= $feather->urlFor('Topic', ['id' => '1']) ?>"><?php _e('Test topic') ?></a></samp></p>
        <p><code>[topic]1[/topic]</code> <?php _e('produces') ?> <samp><a href="<?= $feather->urlFor('Topic', ['id' => '1']) ?>"><?= $feather->urlFor('Topic', ['id' => '1']) ?></a></samp></p>
        <p><code>[post=1]<?php _e('Test post') ?>[/post]</code> <?php _e('produces') ?> <samp><a href="<?= $feather->urlFor('viewPost', ['pid' => '1']).'#1' ?>"><?php _e('Test post') ?></a></samp></p>
        <p><code>[post]1[/post]</code> <?php _e('produces') ?> <samp><a href="<?= $feather->urlFor('viewPost', ['pid' => '1']).'#1' ?>"><?= $feather->urlFor('viewPost', ['pid' => '1']).'#1' ?></a></samp></p>
        <p><code>[forum=1]<?php _e('Test forum') ?>[/forum]</code> <?php _e('produces') ?> <samp><a href="<?= $feather->urlFor('Topic', ['id' => '1']) ?>"><?php _e('Test forum') ?></a></samp></p>
        <p><code>[forum]1[/forum]</code> <?php _e('produces') ?> <samp><a href="<?= $feather->urlFor('Forum', ['id' => '1']) ?>"><?= $feather->urlFor('Forum', ['id' => '1']) ?></a></samp></p>
        <p><code>[user=2]<?php _e('Test user') ?>[/user]</code> <?php _e('produces') ?> <samp><a href="<?= $feather->urlFor('userProfile', ['id' => '2']) ?>"><?php _e('Test user') ?></a></samp></p>
        <p><code>[user]2[/user]</code> <?php _e('produces') ?> <samp><a href="<?= $feather->urlFor('userProfile', ['id' => '2']) ?>"><?= $feather->urlFor('userProfile', ['id' => '2']) ?></a></samp></p>
    </div>
    <div class="inbox">
        <p><a name="img"></a><?php _e('Images info') ?></p>
        dede
        <p><code>[img=<?php _e('FeatherBB bbcode test') ?>]<?= Utils::escape(Url::base(true)) ?>/style/img/logo.png[/img]</code> <?php _e('produces') ?> <samp><img style="height: 21px" src="<?= Utils::escape(Url::base(true)) ?>/style/img/logo.png" alt="<?php _e('FeatherBB bbcode test') ?>" /></samp></p>
    </div>
</div>
<h2><span><?php _e('Quotes') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><?php _e('Quotes info') ?></p>
        <p><code>[quote=James]<?php _e('Quote text') ?>[/quote]</code></p>
        <p><?php _e('produces quote box') ?></p>
        <div class="postmsg">
            <div class="quotebox"><cite>James <?php _e('wrote') ?></cite><blockquote><div><p><?php _e('Quote text') ?></p></div></blockquote></div>
        </div>
        <p><?php _e('Quotes info 2') ?></p>
        <p><code>[quote]<?php _e('Quote text') ?>[/quote]</code></p>
        <p><?php _e('produces quote box') ?></p>
        <div class="postmsg">
            <div class="quotebox"><blockquote><div><p><?php _e('Quote text') ?></p></div></blockquote></div>
        </div>
        <p><?php _e('quote note') ?></p>
    </div>
</div>
<h2><span><?php _e('Code') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><?php _e('Code info') ?></p>
        <p><code>[code]<?php _e('Code text') ?>[/code]</code></p>
        <p><?php _e('produces code box') ?></p>
        <div class="postmsg">
            <div class="codebox"><pre><code><?php _e('Code text') ?></code></pre></div>
        </div>
    </div>
</div>
<h2><span><?php _e('Lists') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><a name="lists"></a><?php _e('List info') ?></p>
        <p><code>[list][*]<?php _e('List text 1') ?>[/*][*]<?php _e('List text 2') ?>[/*][*]<?php _e('List text 3') ?>[/*][/list]</code>
        <br /><span><?php _e('produces list') ?></span></p>
        <div class="postmsg">
            <ul><li><p><?php _e('List text 1') ?></p></li><li><p><?php _e('List text 2') ?></p></li><li><p><?php _e('List text 3') ?></p></li></ul>
        </div>
        <p><code>[list=1][*]<?php _e('List text 1') ?>[/*][*]<?php _e('List text 2') ?>[/*][*]<?php _e('List text 3') ?>[/*][/list]</code>
        <br /><span><?php _e('produces decimal list') ?></span></p>
        <div class="postmsg">
            <ol class="decimal"><li><p><?php _e('List text 1') ?></p></li><li><p><?php _e('List text 2') ?></p></li><li><p><?php _e('List text 3') ?></p></li></ol>
        </div>
        <p><code>[list=a][*]<?php _e('List text 1') ?>[/*][*]<?php _e('List text 2') ?>[/*][*]<?php _e('List text 3') ?>[/*][/list]</code>
        <br /><span><?php _e('produces alpha list') ?></span></p>
        <div class="postmsg">
            <ol class="alpha"><li><p><?php _e('List text 1') ?></p></li><li><p><?php _e('List text 2') ?></p></li><li><p><?php _e('List text 3') ?></p></li></ol>
        </div>
    </div>
</div>
<h2><span><?php _e('Nested tags') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><?php _e('Nested tags info') ?></p>
        <p><code>[b][u]<?php _e('Bold, underlined text') ?>[/u][/b]</code> <?php _e('produces') ?> <samp><strong><span class="bbu"><?php _e('Bold, underlined text') ?></span></strong></samp></p>
    </div>
</div>
<h2><span><?php _e('Smilies') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><a name="smilies"></a><?php _e('Smilies info') ?></p>
<?php

// Display the smiley set
$smiley_groups = array();

foreach ($feather->parser->pd['smilies'] as $smiley_text => $smiley_data) {
    $smiley_groups[$smiley_data['file']][] = $smiley_text;
}

foreach ($smiley_groups as $smiley_img => $smiley_texts) {
    echo "\t\t<p><code>". implode('</code> ' .__('and'). ' <code>', $smiley_texts).'</code> <span>' .__('produces'). '</span> <samp>'.$feather->parser->pd['smilies'][$smiley_texts[0]]['html'] .'</samp></p>'."\n";
}

?>
    </div>
</div>
<?php
Container::get('hooks')->fire('view.header.end');
