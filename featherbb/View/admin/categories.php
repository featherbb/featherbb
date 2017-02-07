<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.categories.start');
?>

    <div class="blockform">
        <h2><span><?= __('Add categories head') ?></span></h2>
        <div class="box">
            <form method="post" action="<?= Router::pathFor('addCategory') ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Add categories subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Add category label') ?><div><input type="submit" value="<?= __('Add new submit') ?>" tabindex="2" /></div></th>
                                    <td>
                                        <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                                        <input type="text" name="cat_name" size="35" maxlength="80" tabindex="1" />
                                        <span><?php printf(__('Add category help'), '<a href="'.Router::pathFor('adminForums').'">'.__('Forums').'</a>') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
            </form>
        </div>

<?php if (!empty($cat_list)): ?>        <h2 class="block2"><span><?= __('Delete categories head') ?></span></h2>
        <div class="box">
            <form method="post" action="<?= Router::pathFor('deleteCategory') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Delete categories subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop">
                                <tr>
                                    <th scope="row"><?= __('Delete category label') ?><div><input type="submit" value="<?= __('Delete') ?>" tabindex="4" /></div></th>
                                    <td>
                                        <select name="cat_to_delete" tabindex="3">
<?php

    foreach ($cat_list as $cur_cat) {
        echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'">'.Utils::escape($cur_cat['cat_name']).'</option>'."\n";
    }

?>
                                        </select>
                                        <span style="color: red;"><input type="checkbox" name="disclaimer" value="1"> <?= __('Delete category disclaimer') ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </div>
            </form>
        </div>
<?php endif; ?>

<?php if (!empty($cat_list)): ?>        <h2 class="block2"><span><?= __('Edit categories head') ?></span></h2>
        <div class="box">
            <form method="post" action="<?= Router::pathFor('editCategory') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Edit categories subhead') ?></legend>
                        <div class="infldset">
                            <table id="categoryedit">
                            <thead>
                                <tr>
                                    <th class="tcl" scope="col"><?= __('Category name label') ?></th>
                                    <th scope="col"><?= __('Category position label') ?></th>
                                </tr>
                            </thead>
                            <tbody>
<?php

    foreach ($cat_list as $cur_cat) {
        ?>
                                <tr>
                                    <td class="tcl"><input type="text" name="cat[<?= $cur_cat['id'] ?>][name]" value="<?= Utils::escape($cur_cat['cat_name']) ?>" size="35" maxlength="80" /></td>
                                    <td><input type="text" name="cat[<?= $cur_cat['id'] ?>][order]" value="<?= $cur_cat['disp_position'] ?>" size="3" maxlength="3" /></td>
                                </tr>
<?php

    }

?>
                            </tbody>
                            </table>
                            <div class="fsetsubmit"><input type="submit" value="<?= __('Update') ?>" /></div>
                        </div>
                    </fieldset>
                </div>
            </form>
        </div>
<?php endif; ?>    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.categories.end');
