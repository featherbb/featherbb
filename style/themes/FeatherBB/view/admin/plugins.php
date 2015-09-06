<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Error;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}
?>

<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="<?php echo $feather->url->get('admin/index/') ?>"><?php _e('Admin').' '.__('Index') ?></a></li>
			<li><span>»&#160;</span><a href="<?php echo $feather->url->get('admin/users/') ?>"><?php _e('Users') ?></a></li>
			<li><span>»&#160;</span><strong><?php _e('Results head') ?></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink"><?php echo $paging_links ?></p>
		</div>
		<div class="clearer"></div>
	</div>
</div>

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
                    <?php foreach ($pluginsList as $plugin => $class) : ?>
                        <tr>
                            <td>
                                <strong><?= $class::$name; ?></strong>
                                <div class="plugin-actions">
                                    <?php if (array_key_exists($class, $activePlugins)) { ?>
                                        <a href="<?= Url::get('/admin/plugins/deactivate?plugin='.$class) ?>">Deactivate</a>
                                    <?php } else { ?>
                                        <a href="<?= Url::get('/admin/plugins/activate?plugin='.$class) ?>">Activate</a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <?= $class::$description; ?>
                                <div class="plugin-details">
                                    Version <?= $class::$version; ?> |
                                    By <?= $class::$author; ?>
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
