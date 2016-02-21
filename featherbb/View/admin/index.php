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

Container::get('hooks')->fire('view.admin.index.start');
?>

    <div class="block">
        <h2><span><?php _e('Forum admin head') ?></span></h2>
        <div id="adintro" class="box">
            <div class="inbox">
                <p><?php _e('Welcome to admin') ?></p>
                <ul>
                    <li><span><?php _e('Welcome 1') ?></span></li>
                    <li><span><?php _e('Welcome 2') ?></span></li>
                    <li><span><?php _e('Welcome 3') ?></span></li>
                    <li><span><?php _e('Welcome 4') ?></span></li>
                    <li><span><?php _e('Welcome 5') ?></span></li>
                    <li><span><?php _e('Welcome 6') ?></span></li>
                    <li><span><?php _e('Welcome 7') ?></span></li>
                    <li><span><?php _e('Welcome 8') ?></span></li>
                    <li><span><?php _e('Welcome 9') ?></span></li>
                </ul>
            </div>
        </div>

        <h2 class="block2"><span><?php _e('About head') ?></span></h2>
        <div id="adstats" class="box">
            <div class="inbox">
                <dl>
                    <dt><?php _e('FeatherBB version label') ?></dt>
                    <dd>
                        <?php printf(__('FeatherBB version data')."\n", ForumSettings::get('o_cur_version'), '<a href="'.Router::pathFor('adminAction', ['action' => 'check_upgrade']).'">'.__('Check for upgrade').'</a>') ?>
                    </dd>
                    <dt><?php _e('Server statistics label') ?></dt>
                    <dd>
                        <a href="<?= Router::pathFor('statistics') ?>"><?php _e('View server statistics') ?></a>
                    </dd>
                    <dt><?php _e('Support label') ?></dt>
                    <dd>
                        <a href="http://forums.featherbb.org"><?php _e('Forum label') ?></a> - <a href="http://gitter.im/featherbb/featherbb"><?php _e('IRC label') ?></a>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.index.end');
