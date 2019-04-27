<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

use FeatherBB\Core\Interfaces\Container;
use FeatherBB\Core\Interfaces\ForumSettings;

class Email
{
    //
    // Validate an email address
    //
    public static function isValidEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return false;
    }


    //
    // Check if $email is banned
    //
    public static function isBannedEmail($email)
    {
        foreach (Container::get('bans') as $curBan) {
            if ($curBan['email'] != '' &&
                ($email == $curBan['email'] ||
                    (strpos($curBan['email'], '@') === false && stristr($email, '@' . $curBan['email'])))
            ) {
                return true;
            }
        }

        return false;
    }


    //
    // Only encode with base64, if there is at least one unicode character in the string
    //
    public static function encodeMailText($str)
    {
        if (self::utf8_is_ascii($str)) {
            return $str;
        }

        return '=?UTF-8?B?' . base64_encode($str) . '?=';
    }

    /**
     * Tests whether a string contains only 7bit ASCII bytes with device
     * control codes omitted. The device control codes can be found on the
     * second table here: http://www.w3schools.com/tags/ref_ascii.asp
     */
    private static function utf8_is_ascii($str)
    {
        // Search for any bytes which are outside the ASCII range...
        return (preg_match('/(?:[^\x00-\x7F])/', $str) !== 1);
    }

    //
    // Extract blocks from a text with a starting and ending string
    // This public static function always matches the most outer block so nesting is possible
    //
    public static function extractBlocks($text, $start, $end, $retab = true)
    {
        $code = [];
        $startLen = strlen($start);
        $endLen = strlen($end);
        $regex = '%(?:' . preg_quote($start, '%') . '|' . preg_quote($end, '%') . ')%';
        $matches = [];

        if (preg_match_all($regex, $text, $matches)) {
            $counter = $offset = 0;
            $startPos = $endPos = false;

            foreach ($matches[0] as $match) {
                if ($match == $start) {
                    if ($counter == 0) {
                        $startPos = strpos($text, $start);
                    }
                    $counter++;
                } elseif ($match == $end) {
                    $counter--;
                    if ($counter == 0) {
                        $endPos = strpos($text, $end, $offset + 1);
                    }
                    $offset = strpos($text, $end, $offset + 1);
                }

                if ($startPos !== false && $endPos !== false) {
                    $code[] = substr($text, $startPos + $startLen,
                        $endPos - $startPos - $startLen);
                    $text = substr_replace($text, "\1", $startPos,
                        $endPos - $startPos + $endLen);
                    $startPos = $endPos = false;
                    $offset = 0;
                }
            }
        }

        if (ForumSettings::get('o_indent_num_spaces') != 8 && $retab) {
            $spaces = str_repeat(' ', ForumSettings::get('o_indent_num_spaces'));
            $text = str_replace("\t", $spaces, $text);
        }

        return [$code, $text];
    }


    //
    // Make a post email safe
    //
    public static function bbcode2email($text, $wrapLength = 72)
    {
        static $baseUrl;

        $replacement = null;

        if (!isset($baseUrl)) {
            $baseUrl = Url::base();
        }

        $text = Utils::trim($text, "\t\n ");

        $shortcutUrls = [
            'topic' => '/topic/$1/',
            'post' => '/post/$1/#p$1',
            'forum' => '/forum/$1/',
            'user' => '/user/$1/',
        ];

        // Split code blocks and text so BBcode in codeblocks won't be touched
        list($code, $text) = self::extractBlocks($text, '[code]', '[/code]');

        // Strip all bbcodes, except the quote, url, img, email, code and list items bbcodes
        $text = preg_replace([
            '%\[/?(?!(?:quote|url|topic|post|user|forum|img|email|code|list|\*))[a-z]+(?:=[^\]]+)?\]%i',
            '%\n\[/?list(?:=[^\]]+)?\]%i' // A separate regex for the list tags to get rid of some whitespace
        ], '', $text);

        // Match the deepest nested bbcode
        // An adapted example from Mastering Regular Expressions
        $matchQuoteRegex = '%
            \[(quote|\*|url|img|email|topic|post|user|forum)(?:=([^\]]+))?\]
            (
                (?>[^\[]*)
                (?>
                    (?!\[/?\1(?:=[^\]]+)?\])
                    \[
                    [^\[]*
                )*
            )
            \[/\1\]
        %ix';

        $urlIndex = 1;
        $urlStack = [];
        while (preg_match($matchQuoteRegex, $text, $matches)) {
            // Quotes
            if ($matches[1] == 'quote') {
                // Put '>' or '> ' at the start of a line
                $replacement = preg_replace(
                    ['%^(?=\>)%m', '%^(?!\>)%m'],
                    ['>', '> '],
                    $matches[2] . " said:\n" . $matches[3]);
            } // List items
            elseif ($matches[1] == '*') {
                $replacement = ' * ' . $matches[3];
            } // URLs and emails
            elseif (in_array($matches[1], ['url', 'email'])) {
                if (!empty($matches[2])) {
                    $replacement = '[' . $matches[3] . '][' . $urlIndex . ']';
                    $urlStack[$urlIndex] = $matches[2];
                    $urlIndex++;
                } else {
                    $replacement = '[' . $matches[3] . ']';
                }
            } // Images
            elseif ($matches[1] == 'img') {
                if (!empty($matches[2])) {
                    $replacement = '[' . $matches[2] . '][' . $urlIndex . ']';
                } else {
                    $replacement = '[' . basename($matches[3]) . '][' . $urlIndex . ']';
                }

                $urlStack[$urlIndex] = $matches[3];
                $urlIndex++;
            } // Topic, post, forum and user URLs
            elseif (in_array($matches[1], ['topic', 'post', 'forum', 'user'])) {
                $url = isset($shortcutUrls[$matches[1]]) ? $baseUrl . $shortcutUrls[$matches[1]] : '';

                if (!empty($matches[2])) {
                    $replacement = '[' . $matches[3] . '][' . $urlIndex . ']';
                    $urlStack[$urlIndex] = str_replace('$1', $matches[2], $url);
                    $urlIndex++;
                } else {
                    $replacement = '[' . str_replace('$1', $matches[3], $url) . ']';
                }
            }

            // Update the main text if there is a replacement
            if (!is_null($replacement)) {
                $text = str_replace($matches[0], $replacement, $text);
                $replacement = null;
            }
        }

        // Put code blocks and text together
        if (isset($code)) {
            $parts = explode("\1", $text);
            $text = '';
            foreach ($parts as $i => $part) {
                $text .= $part;
                if (isset($code[$i])) {
                    $text .= trim($code[$i], "\n\r");
                }
            }
        }

        // Put URLs at the bottom
        if ($urlStack) {
            $text .= "\n\n";
            foreach ($urlStack as $i => $url) {
                $text .= "\n" . ' [' . $i . ']: ' . $url;
            }
        }

        // Wrap lines if $wrapLength is higher than -1
        if ($wrapLength > -1) {
            // Split all lines and wrap them individually
            $parts = explode("\n", $text);
            foreach ($parts as $k => $part) {
                preg_match('%^(>+ )?(.*)%', $part, $matches);
                $parts[$k] = wordwrap($matches[1] . $matches[2], $wrapLength -
                    strlen($matches[1]), "\n" . $matches[1]);
            }

            return implode("\n", $parts);
        } else {
            return $text;
        }
    }


    //
    // Wrapper for PHP's mail()
    //
    public static function send($to, $subject, $message, $replyToEmail = '', $replyToName = '')
    {
        // Define line breaks in mail headers; possible values can be PHP_EOL, "\r\n", "\n" or "\r"
        if (!defined('FORUM_EOL')) {
            define('FORUM_EOL', PHP_EOL);
        }

        // Use \r\n for SMTP servers, the system's line ending for local mailers
        $smtp = ForumSettings::get('o_smtp_host') != '';
        $eOL = $smtp ? "\r\n" : FORUM_EOL;

        // Default sender/return address
        $fromName = sprintf(__('Mailer'), ForumSettings::get('o_board_title'));
        $fromEmail = ForumSettings::get('o_webmaster_email');

        // Do a little spring cleaning
        $to = Utils::trim(preg_replace('%[\n\r]+%s', '', $to));
        $subject = Utils::trim(preg_replace('%[\n\r]+%s', '', $subject));
        $fromEmail = Utils::trim(preg_replace('%[\n\r:]+%s', '', $fromEmail));
        $fromName = Utils::trim(preg_replace('%[\n\r:]+%s', '', str_replace('"', '', $fromName)));
        $replyToEmail = Utils::trim(preg_replace('%[\n\r:]+%s', '', $replyToEmail));
        $replyToName = Utils::trim(preg_replace('%[\n\r:]+%s', '', str_replace('"', '', $replyToName)));

        // Set up some headers to take advantage of UTF-8
        $from = '"' . self::encodeMailText($fromName) . '" <' . $fromEmail . '>';
        $subject = self::encodeMailText($subject);

        $headers = 'From: ' . $from . $eOL . 'Date: ' . gmdate('r') . $eOL . 'MIME-Version: 1.0' . $eOL . 'Content-transfer-encoding: 8bit' . $eOL . 'Content-type: text/plain; charset=utf-8' . $eOL . 'X-Mailer: FeatherBB Mailer';

        // If we specified a reply-to email, we deal with it here
        if (!empty($replyToEmail)) {
            $replyTo = '"' . self::encodeMailText($replyToName) . '" <' . $replyToEmail . '>';

            $headers .= $eOL . 'Reply-To: ' . $replyTo;
        }

        // Make sure all linebreaks are LF in message (and strip out any NULL bytes)
        $message = str_replace("\0", '', Utils::linebreaks($message));
        $message = str_replace("\n", $eOL, $message);

        if ($smtp) {
            return self::smtpMail($to, $subject, $message, $headers);
        } else {
            return mail($to, $subject, $message, $headers);
        }
    }


    //
    // This public function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com)
    // They deserve all the credit for writing it. I made small modifications for it to suit PunBB and its coding standards
    //
    public static function serverParse($socket, $expectedResponse)
    {
        $serverResponse = '';
        while (substr($serverResponse, 3, 1) != ' ') {
            if (!($serverResponse = fgets($socket, 256))) {
                throw new Error('Couldn\'t get mail server response codes. Please contact the forum administrator.', 500);
            }
        }

        if (!(substr($serverResponse, 0, 3) == $expectedResponse)) {
            throw new Error('Unable to send email. Please contact the forum administrator with the following error message reported by the SMTP server: "' . $serverResponse . '"', 500);
        }
    }


    //
    // This public static function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com)
    // They deserve all the credit for writing it. I made small modifications for it to suit PunBB and its coding standards.
    //
    public static function smtpMail($to, $subject, $message, $headers = '')
    {
        static $localHost;

        $recipients = explode(',', $to);

        // Sanitize the message
        $message = str_replace("\r\n.", "\r\n..", $message);
        $message = (substr($message, 0, 1) == '.' ? '.' . $message : $message);

        // Are we using port 25 or a custom port?
        if (strpos(ForumSettings::get('o_smtp_host'), ':') !== false) {
            list($smtpHost, $smtpPort) = explode(':', ForumSettings::get('o_smtp_host'));
        } else {
            $smtpHost = ForumSettings::get('o_smtp_host');
            $smtpPort = 25;
        }

        if (ForumSettings::get('o_smtp_ssl') == '1') {
            $smtpHost = 'ssl://' . $smtpHost;
        }

        if (!($socket = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 15))) {
            throw new Error('Could not connect to smtp host "' . ForumSettings::get('o_smtp_host') . '" (' . $errno . ') (' . $errstr . ')', 500);
        }

        self::serverParse($socket, '220');

        if (!isset($localHost)) {
            // Here we try to determine the *real* hostname (reverse DNS entry preferably)
            $localHost = php_uname('n');

            // Able to resolve name to IP
            if (($localAddr = @gethostbyname($localHost)) !== $localHost) {
                // Able to resolve IP back to name
                if (($localName = @gethostbyaddr($localAddr)) !== $localAddr) {
                    $localHost = $localName;
                }
            }
        }

        if (ForumSettings::get('o_smtp_user') != '' && ForumSettings::get('o_smtp_pass') != '') {
            fwrite($socket, 'EHLO ' . $localHost . "\r\n");
            self::serverParse($socket, '250');

            fwrite($socket, 'AUTH LOGIN' . "\r\n");
            self::serverParse($socket, '334');

            fwrite($socket, base64_encode(ForumSettings::get('o_smtp_user')) . "\r\n");
            self::serverParse($socket, '334');

            fwrite($socket, base64_encode(ForumSettings::get('o_smtp_pass')) . "\r\n");
            self::serverParse($socket, '235');
        } else {
            fwrite($socket, 'HELO ' . $localHost . "\r\n");
            self::serverParse($socket, '250');
        }

        fwrite($socket, 'MAIL FROM: <' . ForumSettings::get('o_webmaster_email') . '>' . "\r\n");
        self::serverParse($socket, '250');

        foreach ($recipients as $email) {
            fwrite($socket, 'RCPT TO: <' . $email . '>' . "\r\n");
            self::serverParse($socket, '250');
        }

        fwrite($socket, 'DATA' . "\r\n");
        self::serverParse($socket, '354');

        fwrite($socket, 'Subject: ' . $subject . "\r\n" . 'To: <' . implode('>, <', $recipients) . '>' . "\r\n" . $headers . "\r\n\r\n" . $message . "\r\n");

        fwrite($socket, '.' . "\r\n");
        self::serverParse($socket, '250');

        fwrite($socket, 'QUIT' . "\r\n");
        fclose($socket);

        return true;
    }
}
