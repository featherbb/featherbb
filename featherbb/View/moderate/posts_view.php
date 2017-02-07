<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
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

Container::get('hooks')->fire('view.moderate.posts_view.start');
?>

<div class="linkst">
    <div class="inbox crumbsplus">
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>"><?= __('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Forum', ['id' => $fid, 'name' => $url_forum]) ?>"><?= Utils::escape($cur_topic['forum_name']) ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Topic', ['id' => $id, 'name' => $url_topic]) ?>"><?= Utils::escape($cur_topic['subject']) ?></a></li>
            <li><span>»&#160;</span><strong><?= __('Moderate') ?></strong></li>
        </ul>
        <div class="pagepost">
            <p class="pagelink conl"><?= $paging_links ?></p>
        </div>
        <div class="clearer"></div>
    </div>
</div>

<form method="post" action="">
<input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
<?php
$post_count = 0; // Keep track of post numbers
foreach ($post_data as $post) {
    $post_count++; ?>
    <div id="p<?= $post['id'] ?>" class="blockpost<?php if ($post['id'] == $cur_topic['first_post_id']) {
        echo ' firstpost';
    } ?><?php echo($post_count % 2 == 0) ? ' roweven' : ' rowodd' ?><?php if ($post_count == 1) {
        echo ' blockpost1';
    } ?>">
        <h2><span><span class="conr">#<?php echo($start_from + $post_count) ?></span> <a href="<?= Router::pathFor('viewPost', ['id' => $id, 'name' => $url_topic, 'pid' => $post['id']]).'#p'.$post['id'] ?>"><?= Utils::format_time($post['posted']) ?></a></span></h2>
        <div class="box">
            <div class="inbox">
                <div class="postbody">
                    <div class="postleft">
                        <dl>
                            <dt><strong><?= $post['poster_disp'] ?></strong></dt>
                            <dd class="usertitle"><strong><?= $post['user_title'] ?></strong></dd>
                        </dl>
                    </div>
                    <div class="postright">
                        <h3 class="nosize"><?= __('Message') ?></h3>
                        <div class="postmsg">
                            <?= $post['message']."\n" ?>
    <?php if ($post['edited'] != '') {
        echo "\t\t\t\t\t\t".'<p class="postedit"><em>'.__('Last edit').' '.Utils::escape($post['edited_by']).' ('.Utils::format_time($post['edited']).')</em></p>'."\n";
    } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="inbox">
                <div class="postfoot clearb">
                    <div class="postfootright"><?php echo($post['id'] != $cur_topic['first_post_id']) ? '<p class="multidelete"><label><strong>'.__('Select').'</strong>&#160;<input type="checkbox" name="posts['.$post['id'].']" value="1" /></label></p>' : '<p>'.__('Cannot select first').'</p>' ?></div>
                </div>
            </div>
        </div>
    </div>
<?php

}
?>

<div class="postlinksb">
    <div class="inbox crumbsplus">
        <div class="pagepost">
            <p class="pagelink conl"><?= $paging_links ?></p>
            <p class="conr modbuttons"><input type="submit" name="split_posts" value="<?= __('Split') ?>"<?= $button_status ?> /> <input type="submit" name="delete_posts" value="<?= __('Delete') ?>"<?= $button_status ?> /></p>
            <div class="clearer"></div>
        </div>
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>"><?= __('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Forum', ['id' => $fid, 'name' => $url_forum]) ?>"><?= Utils::escape($cur_topic['forum_name']) ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Topic', ['id' => $id, 'name' => $url_topic]) ?>"><?= Utils::escape($cur_topic['subject']) ?></a></li>
            <li><span>»&#160;</span><strong><?= __('Moderate') ?></strong></li>
        </ul>
        <div class="clearer"></div>
    </div>
</div>
</form>

<?php
Container::get('hooks')->fire('view.moderate.posts_view.end');
