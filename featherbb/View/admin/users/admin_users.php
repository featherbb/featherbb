<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.users.admin_users.start');
?>

    <div class="blockform">
        <h2><span><?= __('User search head') ?></span></h2>
        <div class="box">
            <form id="find_user" method="get" action="<?= Router::pathFor('adminUsers') ?>">
                <p class="submittop"><input type="submit" name="find_user" value="<?= __('Submit search') ?>" tabindex="1" /></p>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('User search subhead') ?></legend>
                        <div class="infldset">
                            <p><?= __('User search info') ?></p>
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Username label') ?></th>
                                    <td><input type="text" name="form[username]" size="25" maxlength="25" tabindex="2" autofocus /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('E-mail address label') ?></th>
                                    <td><input type="text" name="form[email]" size="30" maxlength="80" tabindex="3" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Title label') ?></th>
                                    <td><input type="text" name="form[title]" size="30" maxlength="50" tabindex="4" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Real name label') ?></th>
                                    <td><input type="text" name="form[realname]" size="30" maxlength="40" tabindex="5" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Website label') ?></th>
                                    <td><input type="text" name="form[url]" size="35" maxlength="100" tabindex="6" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Location label') ?></th>
                                    <td><input type="text" name="form[location]" size="30" maxlength="30" tabindex="12" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Signature label') ?></th>
                                    <td><input type="text" name="form[signature]" size="35" maxlength="512" tabindex="13" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Admin note label') ?></th>
                                    <td><input type="text" name="form[admin_note]" size="30" maxlength="30" tabindex="14" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Posts more than label') ?></th>
                                    <td><input type="text" name="posts_greater" size="5" maxlength="8" tabindex="15" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Posts less than label') ?></th>
                                    <td><input type="text" name="posts_less" size="5" maxlength="8" tabindex="16" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Last post after label') ?></th>
                                    <td><input type="text" name="last_post_after" size="24" maxlength="19" tabindex="17" />
                                    <span><?= __('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Last post before label') ?></th>
                                    <td><input type="text" name="last_post_before" size="24" maxlength="19" tabindex="18" />
                                    <span><?= __('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Last visit after label') ?></th>
                                    <td><input type="text" name="last_visit_after" size="24" maxlength="19" tabindex="17" />
                                    <span><?= __('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Last visit before label') ?></th>
                                    <td><input type="text" name="last_visit_before" size="24" maxlength="19" tabindex="18" />
                                    <span><?= __('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Registered after label') ?></th>
                                    <td><input type="text" name="registered_after" size="24" maxlength="19" tabindex="19" />
                                    <span><?= __('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Registered before label') ?></th>
                                    <td><input type="text" name="registered_before" size="24" maxlength="19" tabindex="20" />
                                    <span><?= __('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Order by label') ?></th>
                                    <td>
                                        <select name="order_by" tabindex="21">
                                            <option value="username" selected="selected"><?= __('Order by username') ?></option>
                                            <option value="email"><?= __('Order by e-mail') ?></option>
                                            <option value="num_posts"><?= __('Order by posts') ?></option>
                                            <option value="last_post"><?= __('Order by last post') ?></option>
                                            <option value="last_visit"><?= __('Order by last visit') ?></option>
                                            <option value="registered"><?= __('Order by registered') ?></option>
                                        </select>&#160;&#160;&#160;<select name="direction" tabindex="22">
                                            <option value="ASC" selected="selected"><?= __('Ascending') ?></option>
                                            <option value="DESC"><?= __('Descending') ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('User group label') ?></th>
                                    <td>
                                        <select name="user_group" tabindex="23">
                                            <option value="-1" selected="selected"><?= __('All groups') ?></option>
                                            <option value="0"><?= __('Unverified users') ?></option>
                                            <?= $group_list; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php Container::get('hooks')->fire('view.admin.users.admin_users.form'); ?>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <p class="submitend"><input type="submit" name="find_user" value="<?= __('Submit search') ?>" tabindex="25" /></p>
            </form>
        </div>

        <h2 class="block2"><span><?= __('IP search head') ?></span></h2>
        <div class="box">
            <form method="get" action="<?= Router::pathFor('usersIpShow') ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?= __('IP search subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('IP address label') ?><div><input type="submit" value="<?= __('Find IP address') ?>" tabindex="26" /></div></th>
                                    <td><input type="text" name="ip" size="18" maxlength="15" tabindex="24" />
                                    <span><?= __('IP address help') ?></span></td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.users.admin_users.end');
