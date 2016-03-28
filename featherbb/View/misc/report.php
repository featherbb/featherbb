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

Container::get('hooks')->fire('view.misc.email.report');
?>

<div class="linkst">
    <div class="inbox">
        <ul class="crumbs">
            <li><a href="<?= Url::base() ?>"><?php _e('Index') ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('Forum', ['id' => $cur_post['fid'], 'name' => Url::url_friendly($cur_post['forum_name'])]) ?>"><?= Utils::escape($cur_post['forum_name']) ?></a></li>
            <li><span>»&#160;</span><a href="<?= Router::pathFor('viewPost', ['id' => $cur_post['tid'], 'name' => URL::url_friendly($cur_post['subject']), 'pid' => $id]).'#p'.$id ?>"><?= Utils::escape($cur_post['subject']) ?></a></li>
            <li><span>»&#160;</span><strong><?php _e('Report post') ?></strong></li>
        </ul>
    </div>
</div>

<div id="reportform" class="blockform">
    <h2><span><?php _e('Report post') ?></span></h2>
    <div class="box">
        <form id="report" method="post" action="<?= Router::pathFor('report', ['id' => $id]) ?>">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Reason desc') ?></legend>
                    <div class="infldset txtarea">
                        <input type="hidden" name="form_sent" value="1" />
                        <label class="required"><strong><?php _e('Reason') ?> <span><?php _e('Required') ?></span></strong><br /><textarea name="req_reason" rows="5" cols="60" required="required" autofocus></textarea><br /></label>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="submit" value="<?php _e('Submit') ?>" accesskey="s" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.misc.report.start');
