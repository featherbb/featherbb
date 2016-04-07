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

use FeatherBB\Core\Utils;

Container::get('hooks')->fire('view.admin.plugins.start');
?>

                <div class="block">
                    <h2><?php _e('Installed plugins'); ?> (<?= count($availablePlugins); ?>)</h2>
                    <div class="box">
                        <div class="inbox">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="plugin-name"><?php _e('Extension') ?></th>
                                        <th class="plugin-version"><?php _e('Version') ?></th>
                                        <th class="plugin-description"><?php _e('Description') ?></th>
                                        <th class="plugin-status"><?php _e('Active') ?>*</th>
                                        <th class="plugin-actions"><?php _e('Action') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
<?php foreach ($availablePlugins as $plugin) : ?>
                                    <tr<?php if (!in_array($plugin->name, $activePlugins)) echo ' class="plugin-deactivated"'; ?>>
                                        <td class="plugin-name">
                                            <strong><?= Utils::escape($plugin->title); ?></strong>
                                            <p class="plugin-details"><?php _e('Author') ?> <a href="http://marketplace.featherbb.org/plugins/author/<?= Utils::escape($plugin->author->name); ?>" target="_blank"><?= Utils::escape($plugin->author->name); ?></a></p>
                                        </td>
                                        <td class="plugin-version"><?= Utils::escape($plugin->version); ?></td>
                                        <td class="plugin-description"><?= Utils::escape($plugin->description); ?></td>
                                        <td class="plugin-status">
<?php if (in_array($plugin->name, $activePlugins)) { ?>
                                            <a href="<?= Router::pathFor('deactivatePlugin', ['name' => Utils::escape($plugin->name)]) ?>" title="<?php _e('Deactivate') ?>" class="text-success">&checkmark;</a>
<?php } else { ?>
                                            <a href="<?= Router::pathFor('activatePlugin', ['name' => Utils::escape($plugin->name)]) ?>" title="<?php _e('Activate') ?>" class="text-error">&#8416;</a>
<?php } ?>
                                        </td>
                                        <td class="plugin-actions">
<?php if (in_array($plugin->name, $activePlugins)) { ?>
                                            -
<?php } else { ?>
                                            <a href="<?= Router::pathFor('uninstallPlugin', ['name' => Utils::escape($plugin->name)]) ?>" onclick="return confirm('<?php _e('Uninstall warning') ?>')"><?php _e('Uninstall') ?></a>
<?php } ?>
                                        </td>
                                    </tr>
<?php endforeach; ?>
                                </tbody>
                            </table>
                            <tfoot><strong>* <?php _e('Toggle active state'); ?></strong></tfoot>
                        </div>
                    </div>

                    <h2 class="block2"><?php _e('Upload plugin'); ?></h2>
                    <div class="box">
                        <div class="inbox">
                            <form id="upload-plugin" method="post" enctype="multipart/form-data" action="">
                                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                                <div class="inform">
                                    <div class="infldset">
                                        <input type="hidden" name="form_sent" value="1" />
                                        <input type="hidden" name="MAX_FILE_SIZE" value="10240" />
                                        <input name="req_file" type="file" size="40" required="required" />
                                    </div>
                                </div>
                                <p class="buttons" style="float:right"><input type="submit" name="upload" value="<?php _e('Upload'); ?>" /></p>
                            </form>
                        </div>
                    </div>

                    <h2 class="block2"><?php _e('Official plugins'); ?> (<?= count($officialPlugins); ?>)</h2>
                    <div class="box">
                        <div class="inbox">
                            <table class="table">
                                <caption><?php _e('Official plugins description') ?></caption>
                                <thead>
                                <tr>
                                    <th class="plugin-name"><?php _e('Extension') ?></th>
                                    <th class="plugin-version"><?php _e('Version') ?></th>
                                    <th class="plugin-description"><?php _e('Description') ?></th>
                                    <th class="plugin-actions"><?php _e('Action') ?></th>
                                </tr>
                                </thead>
                                <tbody>
<?php foreach ($officialPlugins as $plugin) : ?>
                                    <tr>
                                        <td class="plugin-name">
                                            <strong><?= Utils::escape($plugin->title); ?></strong>
                                            <p class="plugin-details"><?php _e('Author') ?> <a href="http://marketplace.featherbb.org/plugins/author/<?= Utils::escape($plugin->author->name); ?>" target="_blank"><?= Utils::escape($plugin->author->name); ?></a></p>
                                        </td>
                                        <td class="plugin-version"><?= Utils::escape($plugin->version); ?></td>
                                        <td class="plugin-description"><?= Utils::escape($plugin->description); ?></td>
                                        <td class="plugin-actions">
                                            <a href="<?= Router::pathFor('downloadPlugin', ['name' => Utils::escape($plugin->name), 'version' => intval($plugin->version)]) ?>"><?php _e('Download'); ?></a>
                                        </td>
                                    </tr>
<?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="clearer"></div>
            </div>
<?php
Container::get('hooks')->fire('view.admin.plugins.end');
