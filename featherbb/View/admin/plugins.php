<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}
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
                                        <a href="<?= $feather->urlFor('deactivatePlugin', ['name' => $plugin->name]) ?>"><?php _e('Deactivate') ?></a>
                                    <?php } else { ?>
                                        <a href="<?= $feather->urlFor('activatePlugin', ['name' => $plugin->name]) ?>"><?php _e('Activate') ?></a>
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
    </div>
</div>

	<div class="clearer"></div>
</div>
