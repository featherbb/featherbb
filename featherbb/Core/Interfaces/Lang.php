<?php
namespace FeatherBB\Core\Interfaces;

use Gettext\Translations;
use Gettext\Translator;

class Lang extends \Statical\BaseProxy
{
    private static $domain;
    private static $translator;

    public static function construct($domain = 'FeatherBB')
    {
        self::$domain = $domain;
        self::$translator = new Translator();
        self::$translator->defaultDomain($domain);
        self::$translator->register();
    }

    public static function load($file, $domain = 'FeatherBB', $path = false, $language = false)
    {
        // Set default path to forum core translations
        $path = $path ? $path : ForumEnv::get('FEATHER_ROOT').'featherbb/lang';
        // Set default language to current user
        if (!$language) {
            $language = (!User::get(null)) ? 'English' : User::getPref('language');
        }

        /**
         * Try to locate translation file with the following priority order :
         *     - As provided in function arguments
         *     - User language
         *     - Forum default
         *     - English (which should always be available)
         */
        if (is_readable($path.'/'.$language.'/'.$file.'.mo')) {
            $file = $path.'/'.$language.'/'.$file.'.mo';
        } elseif (is_readable($path.'/'.ForumSettings::get('language').'/'.$file.'.mo')) {
            $file = $path.'/'.ForumSettings::get('language').'/'.$file.'.mo';
        } elseif (is_readable($path.'/English/'.$file.'.mo')) {
            $file = $path.'/English/'.$file.'.mo';
        } else {
            return false;
        }

        self::$translator->loadTranslations(
            Translations::fromMoFile($file)->setDomain($domain)
        );
    }
}
