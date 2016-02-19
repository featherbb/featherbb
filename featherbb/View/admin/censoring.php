<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.censoring.start');
?>

    <div class="blockform">
        <h2><span><?php _e('Censoring head') ?></span></h2>
        <div class="box">
            <form id="censoring" method="post" action="<?= Router::pathFor('adminCensoring') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>">
                  <input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?php _e('Add word subhead') ?></legend>
                        <div class="infldset">
                            <p><?php _e('Add word info'); echo (Config::get('forum_settings')['o_censoring'] == '1' ? sprintf(__('Censoring enabled'), '<a href="'.Router::pathFor('adminOptions').'#censoring">'.__('Options').'</a>') : sprintf(__('Censoring disabled'), '<a href="'.Router::pathFor('adminOptions').'#censoring">'.__('Options').'</a>')) ?></p>
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
                        <legend><?php _e('Edit remove subhead') ?></legend>
                        <div class="infldset">
<?php
if (!empty($word_data)) {
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

    foreach ($word_data as $word) {
        echo "\t\t\t\t\t\t\t\t".'<tr><td class="tcl"><input type="text" name="search_for['.$word['id'].']" value="'.Utils::escape($word['search_for']).'" size="24" maxlength="60" /></td><td class="tc2"><input type="text" name="replace_with['.$word['id'].']" value="'.Utils::escape($word['replace_with']).'" size="24" maxlength="60" /></td><td><input type="submit" name="update['.$word['id'].']" value="'.__('Update').'" />&#160;<input type="submit" name="remove['.$word['id'].']" value="'.__('Remove').'" /></td></tr>'."\n";
    }

    ?>
                            </tbody>
                            </table>
<?php

} else {
    echo "\t\t\t\t\t\t\t".'<p>'.__('No words in list').'</p>'."\n";
}

?>
                        </div>
                    </fieldset>
                </div>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.censoring.end');
