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

Container::get('hooks')->fire('view.admin.bans.admin_bans.start');
?>

    <div class="blockform">
        <h2><span><?= __('New ban head') ?></span></h2>
        <div class="box">
            <form id="bans" method="post" action="<?= Router::pathFor('addBan') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Add ban subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Username label') ?><div><input type="submit" name="add_ban" value="<?= __('Add') ?>" tabindex="2" /></div></th>
                                    <td>
                                        <input type="text" name="new_ban_user" size="25" maxlength="25" tabindex="1" autofocus />
                                        <span><?= __('Username advanced help') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
            </form>
        </div>

        <h2 class="block2"><span><?= __('Ban search head') ?></span></h2>
        <div class="box">
            <form id="find_bans" method="get" action="<?= Router::pathFor('adminBans') ?>">
                <p class="submittop"><input type="submit" name="find_ban" value="<?= __('Submit search') ?>" tabindex="3" /></p>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Ban search subhead') ?></legend>
                        <div class="infldset">
                            <p><?= __('Ban search info') ?></p>
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Username label') ?></th>
                                    <td><input type="text" name="username" size="30" maxlength="25" tabindex="4" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('IP label') ?></th>
                                    <td><input type="text" name="ip" size="30" maxlength="255" tabindex="5" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('E-mail label') ?></th>
                                    <td><input type="text" name="email" size="30" maxlength="80" tabindex="6" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Message label') ?></th>
                                    <td><input type="text" name="message" size="30" maxlength="255" tabindex="7" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Expire after label') ?></th>
                                    <td><input type="text" name="expire_after" size="10" maxlength="10" tabindex="8" />
                                    <span><?= __('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Expire before label') ?></th>
                                    <td><input type="text" name="expire_before" size="10" maxlength="10" tabindex="9" />
                                    <span><?= __('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Order by label') ?></th>
                                    <td>
                                        <select name="order_by" tabindex="10">
                                            <option value="username" selected="selected"><?= __('Order by username') ?></option>
                                            <option value="ip"><?= __('Order by ip') ?></option>
                                            <option value="email"><?= __('Order by e-mail') ?></option>
                                            <option value="expire"><?= __('Order by expire') ?></option>
                                        </select>
                                        &#160;&#160;&#160;
                                        <select name="direction" tabindex="11">
                                            <option value="ASC" selected="selected"><?= __('Ascending') ?></option>
                                            <option value="DESC"><?= __('Descending') ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <?php Container::get('hooks')->fire('view.admin.bans.admin_bans.form'); ?>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <p class="submitend"><input type="submit" name="find_ban" value="<?= __('Submit search') ?>" tabindex="12" /></p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.bans.admin_bans.end');
