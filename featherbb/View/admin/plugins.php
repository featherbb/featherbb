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

Container::get('hooks')->fire('view.admin.plugins.start');
?>

<div class="block">
    <h2>Plugins</h2>
    <div class="box">
        <div class="inbox">
            <table class="table">
                <caption><?php _e('Available plugins') ?></caption>
                <thead>
                    <tr>
                        <th><?php _e('Extension') ?></th>
                        <th><?php _e('Description') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($availablePlugins as $plugin) : ?>
                        <tr>
                            <td>
                                <strong><?= $plugin->title; ?></strong> <small><?= $plugin->version; ?></small>
                                <div class="plugin-actions">
                                    <?php if (in_array($plugin->name, $activePlugins)) { ?>
                                        <a href="<?= Router::pathFor('deactivatePlugin', ['name' => $plugin->name]) ?>"><?php _e('Deactivate') ?></a>
                                    <?php } else { ?>
                                        <a href="<?= Router::pathFor('activatePlugin', ['name' => $plugin->name]) ?>"><?php _e('Activate') ?></a> <br>
                                        <a href="<?= Router::pathFor('uninstallPlugin', ['name' => $plugin->name]) ?>"><?php _e('Uninstall') ?></a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <?= $plugin->description; ?>
                                <div class="plugin-details">
                                    By <?= $plugin->author->name; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align:right"><?= count($availablePlugins) ?> éléments</p>
        </div>
        <div class="inbox">
            <table class="table">
                <caption><?php _e('Available plugins') ?></caption>
                <thead>
                <tr>
                    <th><?php _e('Extension') ?></th>
                    <th><?php _e('Description') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($officialPlugins as $plugin) : ?>
                    <tr>
                        <td>
                            <strong><?= $plugin->title; ?></strong> <small><?= $plugin->version; ?></small>
                            <div class="plugin-actions">
                                <a href="<?= Router::pathFor('downloadPlugin', ['name' => $plugin->name, 'version' => $plugin->version]) ?>">Download</a>
                            </div>
                        </td>
                        <td>
                            <?= $plugin->description; ?>
                            <div class="plugin-details">
                                By <?= $plugin->author->name; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align:right"><?= count($officialPlugins) ?> éléments</p>
        </div>
    </div>
</div>

    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.plugins.end');
