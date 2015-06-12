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

// Load the admin_parser.php model file
require PUN_ROOT.'model/admin_parser.php';

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

// Load the admin_parser.php view file
require PUN_ROOT.'view/admin_parser.php';

require PUN_ROOT.'footer.php';
