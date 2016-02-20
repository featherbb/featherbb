<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * Parser (C) 2011 Jeff Roberson (jmrware.com)
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// This script compiles the $options, $smilies and $bbcd arrays (from bbcd_source.php script
// or from an admin page web form) into the cache/cache_parser_data.php.

// Initialize a new global parser data array $pd:
$pd = array(
    'newer_php_version'        => version_compare(PHP_VERSION, '5.2.0', '>='), // PHP version affects PCRE error checking.
    'in_signature'            => false,                // TRUE when parsing signatures, FALSE when parsing posts.
    'ipass'                    => 0,                    // Pass number (for multi-pass pre-parsing).
    'tag_stack'                => array('_ROOT_'),        // current stack trace of tags in recursive callback
    'config'                => $config,                // Array of various global parser options.

// -----------------------------------------------------------------------------
// Parser Regular Expressions. (All fully commented in 'x'-"free-spacing" mode.)
// -----------------------------------------------------------------------------
    're_smilies'            => '/ # re_smilies Rev:20110220_1200
# Match special smiley character sequences within BBCode content.
(?<=^|[>\s])                    # Only if preceeded by ">" or whitespace.
(?:%smilies%)
(?=$|[\[<\s])                   # Only if followed by "<", "[" or whitespace.
                                /Sx',

    're_color'              => '% # re_color Rev:20110220_1200
# Match a valid CSS color value. #123, #123456, or "red", "blue", etc.
^                               # Anchor to start of string.
(                               # $1: Foreground color (required).
  \#(?:[0-9A-Fa-f]{3}){1,2}     # Either a "#" and a 3 or 6 digit hex number,
| (?: maroon|red|orange|yellow| # or a recognized CSS color word.
      olive|purple|fuchsia|white|
      lime|green|navy|blue|aqua|
      teal|black|silver|gray
  )                             # End group of recognized color words.
)                               # End $1. Foreground color.
# Match optional CSS background color value. ;#123, ;#123456, or ;"red", "blue", etc.
(?:                             # Begin group for optional background color
  ;?+                           # foreground;background delimiter: e.g. "#123;#456".
  ((?1))                        # $2: Background color. (Same regex as the first.)
)?+                             # Background color spec is optional.
$                               # Anchor to end of string.
                                %ix',

    're_textile'            => '/ # re_textile Rev:20110220_1200
# Match textile inline phrase: _em_ *strong* @tt@ ^super^ ~sub~ -del- +ins+
([+\-@*_\^~])                   # $1: literal exposed start of phrase char, but
(?<=                            # only if preceded by...
  ^          [+\-@*_]           # start of string (for _em_ *strong* -del- +ins+ @code)
| \s         [+\-@*_]           # or whitespace   (for _em_ *strong* -del- +ins+ @code)
| [A-Za-z0-9)}\]>][\^~]         # or alphanum or bracket (for ^superscript^ ~subscript~).
)                               # only if preceded by whitespace or start of string.
(                               # $2: Textile phrase contents.
  [A-Za-z0-9({\[<]              # First char following delim must be alphanum or bracket.
  [^+\-@*_\^~\n]*+              # "normal*" == Zero or more non-delim, non-newline.
  (?>                           # Begin unrolling-the-loop. "(special normal*)*"
    (?!                         # One of two conditions must be true for inside delim:
      (?:(?<=[A-Za-z0-9)}\]>][.,;:!?])(?=\1(?:\s|$)))
    | (?:(?<=[A-Za-z0-9)}\]>])(?=\1(?:[\s.,;:!?]|$)))
    )[+\-@*_\^~]                # If so then not yet at phrase end. Match delim and
    [^+\-@*_\^~\n]*+            # more "normal*" non-delim, non-linefeeds.
  )*+                           # Continue unrolling. "(special normal*)*"
)                               # End $2: Textile phrase contents.
(?>
  (?:(?<=[A-Za-z0-9)}\]>][.,;:!?])(?=\1(?:\s|$)))
| (?:(?<=[A-Za-z0-9)}\]>])(?=\1(?:[\s.,;:!?]|$)))
)
\1                              # Match delim end of phrase, but only if
                                /Smx',

    're_bbcode'             => '% # re_bbcode Rev:20110220_1200
# First, match opening tag of syntax: "[TAGNAME (= ("\')ATTRIBUTE("\') )]";
\[                              # Match opening bracket of outermost opening TAGNAME tag.
(?>(%taglist%)\s*+) # $1:
(?>                             # Atomically group remainder of opening tag.
  (?:                           # Optional attribute.
    (=)\s*+                     # $2: = Optional attribute\'s equals sign delimiter, ws.
    (?:                         # Group for 1-line attribute value alternatives.
      \'([^\'\r\n\\\\]*+(?:\\\\.[^\'\r\n\\\\]*+)*+)\'  # Either $3: == single quoted,
    | "([^"\r\n\\\\]*+(?:\\\\.[^"\r\n\\\\]*+)*+)"      # or     $4: == double quoted,
    | ( [^[\]\r\n]*+            # or $5: == un-or-any-quoted. "normal*" == non-"[]"
        (?:                     # Begin "(special normal*)*" "Unrolling-the-loop" construct.
          \[[^[\]\r\n]*+\]      # Allow matching [square brackets] 1 level deep. "special".
            [^[\]\r\n]*+        # More "normal*" any non-"[]", non-newline characters.
        )*+                     # End "(special normal*)*" "Unrolling-the-loop" construct.
      )                         # End $5: Un-or-any-quoted attribute value.
    )                           # End group of attribute values alternatives.
    \s*+                        # Optional whitespace following quoted values.
  )?                            # End optional attribute group.
  \]                            # Match closing bracket of outermost opening TAGNAME tag.
)                               # End atomic group with opening tag remainder.
# Second, match the contents of the tag.
(                               # $6: Non-trimmed contents of TAGNAME tag.
  (?>                           # Atomic group for contents alternatives.
    [^\[]++                     # Option 1: Match non-tag chars (starting with non-"[").
    (?:                         # Begin "(special normal*)*" "Unrolling-the-loop" construct.
      (?!\[/?+\1[\]=\s])\[      # "special" = "[" if not start of [TAGNAME*] or [/TAGNAME].
      [^\[]*+                   # More "normal*".
    )*+                         # Zero or more "special normal*"s allowed for option 1.
  | (?:                         # or Option 2: Match non-tag chars (starting with "[").
      (?!\[/?+\1[\]=\s])\[      # "special" = "[" if not start of [TAGNAME*] or [/TAGNAME].
      [^\[]*+                   # More "normal*".
    )++                         # One or more "special normal*"s required for option 2.
  | (?R)                        # Or option 3: recursively match nested [TAGNAME]..[/TAGNAME].
  )*+                           # One of these three options as many times as necessary.
)                               # End $6: Non-trimmed contents of TAGNAME tag.
# Finally, match the closing tag.
\[/\1\s*+\]                     # Match outermost closing [/  TAGNAME  ]
                                %ix',

    're_bbtag'              => '%# re_bbtag Rev:20110220_1200
# Match open or close BBtag.
\[/?+                           # Match opening bracket of outermost opening TAGNAME tag.
(?>(%taglist%)\s*+) #$1:
(?:                             # Optional attribute.
  (=)\s*+                       # $2: = Optional attribute\'s equals sign delimiter, ws.
  (?:                           # Group for 1-line attribute value alternatives.
    \'([^\'\r\n\\\\]*+(?:\\\\.[^\'\r\n\\\\]*+)*+)\'  # Either $3: == single quoted,
  | "([^"\r\n\\\\]*+(?:\\\\.[^"\r\n\\\\]*+)*+)"      # or     $4: == double quoted,
  | ( [^[\]\r\n]*+              # or $5: == un-or-any-quoted. "normal*" == non-"[]"
      (?:                       # Begin "(special normal*)*" "Unrolling-the-loop" construct.
        \[[^[\]\r\n]*+\]        # Allow matching [square brackets] 1 level deep. "special".
          [^[\]\r\n]*+          # More "normal*" any non-"[]", non-newline characters.
      )*+                       # End loop construct. See: "Mastering Regular Expressions".
    )                           # End $5: Un-or-any-quoted attribute value.
  )                             # End group of attribute values alternatives.
  \s*+                          # Optional whitespace following quoted values.
)?                              # End optional attribute.
\]                              # Match closing bracket of outermost opening TAGNAME tag.
                                %ix',
    're_fixlist_1'            => '%# re_fixlist_1 Rev:20110220_1200
# Match and repair invalid characters at start of LIST tag (before first [*]).
^                               # Anchor to start of subject text.
(                               # $1: Substring with invalid chars to be enclosed.
  \s*+                          # Optional whitespace before first invalid char.
  (?!\[(?:\*|/list)\])          # Assert invalid char(s). (i.e. valid if [*] or [/list]).
  [^[]*                         # (Normal*) Zero or more non-[.
  (?:                           # Begin (special normal*)* "Unroll-the-loop- construct.
    (?!\[(?:\*|/list)\])        # If this [ is not the start of [*] or [/list], then
    \[                          # go ahead and match non-[*], non-[/list] left bracket.
    [^[]*                       # More (normal*).
  )*                            # End (special normal*)* "unroll-the-loop- construct.
)                               # End $1: non-whitespace before first [*] (or [/list]).
(?<!\s)                         # Backtrack to exclude any trailing whitespace.
(?=\s*\[(?:\*|/list)\])         # Done once we reach a [*] or [/list].
                                %ix',
    're_fixlist_2'            => '%# re_fixlist_2 Rev:20110220_1200
# Match and repair invalid characters between [/*] and next [*] (or [/list]].
\[/\*\]                         # Match [/*] close tag.
(                               # $1: Substring with invalid chars to be enclosed.
  \s*+                          # Optional whitespace before first invalid char.
  (?!\[(?:\*|/list)\])          # Assert invalid char(s). (i.e. valid if [*] or [/list]).
  [^[]*                         # (Normal*) Zero or more non-[.
  (?:                           # Begin (special normal*)* "Unroll-the-loop- construct.
    (?!\[(?:\*|/list)\])        # If this [ is not the start of [*] or [/list], then
    \[                          # go ahead and match non-[*], non-[/list] left bracket.
    [^[]*                       # More (normal*).
  )*                            # End (special normal*)* "unroll-the-loop- construct.
)                               # End $1: non-whitespace before first [*] (or [/list]).
(?<!\s)                         # Backtrack to exclude any trailing whitespace.
(?=\s*\[(?:\*|/list)\])         # Done once we reach a [*] or [/list].
                                %ix',
    'smilies'                => array(),                // Array of Smilies, each an array with filename and html.
    'bbcd'                    => array(),                // Array of BBCode tag definitions.

);
unset($config);

// If this server's PHP installation won't allow access to remote files,
//   then unconditionally turn off validate images option.
if (!ini_get('allow_url_fopen')) {
    $pd['config']['valid_imgs'] = false;
}

// Validate and compute replacement texts for smilies array.
$re_keys = array();                                    // Array of regex-safe smiley texts.
$file_path = ForumEnv::get('FEATHER_ROOT') . 'style/img/smilies/';                // File system path to smilies.
$url_path = Request::getServerParams()['SCRIPT_NAME'];                        // Convert abs URL to relative URL.
$url_path = preg_replace('%^https?://[^/]++(.*)$%i', '$1', $url_path) . '/style/img/smilies/';
foreach ($smilies as $smiley_text => $smiley_img) {    // Loop through all smilieys in array.
    $file = $file_path . $smiley_img['file'];        // Local file system address of smiley.
    if (!file_exists($file)) {
        continue;
    }                // Skip if the file does not exist.
    $info = getimagesize($file);                    // Fetch width & height the image.
    // Scale the smiley image to fit inside tiny smiley box; default = 15 by 15 pixels (@ 100%).
    if (isset($info) && is_array($info) && ($iw = (int)$info[0]) && ($ih = (int)$info[1])) {
        $ar = (float)$iw / (float)$ih;
        if ($iw > $ih) { // Check if landscape?
            $w = (int)((($pd['config']['smiley_size'] * 15.0) / 100.0) + 0.5);
            $h = (int)round((float)$w / $ar);
        } else {
            $h = (int)((($pd['config']['smiley_size'] * 15.0) / 100.0) + 0.5);
            $w = (int)round((float)$h * $ar);
        }
        unset($ar);
    }
    $re_keys[] = preg_quote($smiley_text, '/');        // Gather array of regex-safe smiley texts.
    $url = $url_path . $smiley_img['file'];            // url address of this smiley.
    $url = htmlspecialchars($url);                    // Make sure all [&<>""] are escaped.
    $desc = file2title($smiley_img['file']);        // Convert filename to a title.
    $format = '<img width="%d" height="%d" src="%s" alt="%s" title="%s" />';
    $pd['smilies'][$smiley_text] = array(
        'file' => $smiley_img['file'],
        'html' => sprintf($format, $w, $h, $url, $desc, $desc)
        );
}
// Assemble "the-one-regex-to-match-them-all" (smilies that is!) 8^)
$pd['re_smilies'] = str_replace('%smilies%', implode('|', $re_keys), $pd['re_smilies']);
unset($re_keys); unset($file_path); unset($url_path); unset($file);
unset($info); unset($url); unset($desc); unset($format);
unset($smiley_text); unset($smiley_img); unset($smilies);
unset($w); unset($h); unset($iw); unset($ih);

// Local arrays:
$all_tags                    = array();                // array of all tag names allowed in posts
$all_tags_re                = array();                // array of all tag names allowed in posts (preg_quoted)
$all_block_tags                = array();                // array of all block type tag names

// loop through all BBCodes to pre-assemble and initialize-once global data structures
foreach ($bbcd as $tagname => $tagdata) { // pass 1: accumulate regex pattern string fragments counting block and inline types
    $pd['bbcd'][$tagname]    = $tagdata;                // Copy initial tag data to $pd['bbcd']['tagname'].
    $tag =& $pd['bbcd'][$tagname];                    // tag is shortcut to member of $pd['bbcd']['tagname'] array
    $tag['depth']            = 0;                        // initialize tag nesting depth level to zero

    // assign default values for members that were not specified
    if (!isset($tag['in_post'])) {
        $tag['in_post']    = true;
    }            // default in_post = TRUE
    if (!isset($tag['in_sig'])) {
        $tag['in_sig']        = true;
    }            // default in_sig = TRUE
    if (!isset($tag['html_type'])) {
        $tag['html_type']    = 'inline';
    }        // default html_type = inline
    if (!isset($tag['tag_type'])) {
        $tag['tag_type']    = 'normal';
    }        // default tag_type = normal
    if (!isset($tag['nest_type'])) {
        if ($tag['html_type'] === 'inline') {
            $tag['nest_type'] = 'fix';
        }    // default inline nest_type = fix
        else {
            $tag['nest_type'] = 'err';
        }    // default block nest_type = err
    }
    if (!isset($tag['handlers'])) {
        $tag['handlers']    = array(
                'NO_ATTRIB'        => array(
                    'format' => '<'. $tag['html_name'] .'>%c_str%</'. $tag['html_name'] .'>'
                )
            );
    }
    // Loop through attribute handlers assigning default values to a_type and c_type.
    foreach ($tag['handlers'] as $key => $value) {
        $handler =& $tag['handlers'][$key];
        // Detect when width/height types are being used.
        $w_typ = (preg_match('/%[wh]_str%/', $handler['format'])) ?  'width_height' : false;
        switch ($key) {
        case 'ATTRIB':                            // Variable attribute handler.
            if (!isset($handler['a_type'])) {
                $handler['a_type'] = ($w_typ) ? $w_typ : 'text';
            }
            if (!isset($handler['c_type'])) {
                $handler['c_type'] = 'text';
            }
            break;

        case 'NO_ATTRIB':                        // No attribute handler.
            if (!isset($handler['a_type'])) {
                $handler['a_type'] = 'none';
            }
            if (!isset($handler['c_type'])) {
                $handler['c_type'] = ($w_typ) ? $w_typ : 'text';
            }
            break;

        default:                                // Fixed attribute handlers.
            if (!isset($handler['a_type'])) {
                $handler['a_type'] = ($w_typ) ? $w_typ : 'text';
            }
            if (!isset($handler['c_type'])) {
                $handler['c_type'] = 'text';
            }
            break;
        }
        ksort($handler);
    }
    unset($w_typ);
    // fill arrays with names of tags for block, inline and hidden tag categories
    if ($tagname == '_ROOT_') {
        continue;
    }        // Dont add _ROOT_ to tag lists
    $all_tags[$tagname]    = true;                    // Array of all tags. with the names stored in the $keys.
    $re_name = preg_quote($tagname);            // this name is metachar-safe to concatenate into a regex pattern string
    $all_tags_re[]                        = $re_name;
    if ($tag['html_type'] == 'block') {
        $all_block_tags[]                = $tagname;
        if (!isset($tag['depth_max'])) {
            $tag['depth_max'] = 5;                    // default block tags max depth = 5
        }
    }
    if ($tag['html_type'] == 'inline') {
        $tag['depth_max']    = 1;                    // all inline tags max depth = 1
    }
    if ($tag['tag_type'] === 'hidden') {
        $tag['depth_max'] = 1;                        // all hidden tags max depth = 1
        $tag['tags_allowed']            = array();    // no tags allowed in hidden tags.
    }
    // clean excess whitespace (added for human readable formatting above) from format conversion strings
    foreach ($tag['handlers'] as $ikey => $i) {        // loop through all tag attribute handlers
        if (isset($tag['handlers'][$ikey]['format'])) {
            $format_str =& $tag['handlers'][$ikey]['format'];
            // Strip all whitespace between tags.
            $format_str = preg_replace('/(^|>)\s++(<|$)/S', '$1$2', $format_str);
            // Consolidate consecutive whitespace into a single space.
            $format_str = preg_replace('/\s++/S', ' ', $format_str);
            // Clean out any old version byte marker cruft.
            $format_str = str_replace(array("\1", "\2"), '', $format_str);
            // Wrap all hidden chunks like so: "\1\2<tag>\1 stuff \1\2</tag>\1".
            if ($tag['tag_type'] === 'hidden' || $tag['handlers'][$ikey]['c_type'] === 'url') {
                $format_str = "\1\2". $format_str ."\1";
            } else {
                $format_str = preg_replace('/((?:<[^>]*+>)++(?:%a_str%(?:<[^>]*+>)++)?+)/S', "\1\2$1\1", $format_str);
            }
        } else {
            exit(sprintf("Compile error! \$bbcd['%s']['handlers']['%s']['format'] format string is missing!",
                $tagname, $ikey));
        }
    }
    unset($i);
    unset($ikey);
} // end pass 1

// Now we can complete the regex patterns with precise list of recognized tags.
$re_tag_names = empty($all_tags_re) ? '_ROOT_' : implode($all_tags_re, "|");
$pd['re_bbcode'] = str_replace('%taglist%', $re_tag_names, $pd['re_bbcode']);
$pd['re_bbtag'] = str_replace('%taglist%', $re_tag_names, $pd['re_bbtag']);

unset($all_tags_re); unset($re_tag_names);

foreach ($pd['bbcd'] as $tagname => $tagdata) { // pass 2: initialize allowed and excluded arrays
    $tag =& $pd['bbcd'][$tagname];                            // Alias to "tagname" member of global array
    if (!isset($tag['tags_allowed']) ||                        // if allowed_tags not specified or if
        isset($tag['tags_allowed']['all'])) {                // 'all' has been specified as an allowed tag
        $tag['tags_allowed'] =    $all_tags;                    // then create and set tags_allowed to allow all
    }
    if (isset($tag['tags_excluded'])) {                        // if tags_excluded specified
        foreach ($tag['tags_allowed'] as $iname => $value) {
            // remove them from tags_allow array
            if (isset($tag['tags_excluded'][$iname])) {
                unset($tag['tags_allowed'][$iname]);
            }        // remove tags_excluded tags from tags_allowed array
        }
    }
    if ($tag['html_type'] === 'inline') {                    // tag type is inline.
        foreach ($tag['tags_allowed'] as $iname => $value) {
            // remove them from tags_allow array
            if (in_array($iname, $all_block_tags)) {        // if this is a block type tag then remove
                unset($tag['tags_allowed'][$iname]);
            }                                                // remove tags_excluded tags from tags_allowed array
        }
    }
    // Build the (shorter/faster) excluded list to be used in the code. (discard tags_allowed[]).
    $tag['tags_excluded'] = array();
    foreach ($all_tags as $iname => $value) {
        if (!isset($tag['tags_allowed'][$iname])) {
            $tag['tags_excluded'][$iname] = true;
        }
    }
    // Hidden tags have no use for these arrays so set them to minimum.
    if ($tag['tag_type'] === 'hidden') {
        $tag['tags_excluded'] = array();
        $tag['tags_allowed'] = array();
    }
    unset($iname);
    unset($value);
    unset($tag['tags_allowed']);
    unset($tag['html_name']);
    ksort($tag);
}
unset($i); unset($iname); unset($n); unset($re_name); unset($tagname); unset($tagdata); unset($tag);


//
// SUPPORT FUNCTIONS
//

// Make a nice title out of a file name.
function file2title($file)
{
    // Strip off file extention.
    $title = preg_replace('/\.[^.]*$/', '', $file);
    // Convert underscores and dashes to spaces.
    $title = str_replace(array('_', '-'), ' ', $title);
    // Make first letter of each word uppercase.
    $title = ucwords($title);
    // Space out camelcase words.
    $title = preg_replace('/(?<=[a-z])(?=[A-Z])/', ' ', $title);
    // Make first letter of insignificant words lowercase.
    $title = preg_replace_callback('/(?!^)\b(And|At|A|In|Is|Of|The|To)\b/i', function ($m) { return strtolower($m); }, $title);
    // Ensure this is HTML-safe (No [&<>""]).
    $title = htmlspecialchars($title);
    return $title;
}

// Output the $pd global data array to the cache file. Convert to string first.
$s = "<?php // File: cache_parser_data.php. Automatically generated: " . date('Y-m-d h:i:s') . ". DO NOT EDIT!!!\n";

$s .= sprintf("\$pd = ", count($pd));
$s .= var_export($pd, true);
$s .= ";\n";

$s .= "?>";
file_put_contents(ForumEnv::get('FEATHER_ROOT').'cache/cache_parser_data.php', $s);

// Clean up our global variables.
unset($all_tags); unset($all_block_tags);
unset($bbcd); unset($format_str); unset($handler); unset($key); unset($s);
