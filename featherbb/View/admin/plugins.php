<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}
?>

<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="<?= $feather->urlFor('adminIndex') ?>"><?php _e('Admin').' '.__('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?= $feather->urlFor('adminPlugins') ?>"><strong><?php _e('Extension') ?></strong></a></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>

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
                    <?php foreach ($plugins as $plugin) : ?>
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
            <p style="text-align:right"><?= count($plugins) ?> éléments</p>
        </div>
    </div>
</div>

	<div class="clearer"></div>
</div>
