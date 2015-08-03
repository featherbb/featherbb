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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1">
    <title><?php echo generate_page_title($page_title, $p) ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url() ?>/style/<?php echo $feather->user->style.'.css' ?>" />
    <?php
    if (!defined('FEATHER_ALLOW_INDEX')) {
        echo '<meta name="ROBOTS" content="NOINDEX, FOLLOW" />'."\n";
    }

    if (defined('FEATHER_ADMIN_CONSOLE')) {
        if (file_exists(FEATHER_ROOT.'style/'.$feather->user->style.'/base_admin.css')) {
            echo '<link rel="stylesheet" type="text/css" href="'.get_base_url().'/style/'.$feather->user->style.'/base_admin.css" />'."\n";
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

<body id="pun<?php echo FEATHER_ACTIVE_PAGE ?>">

<header>

    <nav>
        <div class="container">
            <div class="phone-menu" id="phone-button">
                <a class="button-phone"></a>
            </div>
            <div id="phone">
                <?php echo $navlinks ?>
                <div class="navbar-right">
                    <form method="get" action="search" class="nav-search">
                        <input type="hidden" name="action" value="search">
                        <input type="text" name="keywords" size="20" maxlength="100" placeholder="<?php echo $lang_common['Search'] ?>">
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
                <div id="brddesc"><?php echo $feather_config['o_board_desc'] ?></div>
            </h1>
            <div class="status-avatar">
                <?php echo $page_info ?>
            </div>
            <div class="clear"></div>
        </div>
        <?php if ($feather->user->g_read_board == '1' && $feather_config['o_announcement'] == '1') : ?>
            <div id="announce" class="block">
                <div class="hd"><h2><span><?php echo $lang_common['Announcement'] ?></span></h2></div>
                <div class="box">
                    <div id="announce-block" class="inbox">
                        <div class="usercontent"><?php echo $feather_config['o_announcement_message'] ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (isset($flash['message'])) : ?>
            <script type="text/javascript" src="<?=get_base_url();?>/js/common.js"></script>
            <div id="msgflash" class="block">
                <h2><span><?php echo $lang_common['Info'] ?></span><span style="float:right;cursor:pointer" onclick="fadeOut('msgflash', 9);">&times;</span></h2>
                <div class="box">
                    <div class="inbox">
                        <p><?php echo feather_escape($flash['message']) ?></p>
                    </div>
                </div>
            </div>
            <script type="text/javascript">fadeIn('msgflash', 0);</script>
        <?php endif; ?>
    </div>

</header>

<section class="container">
    <div id="brdmain">
