<?php
use FeatherBB\Core\Utils;

?>
        <div class="blockform">
            <h2><span><?php _e('Move conversations', 'private_messages') ?></span></h2>
            <div class="box">
                <form method="post" action="<?= $feather->request()->getPath(); ?>">
                    <input type="hidden" name="topics" value="<?= implode(",",$topics); ?>" />
                    <input name="move_comply" value="1" type="hidden" />
                    <input name="action" value="move" type="hidden" />
                    <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                    <div class="inform">
                        <fieldset>
                            <legend><?php _e('Move legend'); ?></legend>
                            <div class="infldset">
                                <label><?php _e('Move to'); ?><br>
                                    <select name="move_to">
                                        <?php foreach ($inboxes as $key => $inbox): if($key == 1) continue; ?>
                                            <option value="<?= $key ?>"><?= Utils::escape($inbox['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                        </fieldset>
                    </div>
                    <p class="buttons"><input type="submit" name="move" value="<?php _e('Move'); ?>" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
                </form>
            </div>
        </div>

        <div class="clearer"></div>
