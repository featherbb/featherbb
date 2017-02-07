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

<h2><span><?= __('BBCode') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><a name="bbcode"></a><?= __('BBCode info 1') ?></p>
        <p><?= __('BBCode info 2') ?></p>
    </div>
</div>
<h2><span><?= __('Text style') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><?= __('Text style info') ?></p>
        <p><code>[b]<?= __('Bold text') ?>[/b]</code> <?= __('produces') ?> <samp><strong><?= __('Bold text') ?></strong></samp></p>
        <p><code>[u]<?= __('Underlined text') ?>[/u]</code> <?= __('produces') ?> <samp><span class="bbu"><?= __('Underlined text') ?></span></samp></p>
        <p><code>[i]<?= __('Italic text') ?>[/i]</code> <?= __('produces') ?> <samp><em><?= __('Italic text') ?></em></samp></p>
        <p><code>[s]<?= __('Strike-through text') ?>[/s]</code> <?= __('produces') ?> <samp><span class="bbs"><?= __('Strike-through text') ?></span></samp></p>
        <p><code>[del]<?= __('Deleted text') ?>[/del]</code> <?= __('produces') ?> <samp><del><?= __('Deleted text') ?></del></samp></p>
        <p><code>[ins]<?= __('Inserted text') ?>[/ins]</code> <?= __('produces') ?> <samp><ins><?= __('Inserted text') ?></ins></samp></p>
        <p><code>[em]<?= __('Emphasised text') ?>[/em]</code> <?= __('produces') ?> <samp><em><?= __('Emphasised text') ?></em></samp></p>
        <p><code>[color=#FF0000]<?= __('Red text') ?>[/color]</code> <?= __('produces') ?> <samp><span style="color: #ff0000"><?= __('Red text') ?></span></samp></p>
        <p><code>[color=blue]<?= __('Blue text') ?>[/color]</code> <?= __('produces') ?> <samp><span style="color: blue"><?= __('Blue text') ?></span></samp></p>
        <p><code>[h]<?= __('Heading text') ?>[/h]</code> <?= __('produces') ?></p> <div class="postmsg"><h5><?= __('Heading text') ?></h5></div>
    </div>
</div>
<h2><span><?= __('Links and images') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><?= __('Links info') ?></p>
        <p><a name="url"></a><code>[url=<?= Utils::escape(Url::base(true).'/') ?>]<?= Utils::escape(ForumSettings::get('o_board_title')) ?>[/url]</code> <?= __('produces') ?> <samp><a href="<?= Utils::escape(Url::base(true).'/') ?>"><?= Utils::escape(ForumSettings::get('o_board_title')) ?></a></samp></p>
        <p><code>[url]<?= Utils::escape(Url::base(true).'/') ?>[/url]</code> <?= __('produces') ?> <samp><a href="<?= Utils::escape(Url::base(true).'/') ?>"><?= Utils::escape(Url::base(true).'/') ?></a></samp></p>
        <p><code>[url=/help/]<?= __('This help page') ?>[/url]</code> <?= __('produces') ?> <samp><a href="<?= Router::pathFor('help') ?>"><?= __('This help page') ?></a></samp></p>
        <p><code>[email]myname@example.com[/email]</code> <?= __('produces') ?> <samp><a href="mailto:myname@example.com">myname@example.com</a></samp></p>
        <p><code>[email=myname@example.com]<?= __('My email address') ?>[/email]</code> <?= __('produces') ?> <samp><a href="mailto:myname@example.com"><?= __('My email address') ?></a></samp></p>
        <p><code>[topic=1]<?= __('Test topic') ?>[/topic]</code> <?= __('produces') ?> <samp><a href="<?= Router::pathFor('Topic', ['id' => '1', 'name' => 'test-name']) ?>"><?= __('Test topic') ?></a></samp></p>
        <p><code>[topic]1[/topic]</code> <?= __('produces') ?> <samp><a href="<?= Router::pathFor('Topic', ['id' => '1', 'name' => 'test-name']) ?>"><?= Router::pathFor('Topic', ['id' => '1', 'name' => 'test-name']) ?></a></samp></p>
        <p><code>[post=1]<?= __('Test post') ?>[/post]</code> <?= __('produces') ?> <samp><a href="<?= Router::pathFor('viewPost', ['id' => '1', 'name' => 'test-topic', 'pid' => '1']).'#1' ?>"><?= __('Test post') ?></a></samp></p>
        <p><code>[post]1[/post]</code> <?= __('produces') ?> <samp><a href="<?= Router::pathFor('viewPost', ['id' => '1', 'name' => 'test-topic', 'pid' => '1']).'#1' ?>"><?= Router::pathFor('viewPost', ['id' => '1', 'name' => 'test-topic', 'pid' => '1']).'#1' ?></a></samp></p>
        <p><code>[forum=1]<?= __('Test forum') ?>[/forum]</code> <?= __('produces') ?> <samp><a href="<?= Router::pathFor('Topic', ['id' => '1', 'name' => 'test-name']) ?>"><?= __('Test forum') ?></a></samp></p>
        <p><code>[forum]1[/forum]</code> <?= __('produces') ?> <samp><a href="<?= Router::pathFor('Forum', ['id' => '1', 'name' => 'test-name']) ?>"><?= Router::pathFor('Forum', ['id' => '1', 'name' => 'test-name']) ?></a></samp></p>
        <p><code>[user=2]<?= __('Test user') ?>[/user]</code> <?= __('produces') ?> <samp><a href="<?= Router::pathFor('userProfile', ['id' => '2']) ?>"><?= __('Test user') ?></a></samp></p>
        <p><code>[user]2[/user]</code> <?= __('produces') ?> <samp><a href="<?= Router::pathFor('userProfile', ['id' => '2']) ?>"><?= Router::pathFor('userProfile', ['id' => '2']) ?></a></samp></p>
    </div>
    <div class="inbox">
        <p><a name="img"></a><?= __('Images info') ?></p>
        dede
        <p><code>[img=<?= __('FeatherBB bbcode test') ?>]<?= Utils::escape(Url::base(true)) ?>/style/img/logo.png[/img]</code> <?= __('produces') ?> <samp><img style="height: 21px" src="<?= Utils::escape(Url::base(true)) ?>/style/img/logo.png" alt="<?= __('FeatherBB bbcode test') ?>" /></samp></p>
    </div>
</div>
<h2><span><?= __('Quotes') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><?= __('Quotes info') ?></p>
        <p><code>[quote=James]<?= __('Quote text') ?>[/quote]</code></p>
        <p><?= __('produces quote box') ?></p>
        <div class="postmsg">
            <div class="quotebox"><cite>James <?= __('wrote') ?></cite><blockquote><div><p><?= __('Quote text') ?></p></div></blockquote></div>
        </div>
        <p><?= __('Quotes info 2') ?></p>
        <p><code>[quote]<?= __('Quote text') ?>[/quote]</code></p>
        <p><?= __('produces quote box') ?></p>
        <div class="postmsg">
            <div class="quotebox"><blockquote><div><p><?= __('Quote text') ?></p></div></blockquote></div>
        </div>
        <p><?= __('quote note') ?></p>
    </div>
</div>
<h2><span><?= __('Code') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><?= __('Code info') ?></p>
        <p><code>[code]<?= __('Code text') ?>[/code]</code></p>
        <p><?= __('produces code box') ?></p>
        <div class="postmsg">
            <div class="codebox"><pre><code><?= __('Code text') ?></code></pre></div>
        </div>
    </div>
</div>
<h2><span><?= __('Lists') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><a name="lists"></a><?= __('List info') ?></p>
        <p><code>[list][*]<?= __('List text 1') ?>[/*][*]<?= __('List text 2') ?>[/*][*]<?= __('List text 3') ?>[/*][/list]</code>
        <br /><span><?= __('produces list') ?></span></p>
        <div class="postmsg">
            <ul><li><p><?= __('List text 1') ?></p></li><li><p><?= __('List text 2') ?></p></li><li><p><?= __('List text 3') ?></p></li></ul>
        </div>
        <p><code>[list=1][*]<?= __('List text 1') ?>[/*][*]<?= __('List text 2') ?>[/*][*]<?= __('List text 3') ?>[/*][/list]</code>
        <br /><span><?= __('produces decimal list') ?></span></p>
        <div class="postmsg">
            <ol class="decimal"><li><p><?= __('List text 1') ?></p></li><li><p><?= __('List text 2') ?></p></li><li><p><?= __('List text 3') ?></p></li></ol>
        </div>
        <p><code>[list=a][*]<?= __('List text 1') ?>[/*][*]<?= __('List text 2') ?>[/*][*]<?= __('List text 3') ?>[/*][/list]</code>
        <br /><span><?= __('produces alpha list') ?></span></p>
        <div class="postmsg">
            <ol class="alpha"><li><p><?= __('List text 1') ?></p></li><li><p><?= __('List text 2') ?></p></li><li><p><?= __('List text 3') ?></p></li></ol>
        </div>
    </div>
</div>
<h2><span><?= __('Nested tags') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><?= __('Nested tags info') ?></p>
        <p><code>[b][u]<?= __('Bold, underlined text') ?>[/u][/b]</code> <?= __('produces') ?> <samp><strong><span class="bbu"><?= __('Bold, underlined text') ?></span></strong></samp></p>
    </div>
</div>
<h2><span><?= __('Smilies') ?></span></h2>
<div class="box">
    <div class="inbox">
        <p><a name="smilies"></a><?= __('Smilies info') ?></p>
<?php

// Display the smiley set
$smiley_groups = [];

foreach (Container::get('parser')->pd['smilies'] as $smiley_text => $smiley_data) {
    $smiley_groups[$smiley_data['file']][] = $smiley_text;
}

foreach ($smiley_groups as $smiley_img => $smiley_texts) {
    echo "\t\t<p><code>". implode('</code> ' .__('and'). ' <code>', $smiley_texts).'</code> <span>' .__('produces'). '</span> <samp>'.Container::get('parser')->pd['smilies'][$smiley_texts[0]]['html'] .'</samp></p>'."\n";
}

?>
    </div>
</div>
<?php
Container::get('hooks')->fire('view.header.end');
