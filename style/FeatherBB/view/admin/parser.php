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

	<div class="blockform">
		<h2><span><?php echo $lang_admin_parser['Parser head'] ?></span></h2>
		<div class="box">
			<form method="post" action="<?php echo get_link('admin/parser/') ?>" enctype="multipart/form-data">
				<input type="hidden" name="<?php echo $csrf_key; ?>" value="<?php echo $csrf_token; ?>">
				<p class="submittop">
					<input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" />
					<input type="submit" name="reset" value="<?php echo $lang_admin_parser['reset defaults'] ?>" />
				</p>
				<div class="inform">
					<input type="hidden" name="form_sent" value="1" />
					<fieldset>
						<legend><?php echo $lang_admin_parser['Config subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop" cellspacing="0">
								<tr>
									<th scope="row"><?php echo $lang_admin_parser['textile'] ?></th>
									<td colspan="2">
										<input type="radio" name="config[textile]" value="1"<?php if ($config['textile']) {
    echo ' checked="checked"';
} ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="config[textile]" value="0"<?php if (!$config['textile']) {
    echo ' checked="checked"';
} ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<span><?php echo $lang_admin_parser['textile help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_parser['quote_links'] ?></th>
									<td colspan="2">
										<input type="radio" name="config[quote_links]" value="1"<?php if ($config['quote_links']) {
    echo ' checked="checked"';
} ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="config[quote_links]" value="0"<?php if (!$config['quote_links']) {
    echo ' checked="checked"';
} ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<span><?php echo $lang_admin_parser['quote_links help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_parser['quote_imgs'] ?></th>
									<td colspan="2">
										<input type="radio" name="config[quote_imgs]" value="1"<?php if ($config['quote_imgs']) {
    echo ' checked="checked"';
} ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="config[quote_imgs]" value="0"<?php if (!$config['quote_imgs']) {
    echo ' checked="checked"';
} ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<span><?php echo $lang_admin_parser['quote_imgs help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_parser['click_imgs'] ?></th>
									<td colspan="2">
										<input type="radio" name="config[click_imgs]" value="1"<?php if ($config['click_imgs']) {
    echo ' checked="checked"';
} ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="config[click_imgs]" value="0"<?php if (!$config['click_imgs']) {
    echo ' checked="checked"';
} ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<span><?php echo $lang_admin_parser['click_imgs help'] ?></span>
									</td>
								</tr>

								<tr>
									<th scope="row"><?php echo $lang_admin_parser['valid_imgs'] ?></th>
									<td colspan="2">
										<input type="radio" name="config[valid_imgs]" value="1"<?php if ($config['valid_imgs']) {
    echo ' checked="checked"';
} if (!ini_get('allow_url_fopen')) {
    echo(' disabled="disabled" title="'. feather_escape($lang_admin_parser['unavailable']) .'"');
} ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>
										<input type="radio" name="config[valid_imgs]" value="0"<?php if (!$config['valid_imgs']) {
    echo ' checked="checked"';
} if (!ini_get('allow_url_fopen')) {
    echo(' disabled="disabled" title="'. feather_escape($lang_admin_parser['unavailable']) .'"');
} ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td><?php echo $lang_admin_parser['valid_imgs help'] ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_parser['max_size'] ?></th>
									<td colspan="2">
										<input type="text" name="config[max_size]" size="10" maxlength="8" value="<?php echo($config['max_size'])?>"<?php if (!ini_get('allow_url_fopen')) {
    echo(' disabled="disabled" title="'. feather_escape($lang_admin_parser['unavailable']) .'"');
} ?> />
									</td>
									<td><span><?php echo $lang_admin_parser['max_size help'] ?></span></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_parser['def_xy'] ?></th>
									<td>
										<input type="text" name="config[def_width]" size="5" maxlength="5" value="<?php echo $config['def_width'] ?>" /> X:Width
									</td>
									<td>
										<input type="text" name="config[def_height]" size="5" maxlength="5" value="<?php echo $config['def_height'] ?>" /> Y:Height
									</td>
									<td>
										<span><?php echo $lang_admin_parser['def_xy help'] ?></span>
									</td>
								</tr>


								<tr>
									<th scope="row"><?php echo $lang_admin_parser['max_xy'] ?></th>
									<td>
										<input type="text" name="config[max_width]" size="5" maxlength="5" value="<?php echo $config['max_width'] ?>" /> X:Width
									</td>
									<td>
										<input type="text" name="config[max_height]" size="5" maxlength="5" value="<?php echo $config['max_height'] ?>" /> Y:Height
									</td>
									<td><?php echo $lang_admin_parser['max_xy help'] ?></td>
								</tr>

								<tr>
									<th scope="row"><?php echo $lang_admin_parser['smiley_size'] ?></th>
									<td colspan=2>
										<input type="text" name="config[smiley_size]" size="5" maxlength="5" value="<?php echo $config['smiley_size'] .'%' ?>" />
									</td>
									<td>
										<span><?php echo $lang_admin_parser['smiley_size help'] ?></span>
									</td>
								</tr>

							</table>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_parser['Smilies subhead'] ?></legend>
						<div class="infldset">
							<table cellspacing="0">
							<thead>
								<tr>
									<th scope="col"><?php echo $lang_admin_parser['smiley_text_label'] ?></th>
									<th scope="col"><?php echo $lang_admin_parser['smiley_file_label'] ?></th>
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
        ?>]" value="<?php echo(feather_escape($key));
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
									<td><input type="text" name="smiley_text[<?php echo(++$i); ?>]" value="" size="20" maxlength="80" /><br />New smiley text</td>
									<td>
										<select name="smiley_file[<?php echo($i); ?>]">
											<option selected="selected">select new file</option>
<?php
        foreach ($smiley_files as $file) {
            echo("\t\t\t\t\t\t\t\t\t\t\t<option>" . $file . "</option>\n");
        }
?>
										</select><br /><?php echo($lang_admin_parser['New smiley image']); ?>
									</td>
									<td></td>
								</tr>
								<tr>
									<th scope="row"><?php echo($lang_admin_parser['smiley_upload']); ?></th>
 <?php if (ini_get('file_uploads')) {
    ?>
									<td><input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $feather_config['o_avatars_size'] ?>" />
										<input type="file" name="new_smiley" id="upload_smiley" /></td>
									<td><input type="submit" name="upload" value="<?php echo($lang_admin_parser['upload_button']);
    ?>" /></td>
<?php 
} else {
    ?>
									<td colspan="2"><?php echo($lang_admin_parser['upload_off']);
    ?></td>
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
						<legend><?php echo $lang_admin_parser['BBCodes subhead'] ?></legend>
						<div class="infldset">
							<table cellspacing="0">
							<thead>
								<tr>
									<th class="tcl" scope="col"><?php echo $lang_admin_parser['tagname_label'] ?></th>
									<th class="tc3" scope="col"><?php echo $lang_admin_parser['in_post_label'] ?></th>
									<th class="hidehead" scope="col"><?php echo $lang_admin_parser['in_sig_label'] ?></th>
									<th class="tc2" scope="col"><?php echo $lang_admin_parser['depth_max'] ?></th>
								</tr>
							</thead>
							<tbody>
<?php
foreach ($bbcd as $tagname => $tagdata) {
    if ($tagname == '_ROOT_') {
        continue;
    } // Skip last pseudo-tag
    $title = isset($lang_admin_parser['tag_summary'][$tagname]) ?
                $lang_admin_parser['tag_summary'][$tagname] : '';
    ?>
								<tr>
									<th scope="row" title="<?php echo($title);
    ?>"><?php echo('['. $tagname .']') ?></th>
									<td>
										<input type="radio" name="<?php echo($tagname) ?>_in_post" value="1"<?php if ($bbcd[$tagname]['in_post']) {
    echo ' checked="checked"';
}
    ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="<?php echo($tagname) ?>_in_post" value="0"<?php if (!$bbcd[$tagname]['in_post']) {
    echo ' checked="checked"';
}
    ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<input type="radio" name="<?php echo($tagname) ?>_in_sig" value="1"<?php if ($bbcd[$tagname]['in_sig']) {
    echo ' checked="checked"';
}
    ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="<?php echo($tagname) ?>_in_sig" value="0"<?php if (!$bbcd[$tagname]['in_sig']) {
    echo ' checked="checked"';
}
    ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
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
					<input type="submit" name="save" value="<?php echo $lang_admin_common['Save changes'] ?>" />
					<input type="submit" name="reset" value="<?php echo $lang_admin_parser['reset defaults'] ?>" />
				</p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>