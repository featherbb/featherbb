<?php
use FeatherBB\Core\Utils;
?>
        <div id="msg" class="block info">
            <h2><span><?php _e('Move conversations', 'private_messages') ?></span></h2>
            <div class="box">
                <div class="inbox">
                    <form method="post" action="<?= $feather->request()->getPath(); ?>">
                        <input type="hidden" name="topics" value="<?= implode(",",$topics); ?>" />
                        <input name="move_comply" value="1" type="hidden" />
                        <input name="action" value="move" type="hidden" />
                        <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                        <div class="inform">
        					<div class="infldset">
        						<p><?php _e('Select move destination', 'private_messages'); ?></p>
        						<br />
        						<select name="move_to">
                                    <?php foreach ($inboxes as $key => $inbox) { ?>
                                        <option value="<?= $key ?>"><?= Utils::escape($inbox['name']); ?></option>
                                    <?php } ?>
        						</select>
        					</div>
        			    </div>
                        <p class="buttons"><input type="submit" name="move" value="<?php _e('Move'); ?>" /> <a href="javascript:history.go(-1)"><?php _e('Go back') ?></a></p>
                    </form>
                </div>
            </div>
        </div>
        <div class="clearer"></div>
