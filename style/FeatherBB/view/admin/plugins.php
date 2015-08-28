<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}
?>
<div class="block">
    <h2>Plugins</h2>
    <div class="box">
        <div class="inbox">
            <table class="table">
                <caption>The following plugins are available</caption>
                <thead>
                    <tr>
                        <th>Extension</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pluginsList as $class => $plugin) : ?>
                        <tr>
                            <td>
                                <strong><?= $plugin->getName(); ?></strong>
                                <div class="plugin-actions">
                                    <a href="<?= get_link('/admin/plugins/activate?plugin='.$class) ?>">Activate</a>

                                </div>
                            </td>
                            <td>
                                <?= $plugin->getDescription(); ?>
                                <div class="plugin-details">
                                    Version <?= $plugin->getVersion(); ?> |
                                    By <?= $plugin->getAuthor(); ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align:right"><?= count($pluginsList) ?> éléments</p>
        </div>
    </div>
</div>

	<div class="clearer"></div>
</div>
