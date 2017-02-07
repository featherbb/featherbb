<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.moderate.move_topics.start');
?>

<div class="blockform">
    <h2><span><?php echo($action == 'single') ? __('Move topic') : __('Move topics') ?></span></h2>
    <div class="box">
        <form method="post" action="">
            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
            <div class="inform">
                <input type="hidden" name="topics" value="<?= $topics ?>" />
                <fieldset>
                    <legend><?= __('Move legend') ?></legend>
                    <div class="infldset">
                        <label><?= __('Move to') ?>
                        <br /><select name="move_to_forum">
                                <?= $list_forums ?>
                            </optgroup>
                        </select>
                        <br /></label>
                        <div class="rbox">
                            <label><input type="checkbox" name="with_redirect" value="1"<?php if ($action == 'single') {
    echo ' checked="checked"';
} ?> /><?= __('Leave redirect') ?><br /></label>
                        </div>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="move_topics_to" value="<?= __('Move') ?>" /> <a href="javascript:history.go(-1)"><?= __('Go back') ?></a></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.moderate.move_topics.end');
