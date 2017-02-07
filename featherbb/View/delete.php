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

Container::get('hooks')->fire('view.delete.start');
?>

<div class="linkst">
    <div class="inbox">
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>"><?= __('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Forum', ['id' => $cur_post['fid'], 'name' => Url::url_friendly($cur_post['forum_name'])]) ?>"><?= Utils::escape($cur_post['forum_name']) ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('viewPost', ['id' => $cur_post['tid'], 'name' => Url::url_friendly($cur_post['subject']), 'pid' => $id]).'#p'.$id ?>"><?= Utils::escape($cur_post['subject']) ?></a></li>
            <li><span>»&#160;</span><strong><?= __('Delete post') ?></strong></li>
        </ul>
    </div>
</div>

<div class="blockform">
    <h2><span><?= __('Delete post') ?></span></h2>
    <div class="box">
        <form method="post" action="<?= Router::pathFor('deletePost', ['id'=>$id]) ?>">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <div class="forminfo">
                    <h3><span><?php printf($is_topic_post ? __('Topic by') : __('Reply by'), '<strong>'.Utils::escape($cur_post['poster']).'</strong>', Utils::format_time($cur_post['posted'])) ?></span></h3>
                    <p><?= ($is_topic_post) ? '<strong>'.__('Topic warning').'</strong>' : '<strong>'.__('Warning').'</strong>' ?><br /><?= __('Delete info') ?></p>
                </div>
            </div>
            <p class="buttons"><input type="submit" name="delete" value="<?= __('Delete') ?>" /> <a href="javascript:history.go(-1)"><?= __('Go back') ?></a></p>
        </form>
    </div>
</div>

<div id="postreview">
    <div class="blockpost">
        <div class="box">
            <div class="inbox">
                <div class="postbody">
                    <div class="postleft">
                        <dl>
                            <dt><strong><?= Utils::escape($cur_post['poster']) ?></strong></dt>
                            <dd><span><?= Utils::format_time($cur_post['posted']) ?></span></dd>
                        </dl>
                    </div>
                    <div class="postright">
                        <div class="postmsg">
                            <?= $cur_post['message']."\n" ?>
                        </div>
                    </div>
                </div>
                <div class="clearer"></div>
            </div>
        </div>
    </div>
</div>
<?php
Container::get('hooks')->fire('view.delete.end');
