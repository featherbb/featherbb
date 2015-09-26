<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

$feather->hooks->fire('view.profile.upload_avatar.start');
?>
<div class="blockform">
    <h2><span><?php _e('Upload avatar') ?></span></h2>
    <div class="box">
        <form id="upload_avatar" method="post" enctype="multipart/form-data" action="<?= $feather->urlFor('profileAction', ['id' => $id, 'action' => 'upload_avatar2']) ?>" onsubmit="return process_form(this)">
            <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Upload avatar legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="form_sent" value="1" />
                        <input type="hidden" name="MAX_FILE_SIZE" value="<?= $feather->forum_settings['o_avatars_size'] ?>" />
                        <label class="required"><strong><?php _e('File') ?> <span><?php _e('Required') ?></span></strong><br /><input name="req_file" type="file" size="40" /><br /></label>
                        <p><?php _e('Avatar desc'); echo ' '.$feather->forum_settings['o_avatars_width'].' x '.$feather->forum_settings['o_avatars_height'].' '.__('pixels').' '.__('and').' '.Utils::forum_number_format($feather->forum_settings['o_avatars_size']).' '.__('bytes').' ('.$feather->utils->file_size($feather->forum_settings['o_avatars_size']).').' ?></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="upload" value="<?php _e('Upload') ?>" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
        </form>
    </div>
</div>

<?php
$feather->hooks->fire('view.profile.upload_avatar.end');
