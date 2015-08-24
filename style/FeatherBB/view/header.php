<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php _e('lang_identifier') ?>" lang="<?php _e('lang_identifier') ?>" dir="<?php _e('lang_direction') ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1">
<title><?php echo generate_page_title($page_title, $p) ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo get_base_url() ?>/style/<?php echo $feather->user->style.'.css' ?>" />
<?php

echo $allow_index;
echo $admin_console;

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
                        alert('"' + required_fields[elem.name] + '" <?php _e('required field') ?>');
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

<body id="pun<?= $active_page ?>"<?= $focus_element; ?>>

<header>

    <nav>
        <div class="container">
            <div class="phone-menu" id="phone-button">
                <a class="button-phone"></a>
            </div>
            <div id="phone">
                <?php echo $navlinks ?>
                <div class="navbar-right">
                    <form method="get" action="/search" class="nav-search">
                        <input type="hidden" name="action" value="search">
                        <input type="text" name="keywords" size="20" maxlength="100" placeholder="<?php _e('Search') ?>">
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="container-title-status">
            <h1 class="title-site">
                <a href="<?php echo get_base_url() ?>" title="" class="site-name">
                    <p><?php echo feather_escape($feather_config['o_board_title']) ?></p>
                </a>
                <div id="brddesc"><?php echo htmlspecialchars_decode($feather_config['o_board_desc']) ?></div>
            </h1>
            <div class="status-avatar">
                <?php echo $page_info ?>
            </div>
            <div class="clear"></div>
        </div>
        <?php if ($feather->user->g_read_board == '1' && $feather_config['o_announcement'] == '1') : ?>
            <div id="announce" class="block">
                <div class="hd"><h2><span><?php _e('Announcement') ?></span></h2></div>
                <div class="box">
                    <div id="announce-block" class="inbox">
                        <div class="usercontent"><?php echo $feather_config['o_announcement_message'] ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (isset($flash['message'])) : ?>
            <script type="text/javascript" src="<?=get_base_url();?>/js/common.js"></script>
            <script type="text/javascript">
                window.onload = function() {
                    var flashMessage = document.getElementById('flashmsg');
                    flashMessage.className = 'flashmsg show';
                    setTimeout(function () {
                        flashMessage.className = 'flashmsg';
                    }, 10000);
                    return false;
                }
            </script>
            <div class="flashmsg" id="flashmsg">
                <h2><?php _e('Info') ?><span style="float:right;cursor:pointer" onclick="document.getElementById('flashmsg').className = 'flashmsg';">&times;</span></h2>
                <p><?= feather_escape($flash['message']) ?></p>
            </div>
        <?php endif; ?>
    </div>

</header>

<section class="container">
    <div id="brdmain">
