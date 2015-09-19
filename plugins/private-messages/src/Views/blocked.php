<?php

use \FeatherBB\Core\Utils;
use \FeatherBB\Core\Url;
?>

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
                                                <input type="text" name="req_username" size="35" maxlength="80" tabindex="1" required autofocus />
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
                    <form method="post" action="/featherbb/admin/categories/delete/">
                        <input type="hidden" name="<?= $csrf_key; ?>" value="<?= $csrf_token; ?>">
                        <div class="inform">
                            <fieldset>
        						<div class="infldset">
        							<table cellspacing="0">
            							<thead>
            								<tr>
            									<th class="tcl" scope="col">Folder name</th>
            									<th class="hidehead" scope="col">Actions</th>
            								</tr>
            							</thead>
            							<tbody>
<?php foreach ($blocks as $bid => $block): ?>
                            				<tr>
                            					<td class="tcl"><input type="text" name="folder[4]" value="Test" size="24" maxlength="30" /></td>
                            					<td><input type="submit" name="update[4]" value="Update" />&#160;<input type="submit" name="remove[4]" value="Remove" /></td>
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
