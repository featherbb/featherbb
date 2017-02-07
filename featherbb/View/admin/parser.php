<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
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
        <h2><span><?= _e( 'Parser head') ?></span></h2>
        <div class="box">
            <form method="post" action="<?= Router::pathFor('adminParser') ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <p class="submittop">
                    <input type="submit" name="save" value="<?= _e('Save changes') ?>" />
                    <input type="submit" name="reset" value="<?= _e('reset defaults') ?>" />
                </p>
                <div class="inform">
                    <input type="hidden" name="form_sent" value="1" />
                    <fieldset>
                        <legend><?= _e('Config subhead') ?></legend>
                        <div class="infldset">
                            <table class="aligntop" cellspacing="0">
                                <tr>
                                    <th scope="row"><?= _e('textile') ?></th>
                                    <td colspan="2">
                                        <input type="radio" name="config[textile]" value="1"<?php if ($config['textile']) {
    echo ' checked="checked"';
} ?> /> <strong><?= __('Yes') ?></strong>   <input type="radio" name="config[textile]" value="0"<?php if (!$config['textile']) {
    echo ' checked="checked"';
} ?> /> <strong><?= __('No') ?></strong>
                                    </td>
                                    <td>
                                        <span><?= _e('textile help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= _e('quote_links') ?></th>
                                    <td colspan="2">
                                        <input type="radio" name="config[quote_links]" value="1"<?php if ($config['quote_links']) {
    echo ' checked="checked"';
} ?> /> <strong><?= __('Yes') ?></strong>   <input type="radio" name="config[quote_links]" value="0"<?php if (!$config['quote_links']) {
    echo ' checked="checked"';
} ?> /> <strong><?= __('No') ?></strong>
                                    </td>
                                    <td>
                                        <span><?= _e('quote_links help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= _e('quote_imgs') ?></th>
                                    <td colspan="2">
                                        <input type="radio" name="config[quote_imgs]" value="1"<?php if ($config['quote_imgs']) {
    echo ' checked="checked"';
} ?> /> <strong><?= __('Yes') ?></strong>   <input type="radio" name="config[quote_imgs]" value="0"<?php if (!$config['quote_imgs']) {
    echo ' checked="checked"';
} ?> /> <strong><?= __('No') ?></strong>
                                    </td>
                                    <td>
                                        <span><?= _e('quote_imgs help') ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= _e('click_imgs') ?></th>
                                    <td colspan="2">
                                        <input type="radio" name="config[click_imgs]" value="1"<?php if ($config['click_imgs']) {
    echo ' checked="checked"';
} ?> /> <strong><?= __('Yes') ?></strong>   <input type="radio" name="config[click_imgs]" value="0"<?php if (!$config['click_imgs']) {
    echo ' checked="checked"';
} ?> /> <strong><?= __('No') ?></strong>
                                    </td>
                                    <td>
                                        <span><?= _e('click_imgs help') ?></span>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row"><?= _e('valid_imgs') ?></th>
                                    <td colspan="2">
                                        <input type="radio" name="config[valid_imgs]" value="1"<?php if ($config['valid_imgs']) {
    echo ' checked="checked"';
} if (!ini_get('allow_url_fopen')) {
    echo(' disabled="disabled" title="'._e('unavailable').'"');
} ?> /> <strong><?= __('Yes') ?></strong>
                                        <input type="radio" name="config[valid_imgs]" value="0"<?php if (!$config['valid_imgs']) {
    echo ' checked="checked"';
} if (!ini_get('allow_url_fopen')) {
    echo(' disabled="disabled" title="'._e('unavailable').'"');
} ?> /> <strong><?= __('No') ?></strong>
                                    </td>
                                    <td><?= _e('valid_imgs help') ?></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= _e('max_size') ?></th>
                                    <td colspan="2">
                                        <input type="text" name="config[max_size]" size="10" maxlength="8" value="<?php echo($config['max_size'])?>"<?php if (!ini_get('allow_url_fopen')) {
    echo(' disabled="disabled" title="'._e('unavailable').'"');
} ?> />
                                    </td>
                                    <td><span><?= _e('max_size help') ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= _e('def_xy') ?></th>
                                    <td>
                                        <input type="text" name="config[def_width]" size="5" maxlength="5" value="<?= $config['def_width'] ?>" /> X:Width
                                    </td>
                                    <td>
                                        <input type="text" name="config[def_height]" size="5" maxlength="5" value="<?= $config['def_height'] ?>" /> Y:Height
                                    </td>
                                    <td>
                                        <span><?= _e('def_xy help') ?></span>
                                    </td>
                                </tr>


                                <tr>
                                    <th scope="row"><?= _e('max_xy') ?></th>
                                    <td>
                                        <input type="text" name="config[max_width]" size="5" maxlength="5" value="<?= $config['max_width'] ?>" /> X:Width
                                    </td>
                                    <td>
                                        <input type="text" name="config[max_height]" size="5" maxlength="5" value="<?= $config['max_height'] ?>" /> Y:Height
                                    </td>
                                    <td><?= _e('max_xy help') ?></td>
                                </tr>

                                <tr>
                                    <th scope="row"><?= _e('smiley_size') ?></th>
                                    <td colspan=2>
                                        <input type="text" name="config[smiley_size]" size="5" maxlength="5" value="<?= $config['smiley_size'] .'%' ?>" />
                                    </td>
                                    <td>
                                        <span><?= _e('smiley_size help') ?></span>
                                    </td>
                                </tr>

                            </table>
                        </div>
                    </fieldset>
                </div>
                <div class="inform">
                    <fieldset>
                        <legend><?= _e('Smilies subhead') ?></legend>
                        <div class="infldset">
                            <table cellspacing="0">
                            <thead>
                                <tr>
                                    <th scope="col"><?= _e('smiley_text_label') ?></th>
                                    <th scope="col"><?= _e('smiley_file_label') ?></th>
                                    <th scope="col">:)</th>
                                </tr>
                            </thead>
                            <tbody>
<?php
    foreach ($smilies as $key => $value) {
        $i++;
        $oldfile = $value['file'];
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
            if ($file === $oldfile) {
                echo("\t\t\t\t\t\t\t\t\t\t\t<option selected=\"selected\">" . $file . "</option>\n");
            } else {
                echo("\t\t\t\t\t\t\t\t\t\t\t<option>" . $file . "</option>\n");
            }
        }
        ?>
                                        </select>
                                    </td>
                                    <td>
                                        <?php echo($value['html']);
        ?>
                                    </td>
                                </tr>
<?php

    }
?>
                                <tr>
                                    <td>
                                        <input type="text" name="smiley_text[<?php echo(++$i); ?>]" value="" size="20" maxlength="80" /><br />
                                        <?= _e('New smiley text') ?>
                                    </td>
                                    <td>
                                        <select name="smiley_file[<?php echo($i); ?>]">
                                            <option selected="selected"><?= _e('Select new file') ?></option>
<?php
        foreach ($smiley_files as $file) {
            echo("\t\t\t\t\t\t\t\t\t\t\t<option>" . $file . "</option>\n");
        }
?>
                                        </select><br /><?= _e('New smiley image') ?>
                                    </td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?= _e('smiley_upload') ?></th>
 <?php if (ini_get('file_uploads')) {
    ?>
                                    <td><input type="hidden" name="MAX_FILE_SIZE" value="<?= ForumSettings::get('o_avatars_size') ?>" />
                                        <input type="file" name="new_smiley" id="upload_smiley" /></td>
                                    <td><input type="submit" name="upload" value="<?= _e('upload_button') ?>" /></td>
<?php 
} else {
    ?>
                                    <td colspan="2"><?= _e('upload_off') ?></td>
<?php 
} ?>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                    </fieldset>
                </div>



                <div class="inform">
                    <fieldset>
                        <legend><?= _e('BBCodes subhead') ?></legend>
                        <div class="infldset">
                            <table cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="tcl" scope="col"><?= _e('tagname_label') ?></th>
                                    <th class="tc3" scope="col"><?= _e('in_post_label') ?></th>
                                    <th class="hidehead" scope="col"><?= _e('in_sig_label') ?></th>
                                    <th class="tc2" scope="col"><?= _e('depth_max') ?></th>
                                </tr>
                            </thead>
                            <tbody>
<?php
foreach ($bbcd as $tagname => $tagdata) {
    if ($tagname == '_ROOT_') {
        continue;
    } // Skip last pseudo-tag
    $title = isset($tag_summary[$tagname]) ? $tag_summary[$tagname] : ''; ?>
                                <tr>
                                    <th scope="row" title="<?= _e($title) ?>"><?php echo('['. $tagname .']') ?></th>
                                    <td>
                                        <input type="radio" name="<?php echo($tagname) ?>_in_post" value="1"<?php if ($bbcd[$tagname]['in_post']) {
    echo ' checked="checked"';
}
    ?> /> <strong><?= __('Yes') ?></strong>   <input type="radio" name="<?php echo($tagname) ?>_in_post" value="0"<?php if (!$bbcd[$tagname]['in_post']) {
    echo ' checked="checked"';
}
    ?> /> <strong><?= __('No') ?></strong>
                                    </td>
                                    <td>
                                        <input type="radio" name="<?php echo($tagname) ?>_in_sig" value="1"<?php if ($bbcd[$tagname]['in_sig']) {
    echo ' checked="checked"';
}
    ?> /> <strong><?= __('Yes') ?></strong>   <input type="radio" name="<?php echo($tagname) ?>_in_sig" value="0"<?php if (!$bbcd[$tagname]['in_sig']) {
    echo ' checked="checked"';
}
    ?> /> <strong><?= __('No') ?></strong>
                                    </td>
                                    <td>
                                        <input type="text" size="10" name="<?php echo($tagname) ?>_depth_max" value="<?php echo($bbcd[$tagname]['depth_max']);
    ?>" <?php if ($tagdata['html_type'] === 'inline' || $tagdata['tag_type'] === 'hidden') {
    echo(' disabled="disabled" style="display: none;"');
}
    ?> />
                                    </td>
                                </tr>
<?php 
} ?>
                            </tbody>
                            </table>
                        </div>
                    </fieldset>
                </div>


                <p class="submitend">
                    <input type="submit" name="save" value="<?= _e('Save changes') ?>" />
                    <input type="submit" name="reset" value="<?= _e('reset defaults') ?>" />
                </p>
            </form>
        </div>
    </div>
    <div class="clearer"></div>
</div>

<?php
Container::get('hooks')->fire('view.admin.parser.end');
