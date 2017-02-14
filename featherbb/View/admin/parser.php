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

Container::get('hooks')->fire('view.admin.parser.start');
?>

    <div class="blockform">
        <h2><span><?= __( 'Parser head') ?></span></h2>
        <div class="box">
            <form method="post" action="<?= Router::pathFor('adminParser') ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>">
                <input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <p class="submittop">
                    <input type="submit" name="save" value="<?= __('Save changes') ?>" />
                </p>
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Smilies subhead') ?></legend>
                        <div class="infldset">
                            <table cellspacing="0">
                                <thead>
                                <tr>
                                    <th scope="col"><?= __('smiley_text_label') ?></th>
                                    <th scope="col"><?= __('smiley_file_label') ?></th>
                                    <th scope="col"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $i = 0;
                                var_dump($smilies);
                                foreach ($smilies as $key => $value) {
                                    ?>
                                    <tr>
                                        <td><input type="text" name="smiley_text[<?php echo($i);
                                            ?>]" value="<?php echo(Utils::escape($key));
                                            ?>" size="20" maxlength="80" /></td>
                                        <td>
                                            <select name="smiley_file[<?php echo($i);
                                            ?>]">
                                                <?php
                                                foreach ($smiley_files as $file) {
                                                    if ($file === $value) {
                                                        echo("\t\t\t\t\t\t\t\t\t\t\t<option selected=\"selected\">" . $file . "</option>\n");
                                                    } else {
                                                        echo("\t\t\t\t\t\t\t\t\t\t\t<option>" . $file . "</option>\n");
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </td>
                                        <td>
                                            <img src="<?= $urlBase.$value ?>">
                                        </td>
                                    </tr>
                                    <?php
                                    $i++;
                                }
                                ?>
                                <tr>
                                    <td>
                                        <input type="text" name="smiley_text[<?php echo($i); ?>]" value="" size="20" maxlength="80" /><br />
                                        <?= __('New smiley text') ?>
                                    </td>
                                    <td>
                                        <select name="smiley_file[<?php echo($i); ?>]">
                                            <option selected="selected"><?= __('Select new file') ?></option>
                                            <?php
                                            foreach ($smiley_files as $file) {
                                                echo("\t\t\t\t\t\t\t\t\t\t\t<option>" . $file . "</option>\n");
                                            }
                                            ?>
                                        </select><br /><?= __('New smiley image') ?>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= __('smiley_upload') ?></th>
                                    <?php if (ini_get('file_uploads')) {
                                        ?>
                                        <td><input type="hidden" name="MAX_FILE_SIZE" value="<?= ForumSettings::get('o_avatars_size') ?>" />
                                            <input type="hidden" name="form_sent" value="1" />
                                            <input type="file" name="new_smiley" id="upload_smiley" /></td>
                                        <td><input type="submit" name="upload" value="<?= __('upload_button') ?>" /></td>
                                        <?php
                                    } else {
                                        ?>
                                        <td colspan="2"><?= __('upload_off') ?></td>
                                        <?php
                                    } ?>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </fieldset>
                </div>


                <p class="submitend">
                    <input type="submit" name="save" value="<?= __('Save changes') ?>" />
                </p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
    </div>

<?php
Container::get('hooks')->fire('view.admin.parser.end');
