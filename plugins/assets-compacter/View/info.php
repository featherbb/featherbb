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
                        <form id="select-assets" method="post" action="">
                            <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                            <p class="submittop"><input type="submit" name="compact" value="<?php _e('Compact'); ?>" /></p>
                            <div class="inform">
                                <fieldset>
                                    <p><?php _e('Select assets description'); ?></p>
                                    <legend><?php _e('Stylesheets'); ?></legend>
                                    <div class="infldset">
                                        <div class="rbox">
<?php foreach ($stylesheets as $key => $stylesheet): ?>
                                            <label><input type="checkbox" name="stylesheets[]" value="<?= $stylesheet; ?>" /> <?= $stylesheet; ?></label><br/>
<?php endforeach; ?>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="inform">
                                <fieldset>
                                    <legend><?php _e('Javascripts'); ?></legend>
                                    <div class="infldset">
                                        <div class="rbox">
<?php foreach ($scripts as $script): ?>
                                            <label><input type="checkbox" name="scripts[]" value="<?= $script; ?>" /> <?= $script; ?></label><br/>
<?php endforeach; ?>
                                        </div>
                                    </div>
                                </fieldset>
                            </div>
                            <p class="submitend"><input type="submit" name="compact" value="<?php _e('Compact'); ?>" /></p>
                        </form>
                    </div>

                    <h2 class="block2"><span><?php _e('Minified results title'); ?></span></h2>
                    <div class="box">
                        <div class="fakeform">
                            <div class="inform">
                                <fieldset>
                                    <legend><?php _e('Minified results title'); ?></legend>
                                    <div class="infldset">
                                        <p><?php _e('Minified results description'); ?></p>
                                        <table>
<?php
foreach ($themes_state as $key => $theme):
    // Check if destination folder exists
    if (!$theme['directory']) {
        $destination = '<span class="text-error">&times; '.__('Destination').' : '.__('Could not create destination directory').'</span>';
    } else {
        $destination = '<span class="text-success">&#10003; '.__('Destination').' : '.__('Destination directory valid').'</span>';
    }
    // Check if a stylesheet was modified since last minification
    if (!$theme['stylesheets']) {
        $stylesState = '<span class="text-error">&times; '.__('Stylesheets').' : '.__('No minified styles').'</span>';
    } else {
        $stylesState = ($theme['stylesheets'] > $last_modified_style)
            ? '<span class="text-success">&#10003; '.__('Stylesheets').' : '.sprintf(__('Minified styles up to date'), Utils::format_time($theme['stylesheets'])).'</span>'
            : '<span class="text-warning">&#9888; '.__('Stylesheets').' : '.sprintf(__('Minified styles need update'), Utils::format_time($theme['stylesheets']), Utils::format_time($last_modified_style)).'</span>';
    }
    // Check if a javascript was modified since last minification
    if (!$theme['scripts']) {
        $scriptsState = '<span class="text-error">&times; '.__('Javascripts').' : '.__('No minified scripts').'</span>';
    } else {
        $scriptsState = ($theme['scripts'] > $last_modified_script)
            ? '<span class="text-success">&#10003; '.__('Javascripts').' : '.sprintf(__('Minified scripts up to date'), Utils::format_time($theme['scripts'])).'</span>'
            : '<span class="text-warning">&#9888; '.__('Javascripts').' : '.sprintf(__('Minified scripts need update'), Utils::format_time($theme['scripts']), Utils::format_time($last_modified_script)).'</span>';
    }
?>
            								<tr>
                                                <th scope="row"><?= Utils::escape($key) ?></th>
                                                <td><?= $destination . $stylesState . $scriptsState; ?></td>
                                            </tr>
<?php endforeach; ?>
                                        </table>
                                    </div>
                                </fieldset>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="clearer"></div>
            </div>
