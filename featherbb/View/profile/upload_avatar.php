<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.profile.upload_avatar.start');
?>
<div class="blockform">
    <h2><span><?php _e('Upload avatar') ?></span></h2>
    <div class="box">
        <form id="upload_avatar" method="post" enctype="multipart/form-data" action="<?= Router::pathFor('profileAction', ['id' => $id, 'action' => 'upload_avatar2']) ?>" onsubmit="return process_form(this)">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Upload avatar legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="form_sent" value="1" />
                        <input type="hidden" name="MAX_FILE_SIZE" value="<?= Config::get('forum_settings')['o_avatars_size'] ?>" />
                        <label class="required"><strong><?php _e('File') ?> <span><?php _e('Required') ?></span></strong><br /><input name="req_file" type="file" size="40" /><br /></label>
                        <p><?php _e('Avatar desc'); echo ' '.Config::get('forum_settings')['o_avatars_width'].' x '.Config::get('forum_settings')['o_avatars_height'].' '.__('pixels').' '.__('and').' '.Utils::forum_number_format(Config::get('forum_settings')['o_avatars_size']).' '.__('bytes').' ('.Utils::file_size(Config::get('forum_settings')['o_avatars_size']).').' ?></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="upload" value="<?php _e('Upload') ?>" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.profile.upload_avatar.end');
