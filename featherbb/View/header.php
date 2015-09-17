<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Random;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

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
    <title><?= Utils::generate_page_title($title, $page_number) ?></title>
    <!-- Theme -->
    <link rel="stylesheet" type="text/css" href="<?= Url::base_static() ?>/style/themes/<?= $style ?>/style.css">
<?php

foreach($assets as $type => $items) {
    if ($type == 'js') {
        continue;
    }
    echo "\t".'<!-- '.ucfirst($type).' -->'."\n";
    foreach ($items as $item) {
        echo "\t".'<link ';
        foreach ($item['params'] as $key => $value) {
            echo $key.'="'.$value.'" ';
        }
        echo 'href="'.Url::base_static().'/'.$item['file'].'">'."\n";
    }
}
if ($admin_console) {
    if (file_exists($feather->forum_env['FEATHER_ROOT'].'style/themes/'.$feather->user->style.'/base_admin.css')) {
        echo "\t".'<link rel="stylesheet" type="text/css" href="'.Url::base_static().'/style/themes/'.$feather->user->style.'/base_admin.css" />'."\n";
    } else {
        echo "\t".'<link rel="stylesheet" type="text/css" href="'.Url::base_static().'/style/imports/base_admin.css" />'."\n";
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

<body id="pun<?= $active_page ?>"<?= ($focus_element ? ' onload="document.getElementById(\''.$focus_element[0].'\').elements[\''.$focus_element[1].'\'].focus();"' : '')?>>
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
$navlinks[] = '<li id="navindex"'.(($active_page == 'index') ? ' class="isactive"' : '').'><a href="'.Url::base().'/">'.__('Index').'</a></li>'."\n";

if ($feather->user->g_read_board == '1' && $feather->user->g_view_users == '1') {
    $navlinks[] = '<li id="navuserlist"'.(($active_page == 'userlist') ? ' class="isactive"' : '').'><a href="'.$feather->urlFor('userList').'">'.__('User list').'</a></li>'."\n";
}

if ($feather->forum_settings['o_rules'] == '1' && (!$feather->user->is_guest || $feather->user->g_read_board == '1' || $feather->forum_settings['o_regs_allow'] == '1')) {
    $navlinks[] = '<li id="navrules"'.(($active_page == 'rules') ? ' class="isactive"' : '').'><a href="'.$feather->urlFor('rules').'">'.__('Rules').'</a></li>'."\n";
}

if ($feather->user->g_read_board == '1' && $feather->user->g_search == '1') {
    $navlinks[] = '<li id="navsearch"'.(($active_page == 'search') ? ' class="isactive"' : '').'><a href="'.$feather->urlFor('search').'">'.__('Search').'</a></li>'."\n";
}

if ($feather->user->is_guest) {
    $navlinks[] = '<li id="navregister"'.(($active_page == 'register') ? ' class="isactive"' : '').'><a href="'.$feather->urlFor('register').'">'.__('Register').'</a></li>'."\n";
    $navlinks[] = '<li id="navlogin"'.(($active_page == 'login') ? ' class="isactive"' : '').'><a href="'.$feather->urlFor('login').'">'.__('Login').'</a></li>'."\n";
} else {
    $navlinks[] = '<li id="navprofile"'.(($active_page == 'profile') ? ' class="isactive"' : '').'><a href="'.$feather->urlFor('userProfile', ['id' => $feather->user->id]).'">'.__('Profile').'</a></li>'."\n";

    if ($feather->user->is_admmod) {
        $navlinks[] = '<li id="navadmin"'.(($active_page == 'admin') ? ' class="isactive"' : '').'><a href="'.$feather->urlFor('adminIndex').'">'.__('Admin').'</a></li>'."\n";
    }

    $navlinks[] = '<li id="navlogout"><a href="'.$feather->urlFor('logout', ['token' => Random::hash($feather->user->id.Random::hash($feather->request->getIp()))]).'">'.__('Logout').'</a></li>'."\n";
}

// Are there any additional navlinks we should insert into the array before imploding it?
$hooksLinks = $feather->hooks->fire('view.header.navlinks', []);
$extraLinks = $feather->forum_settings['o_additional_navlinks']."\n".implode("\n", $hooksLinks);
if ($feather->user->g_read_board == '1' && ($extraLinks != '')) {
    if (preg_match_all('%([0-9]+)\s*=\s*(.*?)\n%s', $extraLinks."\n", $results)) {
        // Insert any additional links into the $links array (at the correct index)
        $num_links = count($results[1]);
        for ($i = 0; $i < $num_links; ++$i) {
            array_splice($navlinks, $results[1][$i], 0, array('<li id="navextra'.($i + 1).'"'.(($active_page == 'navextra'.($i + 1)) ? ' class="isactive"' : '').'>'.$results[2][$i].'</li>'));
        }
    }
}
echo "\t\t\t".implode("\t\t\t", $navlinks);
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
                <a href="<?= Url::base() ?>" title="" class="site-name">
                    <p><?= Utils::escape($feather->forum_settings['o_board_title']) ?></p>
                </a>
                <div id="brddesc"><?= htmlspecialchars_decode($feather->forum_settings['o_board_desc']) ?></div>
            </h1>
            <div class="status-avatar">
                <div id="brdwelcome" class="inbox">

<?php
if ($feather->user->is_guest) { ?>
                    <p class="conl"><?= __('Not logged in')?></p>
<?php } else {
    echo "\t\t\t\t\t".'<ul class="conl">';
    echo "\t\t\t\t\t\t".'<li><span>'.__('Logged in as').' <strong>'.Utils::escape($feather->user->username).'</strong></span></li>'."\n";
    echo "\t\t\t\t\t\t".'<li><span>'.sprintf(__('Last visit'), $feather->utils->format_time($feather->user->last_visit)).'</span></li>'."\n";

    if ($feather->user->is_admmod) {
        if ($feather->forum_settings['o_report_method'] == '0' || $feather->forum_settings['o_report_method'] == '2') {
            if ($has_reports) {
                echo "\t\t\t\t\t\t".'<li class="reportlink"><span><strong><a href="'.$feather->urlFor('adminReports').'">'.__('New reports').'</a></strong></span></li>'."\n";
            }
        }

        if ($feather->forum_settings['o_maintenance'] == '1') {
            echo "\t\t\t\t\t\t".'<li class="maintenancelink"><span><strong><a href="'.$feather->urlFor('adminMaintenance').'">'.__('Maintenance mode enabled').'</a></strong></span></li>'."\n";
        }
    }
    echo "\t\t\t\t\t".'</ul>'."\n";
}

if ($feather->user->g_read_board == '1' && $feather->user->g_search == '1') {
    echo "\t\t\t\t\t".'<ul class="conr">'."\n";
    echo "\t\t\t\t\t\t".'<li><span>'.__('Topic searches').' ';
    if (!$feather->user->is_guest) {
        echo '<a href="'.$feather->urlFor('quickSearch', ['show' => 'replies']).'" title="'.__('Show posted topics').'">'.__('Posted topics').'</a> | ';
        echo '<a href="'.$feather->urlFor('quickSearch', ['show' => 'new']).'" title="'.__('Show new posts').'">'.__('New posts header').'</a> | ';
    }
    echo '<a href="'.$feather->urlFor('quickSearch', ['show' => 'recent']).'" title="'.__('Show active topics').'">'.__('Active topics').'</a> | ';
    echo '<a href="'.$feather->urlFor('quickSearch', ['show' => 'unanswered']).'" title="'.__('Show unanswered topics').'">'.__('Unanswered topics').'</a>';
    echo '</li>'."\n";
    echo "\t\t\t\t".'</ul>'."\n";
} ?>
                <div class="clearer"></div>
                </div>
            </div>
            <div class="clear"></div>
        </div>
        <?php if ($feather->user->g_read_board == '1' && $feather->forum_settings['o_announcement'] == '1') : ?>
        <div id="announce" class="block">
            <div class="hd"><h2><span><?php _e('Announcement') ?></span></h2></div>
            <div class="box">
                <div id="announce-block" class="inbox">
                    <div class="usercontent"><?= $feather->forum_settings['o_announcement_message'] ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($flash->getMessages())) : ?>
        <script type="text/javascript">
            window.onload = function() {
                var flashMessage = document.getElementById('flashmsg');
                flashMessage.className = 'flashmsg '+flashMessage.getAttribute('data-type')+' show';
                setTimeout(function () {
                    flashMessage.className = 'flashmsg '+flashMessage.getAttribute('data-type');
                }, 10000);
                return false;
            }
        </script>
        <?php foreach ($flash->getMessages() as $type => $message) { ?>
        <div class="flashmsg info" data-type="<?= $type; ?>" id="flashmsg">
            <h2><?php _e('Info') ?><span style="float:right;cursor:pointer" onclick="document.getElementById('flashmsg').className = 'flashmsg';">&times;</span></h2>
            <p><?= Utils::escape($message) ?></p>
        </div>
        <?php } ?>
        <?php endif; ?>
    </div>
</header>

<section class="container">
    <div id="brdmain">
