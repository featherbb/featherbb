<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB;

class Utils
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
    }

    //
    // Return current timestamp (with microseconds) as a float
    //
    function get_microtime()
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
    public function strip_bad_multibyte_chars($str)
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
    public function forum_number_format($number, $decimals = 0)
    {
        return is_numeric($number) ? number_format($number, $decimals, __('lang_decimal_point'), __('lang_thousands_sep')) : $number;
    }

    //
    // Format a time string according to $time_format and time zones
    //
    public function format_time($timestamp, $date_only = false, $date_format = null, $time_format = null, $time_only = false, $no_text = false)
    {
        global $forum_date_formats, $forum_time_formats;

        if ($timestamp == '') {
            return __('Never');
        }

        $diff = ($this->feather->user->timezone + $this->feather->user->dst) * 3600;
        $timestamp += $diff;
        $now = time();

        if (is_null($date_format)) {
            $date_format = $forum_date_formats[$this->feather->user->date_format];
        }

        if (is_null($time_format)) {
            $time_format = $forum_time_formats[$this->feather->user->time_format];
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
    public function strlen($str)
    {
        return utf8_strlen($str);
    }


    //
    // Convert \r\n and \r to \n
    //
    public function linebreaks($str)
    {
        return str_replace(array("\r\n", "\r"), "\n", $str);
    }


    //
    // A wrapper for utf8_trim for compatibility
    //
    public function trim($str, $charlist = false)
    {
        return is_string($str) ? utf8_trim($str, $charlist) : '';
    }

    //
    // Checks if a string is in all uppercase
    //
    public function is_all_uppercase($string)
    {
        return utf8_strtoupper($string) == $string && utf8_strtolower($string) != $string;
    }

    //
    // Replace string matching regular expression
    //
    // This function takes care of possibly disabled unicode properties in PCRE builds
    //
    public function ucp_preg_replace($pattern, $replace, $subject, $callback = false)
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
    // Compute a hash of $str
    //
    public static function feather_hash($str)
    {
        return sha1($str);
    }
}
