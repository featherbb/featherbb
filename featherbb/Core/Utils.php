<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

use FeatherBB\Model\Cache;

class Utils
{
    //
    // Return current timestamp (with microseconds) as a float
    //
    public static function getMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    //
    // Replace four-byte characters with a question mark
    //
    // As MySQL cannot properly handle four-byte characters with the default utf-8
    // charset up until version 5.5.3 (where a special charset has to be used), they
    // need to be replaced, by question marks in this case.
    //
    public static function stripBadMultibyteChars($str)
    {
        $result = '';
        $length = strlen($str);

        for ($i = 0; $i < $length; $i++) {
            // Replace four-byte characters (11110www 10zzzzzz 10yyyyyy 10xxxxxx)
            $ord = ord($str[$i]);
            if ($ord >= 240 && $ord <= 244) {
                $result .= '?';
                $i += 3;
            } else {
                $result .= $str[$i];
            }
        }

        return $result;
    }

    //
    // A wrapper for PHP's number_format function
    //
    public static function forumNumberFormat($number, $decimals = 0)
    {
        return is_numeric($number) ? number_format($number, $decimals, __('lang_decimal_point'), __('lang_thousands_sep')) : $number;
    }

    //
    // Format a time string according to $time_format and time zones
    //
    public static function formatTime($timestamp, $date_only = false, $date_format = null, $time_format = null, $time_only = false, $no_text = false)
    {
        if ($timestamp == '') {
            return __('Never');
        }

        $diff = (User::getPref('timezone') + User::getPref('dst')) * 3600;
        $timestamp += $diff;
        $now = time();

        if (is_null($date_format)) {
            $date_format = User::getPref('date_format');
        }

        if (is_null($time_format)) {
            $time_format = User::getPref('time_format');
        }

        $date = gmdate($date_format, $timestamp);
        $today = gmdate($date_format, $now+$diff);
        $yesterday = gmdate($date_format, $now+$diff-86400);

        if (!$no_text) {
            if ($date == $today) {
                $date = __('Today');
            } elseif ($date == $yesterday) {
                $date = __('Yesterday');
            }
        }

        if ($date_only) {
            return $date;
        } elseif ($time_only) {
            return gmdate($time_format, $timestamp);
        } else {
            return $date.' '.gmdate($time_format, $timestamp);
        }
    }


    //
    // Calls htmlspecialchars with a few options already set
    //
    public static function escape($str)
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }


    //
    // A wrapper for utf8_strlen for compatibility
    //
    public static function strlen($str)
    {
        return utf8_strlen($str);
    }


    //
    // Convert \r\n and \r to \n
    //
    public static function linebreaks($str)
    {
        return str_replace(["\r\n", "\r"], "\n", $str);
    }


    //
    // A wrapper for utf8_trim for compatibility
    //
    public static function trim($str, $charlist = false)
    {
        return is_string($str) ? utf8_trim($str, $charlist) : '';
    }

    //
    // Checks if a string is in all uppercase
    //
    public static function isAllUppercase($string)
    {
        return utf8_strtoupper($string) == $string && utf8_strtolower($string) != $string;
    }

    //
    // Replace string matching regular expression
    //
    // This function takes care of possibly disabled unicode properties in PCRE builds
    //
    public static function ucpPregReplace($pattern, $replace, $subject, $callback = false)
    {
        if ($callback) {
            $replaced = preg_replace_callback($pattern, create_function('$matches', 'return '.$replace.';'), $subject);
        } else {
            $replaced = preg_replace($pattern, $replace, $subject);
        }

        // If preg_replace() returns false, this probably means unicode support is not built-in, so we need to modify the pattern a little
        if ($replaced === false) {
            if (is_array($pattern)) {
                foreach ($pattern as $cur_key => $cur_pattern) {
                    $pattern[$cur_key] = str_replace('\p{L}\p{N}', '\w', $cur_pattern);
                }

                $replaced = preg_replace($pattern, $replace, $subject);
            } else {
                $replaced = preg_replace(str_replace('\p{L}\p{N}', '\w', $pattern), $replace, $subject);
            }
        }

        return $replaced;
    }

    //
    // Converts the file size in bytes to a human readable file size
    //
    public static function fileSize($size)
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB'];

        for ($i = 0; $size > 1024; $i++) {
            $size /= 1024;
        }

        return sprintf(__('Size unit '.$units[$i]), round($size, 2));
    }

    //
    // Generate browser's title
    //
    public static function generatePageTitle($page_title, $p = null)
    {
        if (!is_array($page_title)) {
            $page_title = [$page_title];
        }

        $page_title = array_reverse($page_title);

        if ($p > 1) {
            $page_title[0] .= ' ('.sprintf(__('Page'), self::forumNumberFormat($p)).')';
        }

        $crumbs = implode(__('Title separator'), $page_title);

        return $crumbs;
    }

    /**
     * Generate breadcrumbs on top of page
     * @var $crumbs: array('optionnal/url' => 'Text displayed')
     * @var $rightCrumb: array('link' => 'url/of/action', 'text' => 'Text displayed')
     *
     * @return text
     */
    public static function generateBreadcrumbs(array $crumbs = [], array $rightCrumb = [])
    {
        \View::setPageInfo([
            'rightCrumb'    =>    $rightCrumb,
            'crumbs'    =>    $crumbs,
        ], 1
        )->addTemplate('breadcrumbs.php');
    }

    //
    // Determines the correct title for $user
    // $user must contain the elements 'username', 'title', 'posts', 'g_id' and 'g_user_title'
    //
    public static function getTitle($user)
    {
        static $ban_list;

        // If not already built in a previous call, build an array of lowercase banned usernames
        if (empty($ban_list)) {
            $ban_list = [];
            foreach (Container::get('bans') as $cur_ban) {
                $ban_list[] = utf8_strtolower($cur_ban['username']);
            }
        }

        // If the user has a custom title
        if ($user['title'] != '') {
            $user_title = self::escape($user['title']);
        }
        // If the user is banned
        elseif (in_array(utf8_strtolower($user['username']), $ban_list)) {
            $user_title = __('Banned');
        }
        // If the user group has a default user title
        elseif ($user['g_user_title'] != '') {
            $user_title = self::escape($user['g_user_title']);
        }
        // If the user is a guest
        elseif ($user['g_id'] == ForumEnv::get('FEATHER_GUEST')) {
            $user_title = __('Guest');
        }
        // If nothing else helps, we assign the default
        else {
            $user_title = __('Member');
        }

        return $user_title;
    }

    //
    // Replace censored words in $text
    //
    public static function censor($text)
    {
        static $search_for, $replace_with;

        if (!Container::get('cache')->isCached('search_for')) {
            Container::get('cache')->store('search_for', Cache::getCensoring('search_for'));
        }
        $search_for = Container::get('cache')->retrieve('search_for');

        if (!Container::get('cache')->isCached('replace_with')) {
            Container::get('cache')->store('replace_with', Cache::getCensoring('replace_with'));
        }
        $replace_with = Container::get('cache')->retrieve('replace_with');

        if (!empty($search_for) && !empty($replace_with)) {
            return substr(self::ucpPregReplace($search_for, $replace_with, ' '.$text.' '), 1, -1);
        } else {
            return $text;
        }
    }

    //
    // Fetch admin IDs
    //
    public static function getAdminIds()
    {
        // Get Slim current session
        if (!Container::get('cache')->isCached('admin_ids')) {
            Container::get('cache')->store('admin_ids', Cache::get_admin_ids());
        }

        return Container::get('cache')->retrieve('admin_ids');
    }

    //
    // Outputs markup to display a user's avatar
    //
    public static function generateAvatarMarkup($user_id)
    {
        $filetypes = ['jpg', 'gif', 'png'];
        $avatar_markup = '';

        foreach ($filetypes as $cur_type) {
            $path = ForumSettings::get('o_avatars_dir').'/'.$user_id.'.'.$cur_type;

            if (file_exists(ForumEnv::get('FEATHER_ROOT').$path) && $img_size = getimagesize(ForumEnv::get('FEATHER_ROOT').$path)) {
                $avatar_markup = '<img src="'.self::escape(Container::get('url')->base(true).'/'.$path.'?m='.filemtime(ForumEnv::get('FEATHER_ROOT').$path)).'" '.$img_size[3].' alt="" />';
                break;
            }
        }

        return $avatar_markup;
    }

    //
    // Get IP Address
    //
    public static function getIp()
    {
        if (isset(Request::getServerParams()['HTTP_CLIENT_IP'])) {
            $client = Request::getServerParams()['HTTP_CLIENT_IP'];
        }
        if (isset(Request::getServerParams()['HTTP_X_FORWARDED_FOR'])) {
            $forward = Request::getServerParams()['HTTP_X_FORWARDED_FOR'];
        }

        $remote = Request::getServerParams()['REMOTE_ADDR'];

        if (isset($client) && filter_var($client, FILTER_VALIDATE_IP)) {
            return $client;
        } elseif (isset($forward) && filter_var($forward, FILTER_VALIDATE_IP)) {
            return $forward;
        }

        return $remote;
    }

    /**
     * Timing attack safe string comparison
     *
     * Compares two strings using the same time whether they're equal or not.
     *
     * This function was added in PHP 5.6.
     *
     * Note: It can leak the length of a string when arguments of differing length are supplied.
     *
     * @param string $a Expected string.
     * @param string $b Actual, user supplied, string.
     * @return bool Whether strings are equal.
     *
     * Sourcecode from WordPress
     */
    public static function hashEquals($a, $b)
    {
        if (function_exists('hash_equals')) {
            return hash_equals((string)$a, (string)$b);
        }

        $a_length = strlen($a);
        if ($a_length !== strlen($b)) {
            return false;
        }
        $result = 0;

        // Do not attempt to "optimize" this.
        for ($i = 0; $i < $a_length; $i++) {
            $result |= ord($a[ $i ]) ^ ord($b[ $i ]);
        }

        return $result === 0;
    }

    /**
     * Hash a user password using BCRYPT
     * Replaces old sha1 password hashing verification
     * Requires PHP >= 5.5
     *
     * @param  string $password User password
     * @return string           Hashed password
     */
    public static function passwordHash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Compare an inputed password with the one in database for a user
     * Requires PHP >= 5.5
     *
     * @param  string $password Inputed password
     * @param  string $hash     Password stored in database
     * @return bool             Do the passwords match ?
     */
    public static function passwordVerify($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
