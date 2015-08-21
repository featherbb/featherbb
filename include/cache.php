<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

//
// Generate a cache ID based on the last modification time for all stopwords files
//
function generate_stopwords_cache_id()
{
    $files = glob(FEATHER_ROOT.'lang/*/stopwords.txt');
    if ($files === false) {
        return 'cache_id_error';
    }

    $hash = array();

    foreach ($files as $file) {
        $hash[] = $file;
        $hash[] = filemtime($file);
    }

    return sha1(implode('|', $hash));
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
