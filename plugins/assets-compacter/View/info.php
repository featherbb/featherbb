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
?>
                <div class="blockform">
                    <h2><span><?= __('Select assets title'); ?></span></h2>
                    <div class="box">
                        <form id="minify-assets" method="post" action="">
                            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                            <p class="submittop"><input type="submit" name="compact" value="<?php _e('Compact'); ?>" /></p>
<?php
foreach ($themes_data as $key => $theme):
    // Check if destination folder exists
    if (!$theme['directory']) {
        $destination = '<span class="text-error">&times; '.__('Destination').' : '.__('Could not create destination directory').'</span>';
    } else {
        $destination = '<span class="text-success">&#10003; '.__('Destination').' : '.__('Destination directory valid').'</span>';
    }
    // Check if a stylesheet was modified since last minification
    if (!$theme['stylesheets_mtime']) {
        $stylesState = '<span class="text-error">&times; '.__('Stylesheets').' : '.__('No minified styles').'</span>';
    } else {
        $stylesState = ($theme['stylesheets_mtime'] > $last_modified_style)
            ? '<span class="text-success" title="'.sprintf(__('Last generation'), Utils::format_time($theme['stylesheets_mtime'])).'">&#10003; '.__('Stylesheets').' : '.__('Minified styles up to date').'</span>'
            : '<span class="text-warning" title="'.sprintf(__('Last file generation'), Utils::format_time($theme['stylesheets_mtime']), Utils::format_time($last_modified_style)).'">&#9888; '.__('Stylesheets').' : '.__('Minified styles need update').'</span>';
    }
    // Check if a javascript was modified since last minification
    if (!$theme['scripts_mtime']) {
        $scriptsState = '<span class="text-error">&times; '.__('Javascripts').' : '.__('No minified scripts').'</span>';
    } else {
        $scriptsState = ($theme['scripts_mtime'] > $last_modified_script)
            ? '<span class="text-success" title="'.sprintf(__('Last generation'), Utils::format_time($theme['scripts_mtime'])).'">&#10003; '.__('Javascripts').' : '.sprintf(__('Minified scripts up to date'), Utils::format_time($theme['scripts_mtime'])).'</span>'
            : '<span class="text-warning" title="'.sprintf(__('Last file generation'), Utils::format_time($theme['scripts_mtime']), Utils::format_time($last_modified_script)).'">&#9888; '.__('Javascripts').' : '.__('Minified scripts need update').'</span>';
    }
?>
                            <div class="inform" id="theme-<?= strtolower(Utils::escape($key)) ?>">
                                <fieldset>
                                    <legend><?= Utils::escape($key) ?></legend>
                                    <div class="infldset">
                                        <table>
            								<tr>
                                                <th scope="row"><?php _e('Overview') ?></th>
                                                <td><?= $destination . $stylesState . $scriptsState; ?></td>
                                            </tr>
            								<tr id="theme-<?= strtolower(Utils::escape($key)) ?>-stylesheets">
                                                <th scope="row">
                                                    <?php _e('Stylesheets') ?><br>
                                                    <a href="#" onclick="return select_checkboxes('theme-<?= strtolower(Utils::escape($key)) ?>-stylesheets', this, '<?php _e('Unselect all') ?>')"><?php _e('Select all') ?></a>
                                                </th>
                                                <td>
<?php foreach ($theme['stylesheets'] as $stylesheet): ?>
                                                    <label style="overflow-x:scroll;overflow-y:hidden;white-space:nowrap;max-width:400px;display:block">
                                                        <input type="checkbox" name="themes[<?= Utils::escape($key); ?>][stylesheets][]" value="<?= $stylesheet; ?>" <?= in_array($stylesheet, $theme['previous_styles']) ? 'checked="checked"' : ''; ?> /> <span style="display:inline-block;"><?= $stylesheet; ?></span>
                                                    </label>
<?php endforeach; ?>
                                                </td>
                                            </tr>
            								<tr id="theme-<?= strtolower(Utils::escape($key)) ?>-scripts">
                                                <th scope="row">
                                                    <?php _e('Javascripts') ?><br>
                                                    <a href="#" onclick="return select_checkboxes('theme-<?= strtolower(Utils::escape($key)) ?>-scripts', this, '<?php _e('Unselect all') ?>')"><?php _e('Select all') ?></a>
                                                </th>
                                                <td>
<?php foreach ($theme['scripts'] as $script): ?>
                                                    <label style="overflow-x:scroll;overflow-y:hidden;white-space:nowrap;max-width:400px;display:block">
                                                        <input type="checkbox" name="themes[<?= Utils::escape($key); ?>][scripts][]" value="<?= $script; ?>" <?= in_array($script, $theme['previous_scripts']) ? 'checked="checked"' : ''; ?> /> <span style="display:inline-block;"><?= $script; ?></span>
                                                    </label>
<?php endforeach; ?>
                                                </td>
                                            </tr>
<?php endforeach; ?>
                                        </table>
                                    </div>
                                </fieldset>
                            </div>
                            <p class="submitend"><input type="submit" name="compact" value="<?php _e('Compact'); ?>" /></p>
                        </form>
                    </div>
                </div>
                <div class="clearer"></div>
            </div>
