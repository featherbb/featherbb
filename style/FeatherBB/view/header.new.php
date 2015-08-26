<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

?>
<!doctype html>
<html lang="<?php _e('lang_identifier') ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1">
<?php if ($is_indexed) {
    echo "\t".'<meta name="robots" content="noindex, follow">'."\n";
} ?>
    <title><?php echo generate_page_title($title, $page_number) ?></title>
    <link rel="stylesheet" type="text/css" href="<?php echo get_base_url() ?>/style/<?php echo $feather->user->style.'.css' ?>" />
<?php if ($admin_console) {
    if (file_exists($feather->forum_env['FEATHER_ROOT'].'style/'.$feather->user->style.'/base_admin.css')) {
        echo "\t".'<link rel="stylesheet" type="text/css" href="'.get_base_url().'/style/'.$feather->user->style.'/base_admin.css" />'."\n";
    } else {
        echo "\t".'<link rel="stylesheet" type="text/css" href="'.get_base_url().'/style/imports/base_admin.css" />'."\n";
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

<body id="pun<?= $active_page ?>" <?= ($focus_element ? 'onload="document.getElementById(\''.$focus_element[0].'\').elements[\''.$focus_element[1].'\'].focus();"' : '')?>>
<header>
    <nav>
        <div class="container">
            <div class="phone-menu" id="phone-button">
                <a class="button-phone"></a>
            </div>
            <div id="phone">
                <div id="brdmenu" class="inbox">
                    <ul>
<?php
echo "\t\t\t\t\t\t".'<li id="navindex"'.(($active_page == 'index') ? ' class="isactive"' : '').'><a href="'.get_base_url().'/">'.__('Index').'</a></li>'."\n";

if ($feather->user->g_read_board == '1' && $feather->user->g_view_users == '1') {
    echo "\t\t\t\t\t\t".'<li id="navuserlist"'.(($active_page == 'userlist') ? ' class="isactive"' : '').'><a href="'.get_link('userlist/').'">'.__('User list').'</a></li>'."\n";
}

if ($feather->forum_settings['o_rules'] == '1' && (!$feather->user->is_guest || $feather->user->g_read_board == '1' || $feather->forum_settings['o_regs_allow'] == '1')) {
    echo "\t\t\t\t\t\t".'<li id="navrules"'.(($active_page == 'rules') ? ' class="isactive"' : '').'><a href="'.get_link('rules/').'">'.__('Rules').'</a></li>'."\n";
}

if ($feather->user->g_read_board == '1' && $feather->user->g_search == '1') {
    echo "\t\t\t\t\t\t".'<li id="navsearch"'.(($active_page == 'search') ? ' class="isactive"' : '').'><a href="'.get_link('search/').'">'.__('Search').'</a></li>'."\n";
}

if ($feather->user->is_guest) {
    echo "\t\t\t\t\t\t".'<li id="navregister"'.(($active_page == 'register') ? ' class="isactive"' : '').'><a href="'.get_link('register/').'">'.__('Register').'</a></li>'."\n";
    echo "\t\t\t\t\t\t".'<li id="navlogin"'.(($active_page == 'login') ? ' class="isactive"' : '').'><a href="'.get_link('login/').'">'.__('Login').'</a></li>'."\n";
} else {
    echo "\t\t\t\t\t\t".'<li id="navprofile"'.(($active_page == 'profile') ? ' class="isactive"' : '').'><a href="'.get_link('user/'.$feather->user->id.'/').'">'.__('Profile').'</a></li>'."\n";

    if ($feather->user->is_admmod) {
        echo "\t\t\t\t\t\t".'<li id="navadmin"'.(($active_page == 'admin') ? ' class="isactive"' : '').'><a href="'.get_link('admin/').'">'.__('Admin').'</a></li>'."\n";
    }

    echo "\t\t\t\t\t\t".'<li id="navlogout"><a href="'.get_link('logout/id/'.$feather->user->id.'/token/'.feather_hash($feather->user->id.feather_hash($this->request->getIp()))).'/">'.__('Logout').'</a></li>'."\n";
}

// // Are there any additional navlinks we should insert into the array before imploding it?
// if ($feather->user->g_read_board == '1' && $feather->forum_settings['o_additional_navlinks'] != '') {
//     if (preg_match_all('%([0-9]+)\s*=\s*(.*?)\n%s', $feather->forum_settings['o_additional_navlinks']."\n", $extra_links)) {
//         // Insert any additional links into the $links array (at the correct index)
//         $num_links = count($extra_links[1]);
//         for ($i = 0; $i < $num_links; ++$i) {
//             array_splice($links, $extra_links[1][$i], 0, array('<li id="navextra'.($i + 1).'">'.$extra_links[2][$i].'</li>'));
//         }
//     }
// }
?>
                        </ul>
                    </div>
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
