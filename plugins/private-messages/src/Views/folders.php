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
                <h2><span><?php _e('Add folder', 'private_messages') ?></span></h2>
                <div class="box">
                    <form id="folder" action="" method="post" onsubmit="return process_form(this)">
                        <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                        <div class="inform">
                            <fieldset>
                                <legend><?php _e('Add folder', 'private_messages') ?></legend>
                                <div class="infldset">
                                    <label><?php _e('Folder name', 'private_messages') ?><br>
                                        <input type="text" name="req_folder" size="25" value="<?= isset($folder) ? $folder : '' ?>" maxlength="30" tabindex="1" /><br />
                                    </label>
                                </div>
                            </fieldset>
                        </div>
                        <p class="buttons"><input type="submit" name="add_folder" value="Add" accesskey="s" /></p>
                    </form>

                </div>
<?php
$folders = $inboxes;
// Unset default inboxes
unset($folders['1'], $folders['2'], $folders['3']);
if (!empty($folders)): ?>
                <h2 class="block2"><span><?php _e('My Folders', 'private_messages') ?></span></h2>
                <div class="box">
                    <form method="post" action="">
                        <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                        <div class="inform">
                            <fieldset>
                                <div class="infldset">
                                    <table cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th class="tcl" scope="col"><?php _e('Folder name', 'private_messages') ?></th>
                                                <th class="hidehead" scope="col"><?php _e('Actions', 'private_messages') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
<?php foreach ($folders as $fid => $folder): ?>
                                            <tr>
                                                <td class="tcl"><input type="text" name="folder[<?= $fid ?>]" value="<?= Utils::escape($folder['name']) ?>" size="24" maxlength="30" /></td>
                                                <td><input type="submit" name="update_folder[<?= $fid ?>]" value="Update" />&#160;<input type="submit" name="remove_folder[<?= $fid ?>]" value="Remove" /></td>
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
