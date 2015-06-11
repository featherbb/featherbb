<?php

/**
 * Copyright (C) 2015 FeatherBB
 * Parser (C) 2011 Jeff Roberson (jmrware.com)
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if ($pun_user['g_id'] != PUN_ADMIN)
	message($lang_common['No permission']);

// Load the admin_parser.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_parser.php';

// This is where the parser data lives and breathes.
$cache_file = PUN_ROOT.'cache/cache_parser_data.php';

// If RESET button pushed, or no cache file, re-compile master bbcode source file.
if (isset($_POST['reset']) || !file_exists($cache_file)) {
	require_once(PUN_ROOT.'include/bbcd_source.php');
	require_once(PUN_ROOT.'include/bbcd_compile.php');
	redirect('admin_parser.php', $lang_admin_parser['reset_success']);
}

// Load the current BBCode $pd array from include/parser_data.inc.php.
require_once($cache_file);			// Fetch $pd compiled global regex data.
$bbcd = $pd['bbcd'];				// Local scratch copy of $bbcd.
$smilies = $pd['smilies'];			// Local scratch copy of $smilies.
$config = $pd['config'];			// Local scratch copy of $config.
$count = count($bbcd);

if (isset($_POST['form_sent']))
{
	confirm_referrer('admin_parser.php');

	// Upload new smiley image to img/smilies
	if (isset($_POST['upload']) && isset($_FILES['new_smiley']) && isset($_FILES['new_smiley']['error'])) {
		$f =& $_FILES['new_smiley'];
		switch($f['error']) {
		case 0: // 0: Successful upload.
			$name = str_replace(' ', '_', $f['name']);			// Convert spaces to underscoree.
			$name = preg_replace('/[^\w\-.]/S', '', $name);		// Weed out all unsavory filename chars.
			if (preg_match('/^[\w\-.]++$/', $name)) {			// If we have a valid filename?
				if (preg_match('%^image/%', $f['type'])) {		// If we have an image file type?
					if ($f['size'] > 0 && $f['size'] <= $pun_config['o_avatars_size']) {
						if (move_uploaded_file($f['tmp_name'], PUN_ROOT .'img/smilies/'. $name)) {
							redirect('admin_parser.php', $lang_admin_parser['upload success']);
						} else
						{ //  Error #1: 'Smiley upload failed. Unable to move to smiley folder.'.
							message($lang_admin_parser['upload_err_1']);
						}
					} else
					{ // Error #2: 'Smiley upload failed. File is too big.'
						message($lang_admin_parser['upload_err_2']);
					}
				} else
				{ // Error #3: 'Smiley upload failed. File type is not an image.'.
					message($lang_admin_parser['upload_err_3']);
				}
			} else
			{ // Error #4: 'Smiley upload failed. Bad filename.'
				message($lang_admin_parser['upload_err_4']);
			}
			break;
		case 1: // case 1 similar to case 2 so fall through...
		case 2: message($lang_admin_parser['upload_err_2']);	// File exceeds MAX_FILE_SIZE.
		case 3: message($lang_admin_parser['upload_err_5']);	// File only partially uploaded.
//		case 4: break; // No error. Normal response when this form element left empty
		case 4: message($lang_admin_parser['upload_err_6']);	// No filename.
		case 6: message($lang_admin_parser['upload_err_7']);	// No temp folder.
		case 7: message($lang_admin_parser['upload_err_8']);	// Cannot write to disk.
		default: message($lang_admin_parser['upload_err_9']);		// Generic/unknown error
		}
	}

	// Set new $config values:
	if (isset($_POST['config'])) {
		$pcfg =& $_POST['config'];

		if (isset($pcfg['textile'])) {
			if ($pcfg['textile'] == '1')	$config['textile'] = TRUE;
			else							$config['textile'] = FALSE;
		}
		if (isset($pcfg['quote_links'])) {
			if ($pcfg['quote_links'] == '1')	$config['quote_links'] = TRUE;
			else							$config['quote_links'] = FALSE;
		}
		if (isset($pcfg['quote_imgs'])) {
			if ($pcfg['quote_imgs'] == '1')	$config['quote_imgs'] = TRUE;
			else							$config['quote_imgs'] = FALSE;
		}
		if (isset($pcfg['valid_imgs'])) {
			if ($pcfg['valid_imgs'] == '1') $config['valid_imgs'] = TRUE;
			else							$config['valid_imgs'] = FALSE;
		}
		if (isset($pcfg['click_imgs'])) {
			if ($pcfg['click_imgs'] == '1') $config['click_imgs'] = TRUE;
			else							$config['click_imgs'] = FALSE;
		}
		if (isset($pcfg['syntax_style']) &&
				file_exists(PUN_ROOT .'bin/'. $pcfg['syntax_style'])) {
			$config['syntax_style'] = $pcfg['syntax_style'];
		}
		if (isset($pcfg['max_size']) && preg_match('/^\d++$/', $pcfg['max_size'])) {
			$config['max_size'] = (int)$pcfg['max_size'];
		}
		if (isset($pcfg['max_width']) && preg_match('/^\d++$/', $pcfg['max_width'])) {
			$config['max_width'] = (int)$pcfg['max_width']; // Limit default to maximum.
			if ($config['def_width'] > $config['max_width']) $config['def_width'] = $config['max_width'];
		}
		if (isset($pcfg['max_height']) && preg_match('/^\d++$/', $pcfg['max_height'])) {
			$config['max_height'] = (int)$pcfg['max_height']; // Limit default to maximum.
			if ($config['def_height'] > $config['max_height']) $config['def_height'] = $config['max_height'];
		}
		if (isset($pcfg['def_width']) && preg_match('/^\d++$/', $pcfg['def_width'])) {
			$config['def_width'] = (int)$pcfg['def_width']; // Limit default to maximum.
			if ($config['def_width'] > $config['max_width']) $config['def_width'] = $config['max_width'];
		}
		if (isset($pcfg['def_height']) && preg_match('/^\d++$/', $pcfg['def_height'])) {
			$config['def_height'] = (int)$pcfg['def_height']; // Limit default to maximum.
			if ($config['def_height'] > $config['max_height']) $config['def_height'] = $config['max_height'];
		}
		if (isset($pcfg['smiley_size']) && preg_match('/^\s*+(\d++)\s*+%?+\s*+$/', $pcfg['smiley_size'], $m)) {
			$config['smiley_size'] = (int)$m[1]; // Limit default to maximum.
		}
	}
	// Set new $bbcd values:
	foreach($bbcd as $tagname => $tagdata) {
		if ($tagname == '_ROOT_') continue; // Skip last pseudo-tag
		$tag =& $bbcd[$tagname];
		if(isset($_POST[$tagname.'_in_post'])) {
			if  ($_POST[$tagname.'_in_post'] == '1')	$tag['in_post']	= TRUE;
			else										$tag['in_post']	= FALSE;
		}
		if(isset($_POST[$tagname.'_in_sig'])) {
			if  ($_POST[$tagname.'_in_sig'] == '1')		$tag['in_sig']	= TRUE;
			else										$tag['in_sig']	= FALSE;
		}
		if(isset($_POST[$tagname.'_depth_max']) && preg_match('/^\d++$/', $_POST[$tagname.'_depth_max'])) {
			$tag['depth_max'] = (int)$_POST[$tagname.'_depth_max'];
		}
	}
	// Set new $smilies values:
	if (isset($_POST['smiley_text']) && is_array($_POST['smiley_text']) &&
		isset($_POST['smiley_file']) && is_array($_POST['smiley_file']) &&
		count($_POST['smiley_text']) === count($_POST['smiley_file'])) {
		$stext =& $_POST['smiley_text'];
		$sfile =& $_POST['smiley_file'];
		$len = count($stext);
		$good = '';
		$smilies = array();
		for ($i = 0; $i < $len; ++$i) { // Loop through all posted smileys.
			if ($stext[$i] && $sfile !== 'select new file') {
				$smilies[$stext[$i]] = array('file' => $sfile[$i]);
//				message(sprintf("New smiley: \"%s\" = %s\n", $stext[$i], $sfile[$i]));
			}
		}
	}

	require_once("include/bbcd_compile.php"); // Compile $bbcd and save into $pd['bbcd']
	redirect('admin_parser.php', $lang_admin_parser['save_success']);
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Parser']);
define('PUN_ACTIVE_PAGE', 'admin');
require PUN_ROOT.'header.php';

generate_admin_menu('parser');

?>
	<div class="blockform">
		<h2><span><?php echo $lang_admin_parser['Parser head'] ?></span></h2>
		<div class="box">
			<form method="post" action="admin_parser.php" enctype="multipart/form-data">
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
										<input type="radio" name="config[textile]" value="1"<?php if ($config['textile']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="config[textile]" value="0"<?php if (!$config['textile']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<span><?php echo $lang_admin_parser['textile help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_parser['quote_links'] ?></th>
									<td colspan="2">
										<input type="radio" name="config[quote_links]" value="1"<?php if ($config['quote_links']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="config[quote_links]" value="0"<?php if (!$config['quote_links']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<span><?php echo $lang_admin_parser['quote_links help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_parser['quote_imgs'] ?></th>
									<td colspan="2">
										<input type="radio" name="config[quote_imgs]" value="1"<?php if ($config['quote_imgs']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="config[quote_imgs]" value="0"<?php if (!$config['quote_imgs']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<span><?php echo $lang_admin_parser['quote_imgs help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_parser['click_imgs'] ?></th>
									<td colspan="2">
										<input type="radio" name="config[click_imgs]" value="1"<?php if ($config['click_imgs']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="config[click_imgs]" value="0"<?php if (!$config['click_imgs']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<span><?php echo $lang_admin_parser['click_imgs help'] ?></span>
									</td>
								</tr>

								<tr>
									<th scope="row"><?php echo $lang_admin_parser['valid_imgs'] ?></th>
									<td colspan="2">
										<input type="radio" name="config[valid_imgs]" value="1"<?php if ($config['valid_imgs']) echo ' checked="checked"'; if (!ini_get('allow_url_fopen')) echo(' disabled="disabled" title="'. htmlspecialchars($lang_admin_parser['unavailable']) .'"'); ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>
										<input type="radio" name="config[valid_imgs]" value="0"<?php if (!$config['valid_imgs']) echo ' checked="checked"'; if (!ini_get('allow_url_fopen')) echo(' disabled="disabled" title="'. htmlspecialchars($lang_admin_parser['unavailable']) .'"'); ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td><?php echo $lang_admin_parser['valid_imgs help'] ?></td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_parser['max_size'] ?></th>
									<td colspan="2">
										<input type="text" name="config[max_size]" size="10" maxlength="8" value="<?php echo($config['max_size'])?>"<?php if (!ini_get('allow_url_fopen')) echo(' disabled="disabled" title="'. htmlspecialchars($lang_admin_parser['unavailable']) .'"'); ?> />
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
	$smiley_files = get_smiley_files();
	$i = -1;
	foreach($smilies as $key => $value) {
		$i++;
		$oldfile = $value['file'];
?>
								<tr>
									<td><input type="text" name="smiley_text[<?php echo($i); ?>]" value="<?php echo(pun_htmlspecialchars($key)); ?>" size="20" maxlength="80" /></td>
									<td>
										<select name="smiley_file[<?php echo($i); ?>]">
<?php
		foreach($smiley_files as $file) {
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
										<?php echo($value['html']); ?>
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
		foreach($smiley_files as $file) {
			echo("\t\t\t\t\t\t\t\t\t\t\t<option>" . $file . "</option>\n");
		}
?>
										</select><br />New smiley image
									</td>
									<td></td>
								</tr>
								<tr>
									<th scope="row"><?php echo($lang_admin_parser['smiley_upload']); ?></th>
 <?php if (ini_get('file_uploads')) { ?>
									<td><input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $pun_config['o_avatars_size'] ?>" />
										<input type="file" name="new_smiley" id="upload_smiley" /></td>
									<td><input type="submit" name="upload" value="<?php echo($lang_admin_parser['upload_button']); ?>" /></td>
<?php } else { ?>
									<td colspan="2"><?php echo($lang_admin_parser['upload_off']); ?></td>
<?php } ?>
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
foreach($bbcd as $tagname => $tagdata) {
	if ($tagname == '_ROOT_') continue; // Skip last pseudo-tag
	$title = isset($lang_admin_parser['tag_summary'][$tagname]) ?
				$lang_admin_parser['tag_summary'][$tagname] : '';

/*
									<th class="tc2" scope="col"><?php echo $lang_admin_parser['tagtype_label'] ?></th>

									<td>
										<select name="<?php echo($tagname); ?>_type">
											<option<?php if ($tagdata['tag_type'] == 'ghost') echo( ' selected="selected"'); ?>>Ghost</option>
											<option<?php if ($tagdata['tag_type'] == 'normal') echo( ' selected="selected"'); ?>>Normal</option>
											<option<?php if ($tagdata['tag_type'] == 'atomic') echo( ' selected="selected"'); ?>>Atomic</option>
											<option<?php if ($tagdata['tag_type'] == 'hidden') echo( ' selected="selected"'); ?>>Hidden</option>
										</select>
									</td>
*/
 ?>
								<tr>
									<th scope="row" title="<?php echo($title); ?>"><?php echo('['. $tagname .']') ?></th>
									<td>
										<input type="radio" name="<?php echo($tagname) ?>_in_post" value="1"<?php if ($bbcd[$tagname]['in_post']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="<?php echo($tagname) ?>_in_post" value="0"<?php if (!$bbcd[$tagname]['in_post']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<input type="radio" name="<?php echo($tagname) ?>_in_sig" value="1"<?php if ($bbcd[$tagname]['in_sig']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['Yes'] ?></strong>   <input type="radio" name="<?php echo($tagname) ?>_in_sig" value="0"<?php if (!$bbcd[$tagname]['in_sig']) echo ' checked="checked"' ?> /> <strong><?php echo $lang_admin_common['No'] ?></strong>
									</td>
									<td>
										<input type="text" size="10" name="<?php echo($tagname) ?>_depth_max" value="<?php echo($bbcd[$tagname]['depth_max']); ?>" <?php if ($tagdata['html_type'] === 'inline' || $tagdata['tag_type'] === 'hidden') echo(' disabled="disabled" style="display: none;"'); ?> />
									</td>
								</tr>
<?php } ?>
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
<?php

// Helper function returns array of smiley image files
//   stored in the img/smilies directory.
function get_smiley_files() {
	$imgfiles = array();
	$filelist = scandir(PUN_ROOT.'img/smilies');
	foreach($filelist as $file) {
		if (preg_match('/\.(?:png|gif|jpe?g)$/', $file))
			$imgfiles[] = $file;
	}
	return $imgfiles;
}

// Helper function returns array of syntax highlighter CSS files
//   stored in the img/smilies directory.
function get_shcss() {
	$cssfiles = array();
	$filelist = scandir(PUN_ROOT.'bin');
	foreach($filelist as $file) {
		if (preg_match('/^sh(.*)\.css$/i', $file)) $cssfiles[] = $file;
	}
	return $cssfiles;
}

require PUN_ROOT.'footer.php';
