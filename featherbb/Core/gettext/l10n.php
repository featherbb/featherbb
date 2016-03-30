<?php
/**
 * Load a .mo file into the text domain $domain.
 *
 * If the text domain already exists, the translations will be merged. If both
 * sets have the same string, the translation from the original value will be taken.
 *
 * On success, the .mo file will be placed in the $l10n global by $domain
 * and will be a MO object.
 *
 * @param    string     $domain Text domain. Unique identifier for retrieving translated strings.
 * @param    string     $mofile Path to the .mo file.
 *
 * @return   boolean    True on success, false on failure.
 *
 * Inspired from Luna <http://getluna.org>
 */
function translate($mofile, $domain = 'featherbb', $language = false, $path = false) {

    global $l10n;

    // Set default path to forum core translations
    $path = $path ? $path :  ForumEnv::get('FEATHER_ROOT').'featherbb/lang';
    // Set default language to current user
    $language = $language ? $language : User::get()->language;

    /**
     * Try to locate translation file with the following priority order :
     *     - As provided in function arguments
     *     - User language
     *     - Forum default
     *     - English (which sould always be available)
     */
    if (is_readable($path.'/'.$language.'/'.$mofile.'.mo')) {
        $mofile = $path.'/'.$language.'/'.$mofile.'.mo';
    } elseif (is_readable($path.'/'.ForumSettings::get('o_default_lang').'/'.$mofile.'.mo')) {
        $mofile = $path.'/'.ForumSettings::get('o_default_lang').'/'.$mofile.'.mo';
    } elseif (is_readable($path.'/English/'.$mofile.'.mo')) {
        $mofile = $path.'/English/'.$mofile.'.mo';
    } else {
        return false;
    }

    $mo = new MO();
    if (!$mo->import_from_file($mofile)) {
        return false;
    }

    if (isset($l10n[$domain])) {
        $mo->merge_with($l10n[$domain]);
    }

    $l10n[$domain] = &$mo;

    return true;
}

function __($text, $domain = 'featherbb') {
    return translation($text, $domain);
}

function _e($text, $domain = 'featherbb') {
    echo translation($text, $domain);
}

function translation($text, $domain = 'featherbb') {

    global $l10n;

    if (!isset($l10n[$domain])) {
        if (!class_exists('NOOPTranslations'))
            require_once dirname(__FILE__) . '/translations/NOOPTranslations.php';
        $l10n[$domain] = new NOOPTranslations;
    }

    $translations = $l10n[$domain];
    $translations = $translations->translate($text);

    return $translations;
}
