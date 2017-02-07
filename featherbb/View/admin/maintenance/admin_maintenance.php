<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.maintenance.admin_maintenance.start');
?>

    <div class="blockform">
        <h2><span><?= __('Maintenance head') ?></span></h2>
        <div class="box">
            <form method="get" action="<?= Router::pathFor('adminMaintenance') ?>">
                <div class="inform">
                    <input type="hidden" name="action" value="rebuild" />
                    <fieldset>
                        <legend><?= __('Rebuild index subhead') ?></legend>
                        <div class="infldset">
                            <p><?php printf(__('Rebuild index info'), '<a href="'.Router::pathFor('adminOptions').'#maintenance">'.__('Maintenance mode').'</a>') ?></p>
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Posts per cycle label') ?></th>
                                    <td>
                                        <input type="text" name="i_per_page" size="7" maxlength="7" value="300" tabindex="1" />
                                        <span><?= __('Posts per cycle help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Starting post label') ?></th>
                                    <td>
                                        <input type="text" name="i_start_at" size="7" maxlength="7" value="<?php echo(isset($first_id)) ? $first_id : 0 ?>" tabindex="2" />
                                        <span><?= __('Starting post help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Empty index label') ?></th>
                                    <td class="inputadmin">
                                        <label><input type="checkbox" name="i_empty_index" value="1" tabindex="3" checked="checked" />&#160;&#160;<?= __('Empty index help') ?></label>
                                    </td>
                                </tr>
                            </table>
                            <p class="topspace"><?= __('Rebuild completed info') ?></p>
                            <div class="fsetsubmit"><input type="submit" name="rebuild_index" value="<?= __('Rebuild index') ?>" tabindex="4" /></div>
                        </div>
                    </fieldset>
                </div>
            </form>

            <form method="post" action="<?= Router::pathFor('adminMaintenance') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                    <input type="hidden" name="action" value="prune" />
                    <fieldset>
                        <legend><?= __('Prune subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Days old label') ?></th>
                                    <td>
                                        <input type="text" name="req_prune_days" size="3" maxlength="3" tabindex="5" required="required" />
                                        <span><?= __('Days old help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Prune sticky label') ?></th>
                                    <td>
                                        <label class="conl"><input type="radio" name="prune_sticky" value="1" tabindex="6" checked="checked" />&#160;<strong><?= __('Yes') ?></strong></label>
                                        <label class="conl"><input type="radio" name="prune_sticky" value="0" />&#160;<strong><?= __('No') ?></strong></label>
                                        <span class="clearb"><?= __('Prune sticky help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('Prune from label') ?></th>
                                    <td>
                                        <select name="prune_from" tabindex="7">
                                            <option value="all"><?= __('All forums') ?></option>
                                                <?= $categories; ?>
                                            </optgroup>
                                        </select>
                                        <span><?= __('Prune from help') ?></span>
                                    </td>
                                </tr>
                            </table>
                            <p class="topspace"><?php printf(__('Prune info'), '<a href="'.Router::pathFor('adminOptions').'#maintenance">'.__('Maintenance mode').'</a>') ?></p>
                            <div class="fsetsubmit"><input type="submit" name="prune" value="<?= __('Prune') ?>" tabindex="8" /></div>
                        </div>
                    </fieldset>
                </div>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.maintenance.admin_maintenance.end');
