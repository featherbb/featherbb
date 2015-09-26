<?php

use FeatherBB\Core\Utils;

if (!empty($errors)) { ?>
            <div id="msg" class="block error">
                <h3><?php _e('Block errors', 'private_messages') ?></h3>
                <div>
                    <p><?php _e('Block error info', 'private_messages') ?></p>
                    <ul class="error-list">
<?php foreach ($errors as $error): ?>
                        <li><strong><?= Utils::escape($error) ?></strong></li>
<?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <br />
<?php } ?>
            <div class="blockform">
                <h2><span><?php _e('Add block', 'private_messages') ?></span></h2>
                <div class="box">
                    <form method="post" action="">
                        <div class="inform">
                            <fieldset>
                                <legend><?php _e('Add block', 'private_messages') ?></legend>
                                <div class="infldset">
                                    <table class="aligntop">
                                        <tr>
                                            <th scope="row"><?php _e('Add block legend', 'private_messages') ?>
                                                <div><input type="submit" name="add_block" value="<?php _e('Submit') ?>" tabindex="2" /></div>
                                            </th>
                                            <td>
                                                <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                                                <input type="text" name="req_username" value="<?= $username ?>" size="35" maxlength="80" tabindex="1" required autofocus />
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </fieldset>
                        </div>
                    </form>
                </div>
<?php if (!empty($blocks)): ?>
                <h2 class="block2"><span><?php _e('Blocked Users', 'private_messages') ?></span></h2>
                <div class="box">
                    <form method="post" action="">
                        <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                        <div class="inform">
                            <fieldset>
                                <div class="infldset">
                                    <table cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th class="tcl" scope="col"><?php _e('Username') ?></th>
                                                <th class="hidehead" scope="col"><?php _e('Actions', 'private_messages') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
<?php foreach ($blocks as $bid => $block): ?>
                                            <tr>
                                                <td class="tcl"><strong><?= $block->username ?></strong></td>
                                                <td><input type="submit" name="remove_block[<?= $block->block_id ?>]" value="Remove" /></td>
                                            </tr>
<?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </fieldset>
                        </div>
                    </form>
                </div>
<?php endif; ?>
            </div>
            <div class="clearer"></div>
