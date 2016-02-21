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

Container::get('hooks')->fire('view.admin.users.admin_users.start');
?>

    <div class="blockform">
        <h2><span><?php _e('User search head') ?></span></h2>
        <div class="box">
            <form id="find_user" method="get" action="<?= Router::pathFor('adminUsers') ?>">
                <p class="submittop"><input type="submit" name="find_user" value="<?php _e('Submit search') ?>" tabindex="1" /></p>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('User search subhead') ?></legend>
                        <div class="infldset">
                            <p><?php _e('User search info') ?></p>
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('Username label') ?></th>
                                    <td><input type="text" name="form[username]" size="25" maxlength="25" tabindex="2" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('E-mail address label') ?></th>
                                    <td><input type="text" name="form[email]" size="30" maxlength="80" tabindex="3" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Title label') ?></th>
                                    <td><input type="text" name="form[title]" size="30" maxlength="50" tabindex="4" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Real name label') ?></th>
                                    <td><input type="text" name="form[realname]" size="30" maxlength="40" tabindex="5" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Website label') ?></th>
                                    <td><input type="text" name="form[url]" size="35" maxlength="100" tabindex="6" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Jabber label') ?></th>
                                    <td><input type="text" name="form[jabber]" size="30" maxlength="75" tabindex="7" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('ICQ label') ?></th>
                                    <td><input type="text" name="form[icq]" size="12" maxlength="12" tabindex="8" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('MSN label') ?></th>
                                    <td><input type="text" name="form[msn]" size="30" maxlength="50" tabindex="9" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('AOL label') ?></th>
                                    <td><input type="text" name="form[aim]" size="20" maxlength="20" tabindex="10" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Yahoo label') ?></th>
                                    <td><input type="text" name="form[yahoo]" size="20" maxlength="20" tabindex="11" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Location label') ?></th>
                                    <td><input type="text" name="form[location]" size="30" maxlength="30" tabindex="12" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Signature label') ?></th>
                                    <td><input type="text" name="form[signature]" size="35" maxlength="512" tabindex="13" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Admin note label') ?></th>
                                    <td><input type="text" name="form[admin_note]" size="30" maxlength="30" tabindex="14" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Posts more than label') ?></th>
                                    <td><input type="text" name="posts_greater" size="5" maxlength="8" tabindex="15" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Posts less than label') ?></th>
                                    <td><input type="text" name="posts_less" size="5" maxlength="8" tabindex="16" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Last post after label') ?></th>
                                    <td><input type="text" name="last_post_after" size="24" maxlength="19" tabindex="17" />
                                    <span><?php _e('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Last post before label') ?></th>
                                    <td><input type="text" name="last_post_before" size="24" maxlength="19" tabindex="18" />
                                    <span><?php _e('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Last visit after label') ?></th>
                                    <td><input type="text" name="last_visit_after" size="24" maxlength="19" tabindex="17" />
                                    <span><?php _e('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Last visit before label') ?></th>
                                    <td><input type="text" name="last_visit_before" size="24" maxlength="19" tabindex="18" />
                                    <span><?php _e('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Registered after label') ?></th>
                                    <td><input type="text" name="registered_after" size="24" maxlength="19" tabindex="19" />
                                    <span><?php _e('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Registered before label') ?></th>
                                    <td><input type="text" name="registered_before" size="24" maxlength="19" tabindex="20" />
                                    <span><?php _e('Date help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Order by label') ?></th>
                                    <td>
                                        <select name="order_by" tabindex="21">
                                            <option value="username" selected="selected"><?php _e('Order by username') ?></option>
                                            <option value="email"><?php _e('Order by e-mail') ?></option>
                                            <option value="num_posts"><?php _e('Order by posts') ?></option>
                                            <option value="last_post"><?php _e('Order by last post') ?></option>
                                            <option value="last_visit"><?php _e('Order by last visit') ?></option>
                                            <option value="registered"><?php _e('Order by registered') ?></option>
                                        </select>&#160;&#160;&#160;<select name="direction" tabindex="22">
                                            <option value="ASC" selected="selected"><?php _e('Ascending') ?></option>
                                            <option value="DESC"><?php _e('Descending') ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('User group label') ?></th>
                                    <td>
                                        <select name="user_group" tabindex="23">
                                            <option value="-1" selected="selected"><?php _e('All groups') ?></option>
                                            <option value="0"><?php _e('Unverified users') ?></option>
                                            <?= $group_list; ?>
                                        </select>
                                    </td>
                                </tr>
                                <?php Container::get('hooks')->fire('view.admin.users.admin_users.form'); ?>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <p class="submitend"><input type="submit" name="find_user" value="<?php _e('Submit search') ?>" tabindex="25" /></p>
            </form>
        </div>

        <h2 class="block2"><span><?php _e('IP search head') ?></span></h2>
        <div class="box">
            <form method="get" action="<?= Router::pathFor('usersIpShow') ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('IP search subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?php _e('IP address label') ?><div><input type="submit" value="<?php _e('Find IP address') ?>" tabindex="26" /></div></th>
                                    <td><input type="text" name="ip" size="18" maxlength="15" tabindex="24" />
                                    <span><?php _e('IP address help') ?></span></td>
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
