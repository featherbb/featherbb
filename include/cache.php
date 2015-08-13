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


//
// Generate the config cache PHP script
//
function generate_config_cache()
{
    // Get the forum config from the DB
    $result = \DB::for_table('config')->find_array();

    $output = array();
    foreach ($result as $cur_config_item) {
        $output[$cur_config_item['conf_name']] = $cur_config_item['conf_value'];
    }

    // Output config as PHP code
    $content = '<?php'."\n\n".'define(\'FEATHER_CONFIG_LOADED\', 1);'."\n\n".'$feather_config = '.var_export($output, true).';'."\n\n".'?>';
    featherbb_write_cache_file('cache_config.php', $content);
}


//
// Generate the bans cache PHP script
//
function generate_bans_cache()
{
    // Get the ban list from the DB
    $result = \DB::for_table('bans')->find_array();

    $output = array();
    foreach ($result as $cur_ban) {
        $output[] = $cur_ban;
    }

    // Output ban list as PHP code
    $content = '<?php'."\n\n".'define(\'FEATHER_BANS_LOADED\', 1);'."\n\n".'$feather_bans = '.var_export($output, true).';'."\n\n".'?>';
    featherbb_write_cache_file('cache_bans.php', $content);
}


//
// Generate quick jump cache PHP scripts
//
function generate_quickjump_cache($group_id = false)
{
    global $lang_common;

    $groups = array();

    // If a group_id was supplied, we generate the quick jump cache for that group only
    if ($group_id !== false) {
        // Is this group even allowed to read forums?
        $read_board = \DB::for_table('groups')->where('g_id', $group_id)
                            ->find_one_col('g_read_board');

        $groups[$group_id] = $read_board;
    } else {
        // A group_id was not supplied, so we generate the quick jump cache for all groups
        $select_quickjump_all_groups = array('g_id', 'g_read_board');
        $result = \DB::for_table('groups')->select_many($select_quickjump_all_groups)
                        ->find_many();

        foreach ($result as $row) {
            $groups[$row['g_id']] = $row['g_read_board'];
        }
    }

    // Loop through the groups in $groups and output the cache for each of them
    foreach ($groups as $group_id => $read_board) {
        // Output quick jump as PHP code
        $output = '<?php'."\n\n".'if (!defined(\'FEATHER\')) exit;'."\n".'define(\'FEATHER_QJ_LOADED\', 1);'."\n".'$forum_id = isset($forum_id) ? $forum_id : 0;'."\n\n".'?>';

        if ($read_board == '1') {
            $select_generate_quickjump_cache = array('cid' => 'c.id', 'c.cat_name', 'fid' => 'f.id', 'f.forum_name', 'f.redirect_url');
            $where_generate_quickjump_cache = array(
                array('fp.read_forum' => 'IS NULL'),
                array('fp.read_forum' => '1')
            );
            $order_by_generate_quickjump_cache = array('c.disp_position', 'c.id', 'f.disp_position');

            $result = \DB::for_table('categories')
                            ->table_alias('c')
                            ->select_many($select_generate_quickjump_cache)
                            ->inner_join('forums', array('c.id', '=', 'f.cat_id'), 'f')
                            ->left_outer_join('forum_perms', array('fp.forum_id', '=', 'f.id'), 'fp')
                            ->left_outer_join('forum_perms', array('fp.group_id', '=', $group_id), null, true)
                            ->where_any_is($where_generate_quickjump_cache)
                            ->where_null('f.redirect_url')
                            ->order_by_many($order_by_generate_quickjump_cache)
                            ->find_many();

            if ($result) {
                $output .= "\t\t\t\t".'<form id="qjump" method="get" action="">'."\n\t\t\t\t\t".'<div><label><span><?php echo $lang_common[\'Jump to\'] ?>'.'<br /></span>'."\n\t\t\t\t\t".'<select name="id" onchange="window.location=(\''.get_link('forum/').'\'+this.options[this.selectedIndex].value)">'."\n";

                $cur_category = 0;
                foreach ($result as $cur_forum) {
                    if ($cur_forum['cid'] != $cur_category) {
                        // A new category since last iteration?

                        if ($cur_category) {
                            $output .= "\t\t\t\t\t\t".'</optgroup>'."\n";
                        }

                        $output .= "\t\t\t\t\t\t".'<optgroup label="'.feather_escape($cur_forum['cat_name']).'">'."\n";
                        $cur_category = $cur_forum['cid'];
                    }

                    $redirect_tag = ($cur_forum['redirect_url'] != '') ? ' &gt;&gt;&gt;' : '';
                    $output .= "\t\t\t\t\t\t\t".'<option value="'.$cur_forum['fid'].'/'.url_friendly($cur_forum['forum_name']).'/'.'"<?php echo ($forum_id == '.$cur_forum['fid'].') ? \' selected="selected"\' : \'\' ?>>'.feather_escape($cur_forum['forum_name']).$redirect_tag.'</option>'."\n";
                }

                $output .= "\t\t\t\t\t\t".'</optgroup>'."\n\t\t\t\t\t".'</select></label>'."\n\t\t\t\t\t".'<input type="submit" value="<?php echo $lang_common[\'Go\'] ?>" accesskey="g" />'."\n\t\t\t\t\t".'</div>'."\n\t\t\t\t".'</form>'."\n";
            }
        }

        featherbb_write_cache_file('cache_quickjump_'.$group_id.'.php', $output);
    }
}


//
// Generate the censoring cache PHP script
//
function generate_censoring_cache()
{
    $select_generate_censoring_cache = array('search_for', 'replace_with');
    $result = \DB::for_table('censoring')->select_many($select_generate_censoring_cache)
                    ->find_many();

    $search_for = $replace_with = array();
    $i = 0;
    foreach ($result as $row) {
        $replace_with[$i] = $row['replace_with'];
        $search_for[$i] = '%(?<=[^\p{L}\p{N}])('.str_replace('\*', '[\p{L}\p{N}]*?', preg_quote($row['search_for'], '%')).')(?=[^\p{L}\p{N}])%iu';
        ++$i;
    }

    // Output censored words as PHP code
    $content = '<?php'."\n\n".'define(\'FEATHER_CENSOR_LOADED\', 1);'."\n\n".'$search_for = '.var_export($search_for, true).';'."\n\n".'$replace_with = '.var_export($replace_with, true).';'."\n\n".'?>';
    featherbb_write_cache_file('cache_censoring.php', $content);
}


//
// Generate the stopwords cache PHP script
//
function generate_stopwords_cache()
{
    $stopwords = array();

    $d = dir(FEATHER_ROOT.'lang');
    while (($entry = $d->read()) !== false) {
        if ($entry{0} == '.') {
            continue;
        }

        if (is_dir(FEATHER_ROOT.'lang/'.$entry) && file_exists(FEATHER_ROOT.'lang/'.$entry.'/stopwords.txt')) {
            $stopwords = array_merge($stopwords, file(FEATHER_ROOT.'lang/'.$entry.'/stopwords.txt'));
        }
    }
    $d->close();

    // Tidy up and filter the stopwords
    $stopwords = array_map('feather_trim', $stopwords);
    $stopwords = array_filter($stopwords);

    // Output stopwords as PHP code
    $content = '<?php'."\n\n".'$cache_id = \''.generate_stopwords_cache_id().'\';'."\n".'if ($cache_id != generate_stopwords_cache_id()) return;'."\n\n".'define(\'FEATHER_STOPWORDS_LOADED\', 1);'."\n\n".'$stopwords = '.var_export($stopwords, true).';'."\n\n".'?>';
    featherbb_write_cache_file('cache_stopwords.php', $content);
}


//
// Load some information about the latest registered users
//
function generate_users_info_cache()
{
    $stats = array();

    $stats['total_users'] = (\DB::for_table('users')->where_not_equal('group_id', FEATHER_UNVERIFIED)
                                ->count('id')) - 1;

    $select_generate_users_info_cache = array('id', 'username');
    $last_user = \DB::for_table('users')->select_many($select_generate_users_info_cache)
                        ->where_not_equal('group_id', FEATHER_UNVERIFIED)
                        ->order_by_desc('registered')
                        ->limit(1)
                        ->find_array();
    $stats['last_user'] = $last_user[0];

    // Output users info as PHP code
    $content = '<?php'."\n\n".'define(\'feather_userS_INFO_LOADED\', 1);'."\n\n".'$stats = '.var_export($stats, true).';'."\n\n".'?>';
    featherbb_write_cache_file('cache_users_info.php', $content);
}


//
// Generate the admins cache PHP script
//
function generate_admins_cache()
{
    // Get admins from the DB
    $result = \DB::for_table('users')->select('id')
                    ->where('group_id', FEATHER_ADMIN)
                    ->find_array();

    $output = array();
    foreach ($result as $row) {
        $output[] = $row['id'];
    }

    // Output admin list as PHP code
    $content = '<?php'."\n\n".'define(\'FEATHER_ADMINS_LOADED\', 1);'."\n\n".'$feather_admins = '.var_export($output, true).';'."\n\n".'?>';
    featherbb_write_cache_file('cache_admins.php', $content);
}


//
// Safely write out a cache file.
//
function featherbb_write_cache_file($file, $content)
{
    $fh = @fopen(FORUM_CACHE_DIR.$file, 'wb');
    if (!$fh) {
        error('Unable to write cache file '.feather_escape($file).' to cache directory. Please make sure PHP has write access to the directory \''.feather_escape(FORUM_CACHE_DIR).'\'', __FILE__, __LINE__);
    }

    flock($fh, LOCK_EX);
    ftruncate($fh, 0);

    fwrite($fh, $content);

    flock($fh, LOCK_UN);
    fclose($fh);

    featherbb_invalidate_cached_file(FORUM_CACHE_DIR.$file);
}


//
// Delete all feed caches
//
function clear_feed_cache()
{
    $d = dir(FORUM_CACHE_DIR);
    while (($entry = $d->read()) !== false) {
        if (substr($entry, 0, 10) == 'cache_feed' && substr($entry, -4) == '.php') {
            @unlink(FORUM_CACHE_DIR.$entry);
        }
        featherbb_invalidate_cached_file(FORUM_CACHE_DIR.$entry);
    }
    $d->close();
}


//
// Invalidate updated php files that are cached by an opcache
//
function featherbb_invalidate_cached_file($file)
{
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($file, true);
    } elseif (function_exists('apc_delete_file')) {
        @apc_delete_file($file);
    }
}


define('FORUM_CACHE_FUNCTIONS_LOADED', true);
