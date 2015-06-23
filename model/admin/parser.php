<?php

/**
 * Copyright (C) 2015 FeatherBB
 * Parser (C) 2011 Jeff Roberson (jmrware.com)
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
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