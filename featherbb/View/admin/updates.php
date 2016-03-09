<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.updates.start');
?>

    <div class="blockform">
        <h2><span><?php _e('Available updates') ?></span></h2>
        <div class="box">
            <form id="check-updates" method="get" action="<?= Router::pathFor('adminCensoring') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <p class="submittop"><input type="submit" name="save" value="<?php _e('Check for updates') ?>" /></p>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('FeatherBB core') ?></legend>
                        <div class="infldset">
                            <p><?php _e('Add word info'); echo (ForumSettings::get('o_censoring') == '1' ? sprintf(__('Censoring enabled'), '<a href="'.Router::pathFor('adminOptions').'#censoring">'.__('Options').'</a>') : sprintf(__('Censoring disabled'), '<a href="'.Router::pathFor('adminOptions').'#censoring">'.__('Options').'</a>')) ?></p>
                            <table>
                            <thead>
                                <tr>
                                    <th class="tcl" scope="col"><?php _e('Censored word label') ?></th>
                                    <th class="tc2" scope="col"><?php _e('Replacement label') ?></th>
                                    <th class="hidehead" scope="col"><?php _e('Action label') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="tcl"><input type="text" name="new_search_for" size="24" maxlength="60" tabindex="1" /></td>
                                    <td class="tc2"><input type="text" name="new_replace_with" size="24" maxlength="60" tabindex="2" /></td>
                                    <td><input type="submit" name="add_word" value="<?php _e('Add') ?>" tabindex="3" /></td>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Plugins') ?></legend>
                        <div class="infldset">
<?php
if (!empty($plugin_updates)) {
    ?>
                            <table>
                            <thead>
                                <tr>
                                    <th class="tcl" scope="col"><?php _e('Censored word label') ?></th>
                                    <th class="tc2" scope="col"><?php _e('Replacement label') ?></th>
                                    <th class="hidehead" scope="col"><?php _e('Action label') ?></th>
                                </tr>
                            </thead>
                            <tbody>
<?php

    foreach ($plugin_updates as $plugin) {
        echo "\t\t\t\t\t\t\t\t".'<tr><td class="tcl"><input type="checkbox" name="plugin_updates['.$plugin->name.']" value="'.$plugin->name.'" size="24" maxlength="60" /></td><td class="tc2">'.$plugin->last_version.'</td><td>'.$plugin->description.'</td></tr>'."\n";
    }

    ?>
                            </tbody>
                            </table>
<?php

} else {
    echo "\t\t\t\t\t\t\t".'<p>'.__('All plugins are up to date').'</p>'."\n";
}

?>
                        </div>
                    </fieldset>
                    <?php if (!empty($plugin_updates)): ?><p class="buttons"><input type="submit" name="upgrade" value="<?php _e('Upgrade plugins') ?>" /></p><?php endif; ?>
                </div>
            </form>
            <form id="upgrade-themes" method="post" action="<?= Router::pathFor('adminUpgradeThemes') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Themes') ?></legend>
                        <div class="infldset">
<?php
if (!empty($theme_updates)) {
    ?>
                            <table>
                            <thead>
                                <tr>
                                    <th class="tcl" scope="col"><?php _e('Censored word label') ?></th>
                                    <th class="tc2" scope="col"><?php _e('Replacement label') ?></th>
                                    <th class="hidehead" scope="col"><?php _e('Action label') ?></th>
                                </tr>
                            </thead>
                            <tbody>
<?php

    foreach ($theme_updates as $theme) {
        echo "\t\t\t\t\t\t\t\t".'<tr><td class="tcl"><input type="text" name="search_for['.$theme['id'].']" value="'.Utils::escape($theme['search_for']).'" size="24" maxlength="60" /></td><td class="tc2"><input type="text" name="replace_with['.$theme['id'].']" value="'.Utils::escape($theme['replace_with']).'" size="24" maxlength="60" /></td><td><input type="submit" name="update['.$theme['id'].']" value="'.__('Update').'" />&#160;<input type="submit" name="remove['.$theme['id'].']" value="'.__('Remove').'" /></td></tr>'."\n";
    }

    ?>
                            </tbody>
                            </table>
<?php

} else {
    echo "\t\t\t\t\t\t\t".'<p>'.__('All themes are up to date').'</p>'."\n";
}

?>
                        </div>
                    </fieldset>
                </div>
                <?php if (!empty($theme_updates)): ?><p class="buttons"><input type="submit" name="upgrade" value="<?php _e('Upgrade themes') ?>" /></p><?php endif; ?>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>
<?php
Container::get('hooks')->fire('view.admin.updates.end');
