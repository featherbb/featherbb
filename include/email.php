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

// Define line breaks in mail headers; possible values can be PHP_EOL, "\r\n", "\n" or "\r"
if (!defined('FORUM_EOL')) {
    define('FORUM_EOL', PHP_EOL);
}

require FEATHER_ROOT.'include/utf8/utils/ascii.php';

//
// Validate an email address
//
function is_valid_email($email)
{
    if (strlen($email) > 80) {
        return false;
    }

    return preg_match('%^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|("[^"]+"))@((\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\])|(([a-zA-Z\d\-]+\.)+[a-zA-Z]{2,}))$%', $email);
}


//
// Check if $email is banned
//
function is_banned_email($email)
{
    global $feather_bans;

    foreach ($feather_bans as $cur_ban) {
        if ($cur_ban['email'] != '' &&
            ($email == $cur_ban['email'] ||
            (strpos($cur_ban['email'], '@') === false && stristr($email, '@'.$cur_ban['email'])))) {
            return true;
        }
    }

    return false;
}


//
// Only encode with base64, if there is at least one unicode character in the string
//
function encode_mail_text($str)
{
    if (utf8_is_ascii($str)) {
        return $str;
    }

    return '=?UTF-8?B?'.base64_encode($str).'?=';
}

//
// Extract blocks from a text with a starting and ending string
// This function always matches the most outer block so nesting is possible
//
function extract_blocks($text, $start, $end, $retab = true)
{
    global $feather_config;

    $code = array();
    $start_len = strlen($start);
    $end_len = strlen($end);
    $regex = '%(?:'.preg_quote($start, '%').'|'.preg_quote($end, '%').')%';
    $matches = array();

    if (preg_match_all($regex, $text, $matches)) {
        $counter = $offset = 0;
        $start_pos = $end_pos = false;

        foreach ($matches[0] as $match) {
            if ($match == $start) {
                if ($counter == 0) {
                    $start_pos = strpos($text, $start);
                }
                $counter++;
            } elseif ($match == $end) {
                $counter--;
                if ($counter == 0) {
                    $end_pos = strpos($text, $end, $offset + 1);
                }
                $offset = strpos($text, $end, $offset + 1);
            }

            if ($start_pos !== false && $end_pos !== false) {
                $code[] = substr($text, $start_pos + $start_len,
                    $end_pos - $start_pos - $start_len);
                $text = substr_replace($text, "\1", $start_pos,
                    $end_pos - $start_pos + $end_len);
                $start_pos = $end_pos = false;
                $offset = 0;
            }
        }
    }

    if ($feather_config['o_indent_num_spaces'] != 8 && $retab) {
        $spaces = str_repeat(' ', $feather_config['o_indent_num_spaces']);
        $text = str_replace("\t", $spaces, $text);
    }

    return array($code, $text);
}


//
// Make a post email safe
//
function bbcode2email($text, $wrap_length = 72)
{
    static $base_url;

    if (!isset($base_url)) {
        $base_url = get_base_url();
    }

    $text = feather_trim($text, "\t\n ");

    $shortcut_urls = array(
        'topic' => '/topic/$1/',
        'post' => '/post/$1/#p$1',
        'forum' => '/forum/$1/',
        'user' => '/user/$1/',
    );

    // Split code blocks and text so BBcode in codeblocks won't be touched
    list($code, $text) = extract_blocks($text, '[code]', '[/code]');

    // Strip all bbcodes, except the quote, url, img, email, code and list items bbcodes
    $text = preg_replace(array(
        '%\[/?(?!(?:quote|url|topic|post|user|forum|img|email|code|list|\*))[a-z]+(?:=[^\]]+)?\]%i',
        '%\n\[/?list(?:=[^\]]+)?\]%i' // A separate regex for the list tags to get rid of some whitespace
    ), '', $text);

    // Match the deepest nested bbcode
    // An adapted example from Mastering Regular Expressions
    $match_quote_regex = '%
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

    $url_index = 1;
    $url_stack = array();
    while (preg_match($match_quote_regex, $text, $matches)) {
        // Quotes
        if ($matches[1] == 'quote') {
            // Put '>' or '> ' at the start of a line
            $replacement = preg_replace(
                array('%^(?=\>)%m', '%^(?!\>)%m'),
                array('>', '> '),
                $matches[2]." said:\n".$matches[3]);
        }

        // List items
        elseif ($matches[1] == '*') {
            $replacement = ' * '.$matches[3];
        }

        // URLs and emails
        elseif (in_array($matches[1], array('url', 'email'))) {
            if (!empty($matches[2])) {
                $replacement = '['.$matches[3].']['.$url_index.']';
                $url_stack[$url_index] = $matches[2];
                $url_index++;
            } else {
                $replacement = '['.$matches[3].']';
            }
        }

        // Images
        elseif ($matches[1] == 'img') {
            if (!empty($matches[2])) {
                $replacement = '['.$matches[2].']['.$url_index.']';
            } else {
                $replacement = '['.basename($matches[3]).']['.$url_index.']';
            }

            $url_stack[$url_index] = $matches[3];
            $url_index++;
        }

        // Topic, post, forum and user URLs
        elseif (in_array($matches[1], array('topic', 'post', 'forum', 'user'))) {
            $url = isset($shortcut_urls[$matches[1]]) ? $base_url.$shortcut_urls[$matches[1]] : '';

            if (!empty($matches[2])) {
                $replacement = '['.$matches[3].']['.$url_index.']';
                $url_stack[$url_index] = str_replace('$1', $matches[2], $url);
                $url_index++;
            } else {
                $replacement = '['.str_replace('$1', $matches[3], $url).']';
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
    if ($url_stack) {
        $text .= "\n\n";
        foreach ($url_stack as $i => $url) {
            $text .= "\n".' ['.$i.']: '.$url;
        }
    }

    // Wrap lines if $wrap_length is higher than -1
    if ($wrap_length > -1) {
        // Split all lines and wrap them individually
        $parts = explode("\n", $text);
        foreach ($parts as $k => $part) {
            preg_match('%^(>+ )?(.*)%', $part, $matches);
            $parts[$k] = wordwrap($matches[1].$matches[2], $wrap_length -
                strlen($matches[1]), "\n".$matches[1]);
        }

        return implode("\n", $parts);
    } else {
        return $text;
    }
}


//
// Wrapper for PHP's mail()
//
function pun_mail($to, $subject, $message, $reply_to_email = '', $reply_to_name = '')
{
    global $feather_config, $lang_common;

    // Use \r\n for SMTP servers, the system's line ending for local mailers
    $smtp = $feather_config['o_smtp_host'] != '';
    $EOL = $smtp ? "\r\n" : FORUM_EOL;

    // Default sender/return address
    $from_name = sprintf($lang_common['Mailer'], $feather_config['o_board_title']);
    $from_email = $feather_config['o_webmaster_email'];

    // Do a little spring cleaning
    $to = feather_trim(preg_replace('%[\n\r]+%s', '', $to));
    $subject = feather_trim(preg_replace('%[\n\r]+%s', '', $subject));
    $from_email = feather_trim(preg_replace('%[\n\r:]+%s', '', $from_email));
    $from_name = feather_trim(preg_replace('%[\n\r:]+%s', '', str_replace('"', '', $from_name)));
    $reply_to_email = feather_trim(preg_replace('%[\n\r:]+%s', '', $reply_to_email));
    $reply_to_name = feather_trim(preg_replace('%[\n\r:]+%s', '', str_replace('"', '', $reply_to_name)));

    // Set up some headers to take advantage of UTF-8
    $from = '"'.encode_mail_text($from_name).'" <'.$from_email.'>';
    $subject = encode_mail_text($subject);

    $headers = 'From: '.$from.$EOL.'Date: '.gmdate('r').$EOL.'MIME-Version: 1.0'.$EOL.'Content-transfer-encoding: 8bit'.$EOL.'Content-type: text/plain; charset=utf-8'.$EOL.'X-Mailer: FeatherBB Mailer';

    // If we specified a reply-to email, we deal with it here
    if (!empty($reply_to_email)) {
        $reply_to = '"'.encode_mail_text($reply_to_name).'" <'.$reply_to_email.'>';

        $headers .= $EOL.'Reply-To: '.$reply_to;
    }

    // Make sure all linebreaks are LF in message (and strip out any NULL bytes)
    $message = str_replace("\0", '', feather_linebreaks($message));
    $message = str_replace("\n", $EOL, $message);
    
    $mailer = $smtp ? 'smtp_mail' : 'mail';
    $mailer($to, $subject, $message, $headers);
}


//
// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com)
// They deserve all the credit for writing it. I made small modifications for it to suit PunBB and its coding standards
//
function server_parse($socket, $expected_response)
{
    $server_response = '';
    while (substr($server_response, 3, 1) != ' ') {
        if (!($server_response = fgets($socket, 256))) {
            error('Couldn\'t get mail server response codes. Please contact the forum administrator.', __FILE__, __LINE__);
        }
    }

    if (!(substr($server_response, 0, 3) == $expected_response)) {
        error('Unable to send email. Please contact the forum administrator with the following error message reported by the SMTP server: "'.$server_response.'"', __FILE__, __LINE__);
    }
}


//
// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com)
// They deserve all the credit for writing it. I made small modifications for it to suit PunBB and its coding standards.
//
function smtp_mail($to, $subject, $message, $headers = '')
{
    global $feather_config;
    static $local_host;

    $recipients = explode(',', $to);

    // Sanitize the message
    $message = str_replace("\r\n.", "\r\n..", $message);
    $message = (substr($message, 0, 1) == '.' ? '.'.$message : $message);

    // Are we using port 25 or a custom port?
    if (strpos($feather_config['o_smtp_host'], ':') !== false) {
        list($smtp_host, $smtp_port) = explode(':', $feather_config['o_smtp_host']);
    } else {
        $smtp_host = $feather_config['o_smtp_host'];
        $smtp_port = 25;
    }

    if ($feather_config['o_smtp_ssl'] == '1') {
        $smtp_host = 'ssl://'.$smtp_host;
    }

    if (!($socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 15))) {
        error('Could not connect to smtp host "'.$feather_config['o_smtp_host'].'" ('.$errno.') ('.$errstr.')', __FILE__, __LINE__);
    }

    server_parse($socket, '220');

    if (!isset($local_host)) {
        // Here we try to determine the *real* hostname (reverse DNS entry preferably)
        $local_host = php_uname('n');

        // Able to resolve name to IP
        if (($local_addr = @gethostbyname($local_host)) !== $local_host) {
            // Able to resolve IP back to name
            if (($local_name = @gethostbyaddr($local_addr)) !== $local_addr) {
                $local_host = $local_name;
            }
        }
    }

    if ($feather_config['o_smtp_user'] != '' && $feather_config['o_smtp_pass'] != '') {
        fwrite($socket, 'EHLO '.$local_host."\r\n");
        server_parse($socket, '250');

        fwrite($socket, 'AUTH LOGIN'."\r\n");
        server_parse($socket, '334');

        fwrite($socket, base64_encode($feather_config['o_smtp_user'])."\r\n");
        server_parse($socket, '334');

        fwrite($socket, base64_encode($feather_config['o_smtp_pass'])."\r\n");
        server_parse($socket, '235');
    } else {
        fwrite($socket, 'HELO '.$local_host."\r\n");
        server_parse($socket, '250');
    }

    fwrite($socket, 'MAIL FROM: <'.$feather_config['o_webmaster_email'].'>'."\r\n");
    server_parse($socket, '250');

    foreach ($recipients as $email) {
        fwrite($socket, 'RCPT TO: <'.$email.'>'."\r\n");
        server_parse($socket, '250');
    }

    fwrite($socket, 'DATA'."\r\n");
    server_parse($socket, '354');

    fwrite($socket, 'Subject: '.$subject."\r\n".'To: <'.implode('>, <', $recipients).'>'."\r\n".$headers."\r\n\r\n".$message."\r\n");

    fwrite($socket, '.'."\r\n");
    server_parse($socket, '250');

    fwrite($socket, 'QUIT'."\r\n");
    fclose($socket);

    return true;
}
