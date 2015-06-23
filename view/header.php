<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang_common['lang_identifier'] ?>" lang="<?php echo $lang_common['lang_identifier'] ?>" dir="<?php echo $lang_common['lang_direction'] ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo generate_page_title($page_title, $p) ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo get_base_url() ?>/style/<?php echo $pun_user['style'].'.css' ?>" />
<?php
if (!defined('PUN_ALLOW_INDEX')) {
    echo '<meta name="ROBOTS" content="NOINDEX, FOLLOW" />'."\n";
}

if (defined('PUN_ADMIN_CONSOLE')) {
    if (file_exists(PUN_ROOT.'style/'.$pun_user['style'].'/base_admin.css')) {
        echo '<link rel="stylesheet" type="text/css" href="'.get_base_url().'/style/'.$pun_user['style'].'/base_admin.css" />'."\n";
    } else {
        echo '<link rel="stylesheet" type="text/css" href="'.get_base_url().'/style/imports/base_admin.css" />'."\n";
    }
}

if (isset($required_fields)) :
    // Output JavaScript to validate form (make sure required fields are filled out)

?>
<script type="text/javascript">
/* <![CDATA[ */
function process_form(the_form)
{
	var required_fields = {
<?php
    // Output a JavaScript object with localised field names
    $tpl_temp = count($required_fields);
    foreach ($required_fields as $elem_orig => $elem_trans) {
        echo "\t\t\"".$elem_orig.'": "'.addslashes(str_replace('&#160;', ' ', $elem_trans));
        if (--$tpl_temp) {
            echo "\",\n";
        } else {
            echo "\"\n\t};\n";
        }
    }
    ?>
	if (document.all || document.getElementById)
	{
		for (var i = 0; i < the_form.length; ++i)
		{
			var elem = the_form.elements[i];
			if (elem.name && required_fields[elem.name] && !elem.value && elem.type && (/^(?:text(?:area)?|password|file)$/i.test(elem.type)))
			{
				alert('"' + required_fields[elem.name] + '" <?php echo $lang_common['required field'] ?>');
				elem.focus();
				return false;
			}
		}
	}
	return true;
}
/* ]]> */
</script>
<?php
endif;
if (!empty($page_head)) :
    echo implode("\n", $page_head)."\n";
endif;
?>
</head>

<body>

<div id="pun<?php echo PUN_ACTIVE_PAGE ?>" class="pun">
<div class="top-box"></div>
<div class="punwrap">

<div id="brdheader" class="block">
	<div class="box">
		<div id="brdtitle" class="inbox">
			<h1><a href="index.php"><?php echo pun_htmlspecialchars($pun_config['o_board_title']) ?></a></h1>
			<div id="brddesc"><?php echo  $pun_config['o_board_desc'] ?></div>
		</div>
		<?php echo $navlinks ?>
		<?php echo $page_info ?>
	</div>
</div>

<?php if ($pun_user['g_read_board'] == '1' && $pun_config['o_announcement'] == '1') : ?>
<div id="announce" class="block">
	<div class="hd"><h2><span><?php echo $lang_common['Announcement'] ?></span></h2></div>
	<div class="box">
		<div id="announce-block" class="inbox">
			<div class="usercontent"><?php echo $pun_config['o_announcement_message'] ?></div>
		</div>
	</div>
</div>
<?php endif; ?>

<div id="brdmain">