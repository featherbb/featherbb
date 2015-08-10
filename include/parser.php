<?php

/**
 * Copyright (C) 2015 FeatherBB
 * Parser (C) 2011 Jeff Roberson (jmrware.com)
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

define('FEATHER_PARSER', '11-Feb-2011 13:33');

// globals. we share one array: $pd
if (file_exists(FEATHER_ROOT.'cache/cache_parser_data.php')) { // If file already exists
    require_once(FEATHER_ROOT.'cache/cache_parser_data.php');
} else { // It needs to be re-generated.
    require_once(FEATHER_ROOT.'include/bbcd_source.php');
    require_once(FEATHER_ROOT.'include/bbcd_compile.php');
}
// !!!! AVOIDING PCRE STACK OVERFLOWS WHICH SEG-FAULT CRASH APACHE/PHP !!!!
// By default, PHP sets up pcre.recursion_limit way too high (100000). According
// to PCRE documentation, a sensible value for this parameter is the stacksize
// of the PCRE executable, divided by 500. The Apache executable for Windows is
// built with a 256KB stack, but most *nix installations set a stack size of 8MB.
// We need to set the PCRE pcre.recursion_limit to the stacksize / 500. If this
// precaution is not done, then an overly large subject text will cause the
// executable to stack-overflow, and seg-fault crash with no warning. Taking the
// following precaution, prevents this severe error and allows the program to
// gracefully recover and display an appropriate error message.
if (isset($_ENV['OS']) && $_ENV['OS'] === "Windows_NT") { // Are we: Win NT, 2K, XP, Vista or 7)?
    ini_set("pcre.recursion_limit", "524");        // 256KB / 500 = 524
} else {                                        // Otherwise assume we are on a *nix box.
    ini_set("pcre.recursion_limit", "16777");    // 8MB / 500 = 16777
}
//
// Convert open urls into clickable links
//
function linkify($text)
{
    return preg_replace_callback('/ # Rev:20110220_1200 github.com\/jmrware\/LinkifyURL
	# Match http & ftp URL that is not already linkified.
	  # Alternative 1: URL delimited by (parentheses).
	  (\()					   # $1	 "(" start delimiter.
	  ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $2: URL.
	  (\))					   # $3: ")" end delimiter.
	| # Alternative 2: URL delimited by [square brackets].
	  (\[)					   # $4: "[" start delimiter.
	  ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $5: URL.
	  (\])					   # $6: "]" end delimiter.
	| # Alternative 3: URL delimited by {curly braces}.
	  (\{)					   # $7: "{" start delimiter.
	  ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $8: URL.
	  (\})					   # $9: "}" end delimiter.
	| # Alternative 4: URL delimited by <angle brackets>.
	  (<|&(?:lt|\#60|\#x3c);)  # $10: "<" start delimiter (or HTML entity).
	  ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $11: URL.
	  (>|&(?:gt|\#62|\#x3e);)  # $12: ">" end delimiter (or HTML entity).
	| # Alternative 5: URL not delimited by (), [], {} or <>.
	  (						   # $13: Prefix proving URL not already linked.
		(?: ^				   # Can be a beginning of line or string, or
		| [^=\s\'"\]]		   # a non-"=", non-quote, non-"]", followed by
		) \s*[\'"]?			   # optional whitespace and optional quote;
	  | [^=\s]\s+			   # or... a non-equals sign followed by whitespace.
	  )						   # End $13. Non-prelinkified-proof prefix.
	  ( \b					   # $14: Other non-delimited URL.
		(?:ht|f)tps?:\/\/	   # Required literal http, https, ftp or ftps prefix.
		[a-z0-9\-._~!$\'()*+,;=:\/?#[\]@%]+ # All URI chars except "&" (normal*).
		(?:					   # Either on a "&" or at the end of URI.
		  (?!				   # Allow a "&" char only if not start of an...
			&(?:gt|\#0*62|\#x0*3e);					 # HTML ">" entity, or
		  | &(?:amp|apos|quot|\#0*3[49]|\#x0*2[27]); # a [&\'"] entity if
			[.!&\',:?;]?		# followed by optional punctuation then
			(?:[^a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]|$)	# a non-URI char or EOS.
		  ) &				   # If neg-assertion true, match "&" (special).
		  [a-z0-9\-._~!$\'()*+,;=:\/?#[\]@%]* # More non-& URI chars (normal*).
		)*					   # Unroll-the-loop (special normal*)*.
		[a-z0-9\-_~$()*+=\/#[\]@%]	# Last char can\'t be [.!&\',;:?]
	  )						   # End $14. Other non-delimited URL.
	/imx', '_linkify_callback', $text);
//	  $url_replace = '$1$4$7$10$13[url]$2$5$8$11$14[/url]$3$6$9$12';
}
function _linkify_callback($m)
{ // Only linkify valid urls.
    $url = $m[2] . $m[5] . $m[8] . $m[11] . $m[14];
    if (is_array($u = url_valid($url))) {
        if (preg_match('%\.(?:jpe?g|gif|png)$%Si', $u['path_abempty'])) {
            return    $m[1].$m[4].$m[7].$m[10].$m[13] .'[img]'. $u['url'] .'[/img]'. $m[3].$m[6].$m[9].$m[12];
        } else {
            return    $m[1].$m[4].$m[7].$m[10].$m[13] .'[url]'. $u['url'] .'[/url]'. $m[3].$m[6].$m[9].$m[12];
        }
    } else {
        return    $m[1].$m[4].$m[7].$m[10].$m[13].        $url.               $m[3].$m[6].$m[9].$m[12];
    }
}
/* ***********************************************************
callback function _preparse_bbcode_callback($matches)

  This is the callback function for the main pre-parser. This routine is
called when the preg_replace_callback function within the preparse_bbcode()
function matches one BBCode open/close pair. The BBCode tag components are
passed in $matches. This routine checks for various error conditions and
repairs some of them. Erroneous code that cannot be fixed is wrapped in the
special error BBCode tag: ERR.

Parameters: (See the BBcode regex to see how each of these parameters are captured.)
    $matches[0];					=  ([TAG=att]..content..[/TAG])	// The whole match.
    $matches[1];	$tagname		=  (TAG)				// The BBCode tag name.
    $matches[2];					=  (=)					// Attribute equals sign delimiter.
    $matches[3];	$attribute		= '(attribute)'			// Attribute within single quotes, or
    $matches[4];	$attribute		= "(attribute)"			// Attribute within double quotes or
    $matches[5];	$attribute		=  (attribute)			// Attribute within no or any quotes.
    $matches[6];	$contents		=  (tag contents)		// BBCode tag contents.
*********************************************************** */
function _preparse_bbcode_callback($matches)
{
    global $lang_common, $errors, $pd;
    
    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    // Initialize some local variables. Use reference variables where possible.
    $tagname =& $matches[1];                                // BBCode tag name.
    $contents =& $matches[6];                                // BBCode tag contents.
    $tag =& $pd['bbcd'][$tagname];                            // alias to this tags array element of the BBCD database
    $parent = end($pd['tag_stack']);                        // Name of parent tag. ("_ROOT_" is base parent tag).
/*	$new_errors = array();	*/                                // BBCode tag error messages. (Create on error.)

    // First things first.
    $tag['depth']++;                                        // Increment tag-specific nesting level depth.
    $tagname = strtolower($tagname);                        // Force lowercase tags name.
    array_push($pd['tag_stack'], $tagname);                    // Push this tags name onto the tag stack.

    // ---------------------------------------------------------------------------
    // Recursively parse any nested BBCode tag markup (unless tag type is hidden):
    // ---------------------------------------------------------------------------
    if ($tag['tag_type'] !== 'hidden' && strpos($contents, '[') !== false) {
        $contents = preg_replace_callback($pd['re_bbcode'], '_preparse_bbcode_callback', $contents);
        if ($contents === null) {
            // On error, preg_replace_callback returns NULL.
 // Error #1: '(%s) Message is too long or too complex. Please shorten.'
            $new_errors[] = sprintf($lang_common['BBerr pcre'], preg_error());
            $contents = ''; // Zero out the contents.
        }
    }
    // ---------------------------------------------------------------------------------------
    // Process optional $attribute. Set $fmt_open, $fmt_close and $handler based on attribute.
    // ---------------------------------------------------------------------------------------
    $fmt_close = '[/'. $tagname .']';                        // BBCode closing tag format specifier string.
    if ($matches[2]) {                                        // Check if attribute specified?
        // Attribute specified. Pick value from one of the three possible quote delimitations.
        if ($matches[3]) {                                    // Non-empty single-quoted value.
            $attribute =& $matches[3];                        // Set attribute to quoted content.
            $fmt_open = '['. $tagname .'=\'%a_str%\']';        // Set 'single-quoted' opening format.
        } elseif ($matches[4]) {                                // Non-empty double-quoted value.
            $attribute =& $matches[4];                        // Set attribute to quoted content.
            $fmt_open = '['. $tagname .'="%a_str%"]';        // Set "double-quoted" opening format.
        } elseif ($matches[5]) {                                // Non-empty un-or-any-quoted value.
            $attribute =& $matches[5];                        // Set attribute to unquoted content.
            $fmt_open = '['. $tagname .'=%a_str%]';            // Set un-'or'-"any"-quoted opening format.
        } else {                                                // Otherwise must be empty.
            $attribute = '';                                // Set empty attribute.
            $fmt_open = '['. $tagname .'=%a_str%]';            // Set empty-attribute opening format.
        }
        // Consolidate consecutive attribute whitespace to a single space. Trim start and end.
        $attribute = preg_replace(array('/\s++/S', '/^ /', '/ $/'), array(' ', '', ''), $attribute);

        // Determine attribute handler: fixed or variable or none.
        if (isset($tag['handlers'][$attribute])) {            // If attribute matches handler key
            $handler =& $tag['handlers'][$attribute];        // use the fixed-attribute handler.
        } elseif (isset($tag['handlers']['ATTRIB'])) {        // Else if we have one, use this tags
            $handler =& $tag['handlers']['ATTRIB'];            // variable attribute handler. Otherwise...
        } elseif (isset($tag['handlers']['NO_ATTRIB']) &&    // Otherwise we have an erroneous attribute
                            count($tag['handlers']) === 1) {
            // which is either unexpected or unrecognized.
 // Error #2: 'Unexpected attribute: "%1$s". (No attribute allowed for [%2$s].'.
            $handler =& $pd['bbcd']['_ROOT_']['handlers']['NO_ATTRIB'];
            $new_errors[] = sprintf($lang_common['BBerr unexpected attribute'], $attribute, $tagname);
        } else { // Error #3: 'Unrecognized attribute: "%1$s", is not valid for [%2$s].'
            $handler =& $pd['bbcd']['_ROOT_']['handlers']['NO_ATTRIB'];
            $new_errors[] = sprintf($lang_common['BBerr unrecognized attribute'], $attribute, $tagname);
        }
        // Make sure attribute does nor contain a valid BBcode tag.
        if (preg_match($pd['re_bbtag'], $attribute)) { // Error #4: 'Attribute may NOT contain open or close bbcode tags'
            $handler =& $pd['bbcd']['_ROOT_']['handlers']['NO_ATTRIB'];
            $new_errors[] = $lang_common['BBerr bbcode attribute'];
        }
        // Validate and filter tag's attribute value if and according to custom attribute regex.
        if (isset($handler['a_regex'])) { // Check if this tag has an attribute regex? (very rare)
            if (preg_match($handler['a_regex'], $attribute, $m)) { // Yes. Check if regex matches attribute?
                $attribute = $m[1];
            } else { // Error #4b: 'Invalid attribute, [%s] requires specific attribute.'
                $new_errors[] = sprintf($lang_common['BBerr invalid attrib'], $tagname);
            }
        }
    } else { // Attribute not specified. Use the NO_ATTRIB handler if it exixts else error.
        $attribute = '';                                    // No attribute? Make it so.
        $fmt_open = '['. $tagname .']';                        // Set no-attribute fmt_open string.
        if (isset($tag['handlers']['NO_ATTRIB'])) {            // If we have one, use this tags
            $handler =& $tag['handlers']['NO_ATTRIB'];        // no-attribute handler. Otherwise...
        } else { // Error #5: '[%1$s] is missing a required attribute.'.
            $handler =& $pd['bbcd']['_ROOT_']['handlers']['NO_ATTRIB'];
            $new_errors[] = sprintf($lang_common['BBerr missing attribute'], $tagname);
        }
    }
    // -------------------------------------------------------
    // Do some validation checks. Fix problems where possible:
    // -------------------------------------------------------
    // Handle tag nesting depth overflow.
    if ($tag['depth'] > $tag['depth_max']) { // Allowable tag nesting level exceeded?
        switch ($tag['nest_type']) {            // Overflow. Handle based upon tag's "nest_type"
        case 'clip':                        // Silently strip overly nested tags and content.
            $contents = '';
            break;
        case 'fix':                            // Silently strip overly-nested tags (keep contents).
            $fmt_open = $fmt_close = '';
            break;
        case 'err':    // Error #6: '[%1$s] tag nesting depth: %2$d exceeds allowable limit: %3$d.'.
            $new_errors[] = sprintf($lang_common['BBerr nesting overflow'],
                                $tagname, $tag['depth'], $tag['depth_max']);
            break;
        default:
        }
    }
    // Verify this tag is not in its parent's excluded tags list.
    if (isset($pd['bbcd'][$parent]['tags_excluded'][$tagname])) {
        // Are we illegitimate?
    // Yes. Pick between error #6 and #7.
        if ($parent === $tagname) { // Error #7: '[%s] was opened within itself, this is not allowed.'
            $new_errors[] = sprintf($lang_common['BBerr self-nesting'], $tagname);
        } else { // Error #8: '[%1$s] was opened within [%2$s], this is not allowed.'
            $new_errors[] = sprintf($lang_common['BBerr invalid nesting'], $tagname, $parent);
        }
    }
    // Verfify our parent tag is in our 'parents' allowable array if it exists.
    if (isset($tag['parents']) && !isset($tag['parents'][$parent])) { // Error #9: '[%1$s] cannot be within: [%2$s]. Allowable parent tags: %3$s.'.
        $new_errors[] = sprintf($lang_common['BBerr invalid parent'],
            $tagname, $parent, '('. implode('), (', array_keys($tag['parents'])) .')');
    }
    // -----------------------------------------
    // Perform content-type-specific processing:
    // -----------------------------------------
    switch ($handler['c_type']) {
    case 'width_height':
        if (preg_match('/\b(\d++)[Xx](\d++)\b/S', $contents, $m)) {
            $width = (int)$m[1];
            $height = (int)$m[2];
        }
        if (preg_match('/\bw(?:idth)?+\s*+=\s*+[\'"]?+(\d++)\b/Si',  $contents, $m)) {
            $width  = (int)$m[1];
        }
        if (preg_match('/\bh(?:eight)?+\s*+=\s*+[\'"]?+(\d++)\b/Si', $contents, $m)) {
            $height = (int)$m[1];
        }
        if (isset($height, $tag['x_padding'], $tag['y_padding'])) {
            $height -= $tag['y_padding'] - $tag['x_padding']; // Adjust for height of embedded controller.
        }
        break;

    case 'url':
        // Sanitize contents which is (hopefully) a url link. Trim spaces.
        $contents = preg_replace(array('/^\s+/', '/\s+$/S'), '', $contents);
        // Handle special case link to a
        if ($feather->user->g_post_links != '1') {
            $new_errors[] = $lang_common['BBerr cannot post URLs'];
        }
        else if (($m = url_valid($contents))) {
            $contents = $m['url']; // Fetch possibly more complete url address.
        } else { // Error #10a: 'Invalid URL name: %s'.
            $new_errors[] = sprintf($lang_common['BBerr Invalid URL name'], $contents);
        }
        break;

    case 'email':
        if (filter_var($contents, FILTER_VALIDATE_EMAIL)) { // Error #10c: 'Invalid email address: %s'.
            $new_errors[] = sprintf($lang_common['BBerr Invalid email address'], $contents);
        }
        break;

    default:
    } // End c_type switch().
    // -------------------------------------------
    // Perform attribute-type-specific processing:
    // -------------------------------------------
    switch ($handler['a_type']) {
    case 'width_height':
        if ($attribute) {
            if (preg_match('/\b(\d++)[Xx](\d++)\b/', $attribute, $m)) {    // Check for a "123x456" WxH spec?
                $width = (int)$m[1];                                    // Yes. Set both dimensions.
                $height = (int)$m[2];
            }
            if (preg_match('/\bw(?:idth)?+\s*+=\s*+[\'"]?+(\d++)\b/i', $attribute, $m)) {
                $width  = (int)$m[1];
            }
            if (preg_match('/\bh(?:eight)?+\s*+=\s*+[\'"]?+(\d++)\b/i', $attribute, $m)) {
                $height = (int)$m[1];
            }
            $attribute = preg_replace('/[;\s]?+\b(?:(?:w(?:idth)?+|h(?:eight)?+)\s*+=\s*+|\d++[Xx])\d++\b/Si',
                            '', $attribute);
        }
        break;

    case 'url':
        if (($m = url_valid($attribute))) {
            $attribute = $m['url']; // Fetch possibly more complete url address.
        } else { // Error #10b: 'Invalid URL name: %s'.
            $new_errors[] = sprintf($lang_common['BBerr Invalid URL name'], $attribute);
        }
        break;

    case 'color':
        if (!preg_match($pd['re_color'], $attribute)) { // Error #11: 'Invalid color attribute: %s'.
            $new_errors[] = sprintf($lang_common['BBerr Invalid color'], $attribute);
        }
        break;

    case 'email':
        // TODO: improve this quick-n-dirty email check.
        if (!preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i', $attribute)) { // Error #10c: 'Invalid email address: %s'.
            $new_errors[] = sprintf($lang_common['BBerr Invalid email address'], $attribute);
        }
        break;

    default:
    } // End a_type switch().

    // ----------------------------------------------------------
    // Perform tag-specific processing of attribute and contents:
    // ----------------------------------------------------------
    switch ($tagname) {
    case 'img': // Handle bad image url, file too big, then scale-to-fit within forum defaults if too large.
        if ($tag['depth'] === 1) { // Check if not overly nested?
            if (($pd['ipass'] === 2) && $pd['config']['valid_imgs'] && url_valid($contents)) { // Valid URI?
                // Yes. Fetch file headers containing file type and size ("Content-Type" and "Content-Length").
                if (($http = @get_headers($contents)) !== false && is_array($http)) {
                    if (preg_match('/\b200\s++OK\s*+$/i', $http[0])) { // Good response header?
                        for ($i = 1, $len = count($http); $i < $len; ++$i) { // Yes. Loop through HTTP response headers.
                            if (preg_match('/^\s*+Content-Length\s*+:\s*+(\d++)\s*+$/i', $http[$i], $m)) {
                                $size = (int)$m[1];
                            }                // File size found.
                            if (preg_match('/^\s*+Content-Type\s*+:\s*+image\/(.++)$/i', $http[$i], $m)) {
                                $type = $m[1];
                            }                    // Image file type found.
                        }
                        // Verify Content-Type is an image.
                        if (isset($type)) {
                            // Verify remote file size is not too big. (If too big, handle error.)
                            if (isset($size)) {
                                if ($size <= $pd['config']['max_size']) {
                                    // Filesize is ok. Do nothing.
                                    if (($info = @getimagesize($contents)) && is_array($info)) {
                                        // Fetch width & height.
                                        // Now we know the filesize, width and height of remote image.
                                        if (($iwidth = (int)$info[0]) && ($iheight = (int)$info[1])) {
                                            // To resize or not resize, that is the question.
                                            // If bigger than default, scale down. Otherwise dont touch.
                                            // Scale image to fit within forum default width/height box dimensions.
                                            $ar = ((float)$iwidth) / ((float)$iheight);
                                            // Otherwise, for images that naturally fit inside the box,
                                            // leave the attribute clean (or unset).
                                            if (!isset($width) && !isset($height) &&
                                                    ($iwidth > $pd['config']['def_width'] ||
                                                        $iheight > $pd['config']['def_height'])
                                            ) {    // Remote file dimensions are too big to fit within default box.
                                                // Explicitly scale a new width and height in IMG attribute.
                                                $width = $pd['config']['def_width'];
                                                $height = (int)((((float)$width) / $ar) + 0.5);
                                                if ($height > $pd['config']['def_height']) {
                                                    $height = $pd['config']['def_height'];
                                                    $width = (int)((((float)$height) * $ar) + 0.5);
                                                }
                                            } // Else remote image fits. Do nothing special with width and height.
                                        }
                                    } else { // Error #13: 'Unable to retrieve image data from remote url: %s'.
                                        $new_errors[] = sprintf($lang_common['BBerr bad meta data'], $contents);
                                    } // NOTE: cannot generate this error.
                                } else { // Filesize of remote image is too big. Silently convert to link if possible.
                                    if (isset($pd['bbcd']['url']) && $pd['bbcd']['url']['depth'] === 0) {
                                        $fmt_open  = '{[url='. $contents .']';
                                        $fmt_close = '[/url]}';
                                        $contents = $lang_common['BBmsg big image'];
                                    } else { // Image within a url cannot be linkified. Just display url name.
                                        $contents = '{'. $contents .'}';
                                        $fmt_open  = '';
                                        $fmt_close = '';
                                    }
                                }
                            } else {
                                // $size not set.
 // Error #14: 'Unable to determine remote file size.'.
                                $new_errors[] = $lang_common['BBerr no file size'];
                            } // NOTE: cannot generate this error.
                        } else { // Error #15: 'Remote url does not have Content-Type: "image".'
                            $new_errors[] = $lang_common['BBerr non image'];
                        }
                    } else { // Error #16: 'Bad HTTP response header: "%s"'.
                        $new_errors[] = sprintf($lang_common['BBerr bad http response'], $http[0]);
                    }
                } else { // Error #17: 'Unable to read remote image http headers.'.
                    $new_errors[] = $lang_common['BBerr bad headers'];
                }
            } // Image validation turned off. Do nothing.
        } else { // Non-Error: IMG tag self nesting. Handle by silently stripping tags with no error.
            $fmt_open = $fmt_close = '';
        }
        break;

    case 'list': // Fixup lists within lists. In lists, everything must be in a [*] tag.
        // Check if LIST contents well-formed.
        if ($pd['ipass'] === 2 && !preg_match('% # Rev:20110220_1200
			^\s*+  # This regex validates well-formed list content.
			(?:
			  \[\*\]
			  [^[]*+(?:(?!\[/?\*\])\[[^[]*+)*+
			  \[/\*\]\s*+
			)++
			$
			%x', $contents)) { // Not well formed. Do fixup to ensure list contents are only * tags.
            // First regex wraps invalid characters at start of LIST in a [*]...[/*] tag.
            $contents = preg_replace($pd['re_fixlist_1'], '[*]$1[/*]', $contents);
            // Second regex wraps invalid characters between [/*] and [*] (or [/list]).
            $contents = preg_replace($pd['re_fixlist_2'], '$1[/*]', $contents);
        } // Well-formed LIST contents!
        if ($parent === 'list') {
            $fmt_open = '[*]'. $fmt_open;
            $fmt_close .= '[/*]';
        }
        break;

    default:
        break;
    } // End switch statement.
    // -------------------------------------------
    // Process width and height values if present.
    // -------------------------------------------
    if (isset($width) || isset($height)) { // Check if dimension specified in attrib or contents?
        // Yes. Clip both $width and/or $height to their respective config maximums.
        if (isset($width)) {                            // Clip to max. Set to default if zero.
            if ($width > $pd['config']['max_width']) {
                $width = $pd['config']['max_width'];
            } elseif ($width === 0) {
                $width = $pd['config']['def_width'];
            }
        }
        if (isset($height)) {                            // Clip to max. Set to default if zero.
            if ($height > $pd['config']['max_height']) {
                $height = $pd['config']['max_height'];
            } elseif ($height === 0) {
                $height = $pd['config']['def_height'];
            }
        }
        if (isset($ar)) { // If the real image dimensions are known ($ar), then adjust to fit in box and maintain $ar.
            if (isset($width) && isset($height)) {        // Check if both dimensions set?
                if ($ar > (((float)$width) / ((float)$height))) {    // Yes. Check if $width more precise than $height?
                    $height = (int)((((float)$width) / $ar) + 0.5); // Yes. Compute height from width and AR.
                    if ($height > $pd['config']['max_height']) {
                        $height = $pd['config']['max_height'];
                        $width = (int)((((float)$height) * $ar) + 0.5);
                    }
                } else {
                    $width = (int)((((float)$height) * $ar) + 0.5); // Compute width from height and AR.
                    if ($width > $pd['config']['max_width']) {
                        $width = $pd['config']['max_width'];
                        $height = (int)((((float)$width) / $ar) + 0.5);
                    }
                }
            } elseif (isset($width)) {
                $height = (int)((((float)$width) / $ar) + 0.5);        // Compute height from width and AR.
                if ($height > $pd['config']['max_height']) {
                    $height = $pd['config']['max_height'];
                    $width = (int)((((float)$height) * $ar) + 0.5);
                }
            } else {
                $width = (int)((((float)$height) * $ar) + 0.5);    // Compute width from height and AR.
                if ($width > $pd['config']['max_width']) {
                    $width = $pd['config']['max_width'];
                    $height = (int)((((float)$width) / $ar) + 0.5);
                }
            }
        }
        // Unconditionally write width and/or height data back into attribute.
        if ($width === 0) {
            $width = 1;
        }
        if ($height === 0) {
            $height = 1;
        }
        if ($attribute) {
            $attribute .= ';';
        }                // Add delimiter for non-empty attrib.
        if (isset($width) && isset($height)) {
            $attribute .= $width .'x'. $height;
        } elseif (isset($width)) {
            $attribute .= 'w='. $width;
        } else {
            $attribute .= 'h='. $height;
        }
        $fmt_open = '['. $tagname .'=%a_str%]';    // Set open tag format to receive attribute.
    }
    // Validate and filter tag's contents if and according to optional contents regex.
    if (isset($handler['c_regex'])) { // Check if this tag has a contents regex? (youtube, vimeo, etc.)
        // Yes. Check if regex matches contents?
        if (preg_match($handler['c_regex'], $contents, $m)) {
            $contents = $m[1];
        } else { // Error #12: 'Invalid content, [%s] requires specific content.'
            $new_errors[] = sprintf($lang_common['BBerr invalid content'], $tagname);
        }
    }
    // Silently strip empty or all-white tags:
    if (preg_match('/^\s*+$/', $contents)) {
        $contents = '';
    }

    // Unconditionally hide all opening square brackets within hidden CODE contents.
    // This is necessary otherwise the LIST fixup code would process "[*]" within CODE tags.
    // These \3 byte markers are subsequently removed by preparse_bbcode().
    if ($tag['tag_type'] === 'hidden') {
        $contents = str_replace('[', "\3", $contents);
    }
    // On first pass, fix inline tags which span paragraphs by closting then re-opening.
    if ($pd['ipass'] === 1 && $tag['html_type'] === 'inline' &&
            $tag['tag_type'] !== 'hidden' && strpos($contents, "\n") !== false) {
        $contents = preg_replace('/\n\s*?\n\s*/',
            "\1\2". $fmt_close ."\1".'$0'."\1\2". str_replace('%a_str%', $attribute, $fmt_open) ."\1", $contents);
    }
    // ***********************************************************************************
    // Handle errors. Wrap this tags open and close BBCode tag each in a valid [err] tag.
    // ***********************************************************************************
    if (isset($new_errors) && $fmt_open) {
        // check if we detected any errors?
 // Yes, we have detected one or more new error conditions.
        foreach ($new_errors as $errmsg) { // Push all new errors on g errors array.
            $pd['new_errors'][] = htmlspecialchars($errmsg);
        }
        // Wrap offending BBCode open and close tags each in its own valid error tag (last err only).
        $fmt_open  = '[err='. $errmsg .']'. $fmt_open  . '[/err]';    // Wrap tags in the last error message.
        $fmt_close = '[err='. $errmsg .']'. $fmt_close . '[/err]';
    }

    // -----------------------------------------------------------------------------
    // All done processing. Substitute $attribute and $contents into format strings:
    // -----------------------------------------------------------------------------
    if ($contents) {
        if ($pd['ipass'] === 1) {                    // Add byte markers on first pass.
            if ($tag['tag_type'] === 'hidden' || $handler['c_type'] == 'url') {
                $text = "\1\2". $fmt_open .'%c_str%'. $fmt_close ."\1";
            } else {
                $text = "\1\2". $fmt_open ."\1%c_str%\1\2". $fmt_close ."\1";
            }
        } else {
            $text = $fmt_open .'%c_str%'. $fmt_close;
        } // Pass 2, dont bother with byte markers.
        $text = str_replace('%a_str%', $attribute, $text);
        $text = str_replace('%c_str%', $contents, $text);
    } else {
        $text = '';
    }
    array_pop($pd['tag_stack']);                            // Were done. Pop this tag off the stack.
    $tag['depth']--;                                        // Restore pre-call tag specific depth.
    return $text;
} // Exit _preparse_bbcode_callback

/**----------------------------------------------------------------
 * Pre-process text containing BBCodes. Check for integrity,
 * well-formedness, nesting, etc. Flag errors by wrapping offending
 * tags in a special [err] tag.
 *-----------------------------------------------------------------
 */
function preparse_bbcode($text, &$errors, $is_signature = false)
{
    global $lang_common, $feather_config, $pd;

    $pd['new_errors'] = array(); // Reset the parser error message stack.
    $pd['in_signature'] = ($is_signature) ? true : false;
    $pd['ipass'] = 1;
    $newtext = preg_replace_callback($pd['re_bbcode'], '_preparse_bbcode_callback', $text);
    if ($newtext === null) {
        // On error, preg_replace_callback returns NULL.
 // Error #1: '(%s) Message is too long or too complex. Please shorten.'
        $errors[] = sprintf($lang_common['BBerr pcre'], preg_error());
        return $text;
    }
    $newtext = str_replace("\3", '[', $newtext); // Fixup CODE sections.
    $parts = explode("\1", $newtext); // Hidden chunks pre-marked like so: "\1\2<code.../code>\1"
    for ($i = 0, $len = count($parts); $i < $len; ++$i) {    // Loop through hidden and non-hidden text chunks.
        $part =& $parts[$i];                                // Use shortcut alias
        if (empty($part)) {
            continue;
        }                            // Skip empty string chunks.
        if ($part[0] !== "\2") { // If not hidden, process this normal text content.
            // Mark erroneous orphan tags.
            $part = preg_replace_callback($pd['re_bbtag'], '_orphan_callback', $part);
            // Process do-clickeys if enabled.
            if ($feather_config['o_make_links']) {
                $part = linkify($part);
            }
            // Process textile syntax tag shortcuts.
            if ($pd['config']['textile']) {
                // Do phrase replacements.
                $part = preg_replace_callback($pd['re_textile'],
                            '_textile_phrase_callback', $part);
                // Do lists.
                $part = preg_replace_callback('/^([*#]) .*+(?:\n\1 .*+)++$/Sm',
                            '_textile_list_callback', $part);
            }
            $part = preg_replace('/^[ \t]++$/m', '', $part);    // Clear "white" lines of spaces and tabs.
        } else {
            $part = substr($part, 1);
        } // For hidden chunks, strip \2 marker byte.
    }
    $text = implode("", $parts); // Put hidden and non-hidden chunks back together.
    $pd['ipass'] = 2; // Run a second pass through parser to clean changed content.
    $text = preg_replace_callback($pd['re_bbcode'], '_preparse_bbcode_callback', $text);
    $text = str_replace("\3", '[', $text); // Fixup CODE sections.
    if (!empty($pd['new_errors'])) {
        foreach ($pd['new_errors'] as $errmsg) {
            $errors[] = $errmsg;
        } // Push all new errors on global array.
    }
    return $text;
}
//
// Helper preg_replace_callback function for orphan processing.
//
function _orphan_callback($matches)
{
    global $pd, $lang_common;
    if ($matches[0][1] === '/') { // Error #18: 'Orphan close tag: [/%s] is missing its open tag.'
        $errmsg = sprintf($lang_common['BBerr orphan close'], $matches[1]);
    } else { // Error #19: 'Orphan open tag: [%s] is missing its close tag.'
        $errmsg = sprintf($lang_common['BBerr orphan open'], $matches[1]);
    }
    $pd['new_errors'][] = $errmsg;    // Append to array of errors so far.
    return '[err='. $errmsg .']'. $matches[0] .'[/err]';
}
//
// Helper preg_replace_callback function for textile lists processing.
//
function _textile_list_callback($matches)
{
    global $pd;
    if (!isset($pd['bbcd']['list'])) {
        return $matches[0];
    }
    $parts = preg_split('/(?:^|\n)\\'. $matches[1] .' /S', $matches[0], -1, PREG_SPLIT_NO_EMPTY);
    switch ($matches[1]) {
    case '*': return "[list]\n[*]". implode("[/*]\n[*]", $parts) ."[/*]\n[/list]";
    case '#': return "[list=1]\n[*]". implode("[/*]\n[*]", $parts) ."[/*]\n[/list]";
    }
}
//
// Helper preg_replace_callback function for textile processing.
//
function _textile_phrase_callback($matches)
{
    global $pd;
    $matches[2] = preg_replace_callback($pd['re_textile'], '_textile_phrase_callback', $matches[2]);
    switch ($matches[1]) {
    case '_': return (isset($pd['bbcd']['i']))   ? '[i]'.   $matches[2] .'[/i]'   : $matches[0];
    case '*': return (isset($pd['bbcd']['b']))   ? '[b]'.   $matches[2] .'[/b]'   : $matches[0];
    case '@': return (isset($pd['bbcd']['tt']))  ? '[tt]'.  $matches[2] .'[/tt]'  : $matches[0];
    case '^': return (isset($pd['bbcd']['sup'])) ? '[sup]'. $matches[2] .'[/sup]' : $matches[0];
    case '~': return (isset($pd['bbcd']['sub'])) ? '[sub]'. $matches[2] .'[/sub]' : $matches[0];
    case '-': return (isset($pd['bbcd']['del'])) ? '[del]'. $matches[2] .'[/del]' : $matches[0];
    case '+': return (isset($pd['bbcd']['ins'])) ? '[ins]'. $matches[2] .'[/ins]' : $matches[0];
    }
}

/* --- function preg_error()
Check to see what the last PREG/PCRE error was. Returns a string describing the
last error, or an empty string if there is no error (or if the PHP version is
older than 5.2.0).
--- */
function preg_error()
{
    global $pd;
    $errmsg = '';                                        // assume no error has occured. return empty string
    if ($pd['newer_php_version']) {                        // this function requires PHP 5.2.0 or higher
        switch (preg_last_error()) {                    // this returns the last error condition as a number
            case PREG_NO_ERROR:
                break;                                    // no error? return empty string === FALSE
            case PREG_INTERNAL_ERROR:
                $errmsg = 'PREG_INTERNAL_ERROR';
                break;
            case PREG_BACKTRACK_LIMIT_ERROR:
                $errmsg = 'PREG_BACKTRACK_LIMIT_ERROR';
                break;
            case PREG_RECURSION_LIMIT_ERROR:
                $errmsg = 'PREG_RECURSION_LIMIT_ERROR';
                break;
            case PREG_BAD_UTF8_ERROR:
                $errmsg = 'PREG_BAD_UTF8_ERROR';
                break;
            case PREG_BAD_UTF8_OFFSET_ERROR:
                $errmsg = 'PREG_BAD_UTF8_OFFSET_ERROR';
                break;
            default:
                $errmsg = 'Unrecognized PREG error';
                break;
        }
    }
    return $errmsg;
}
/* ***********************************************************
callback function _parse_bbcode_callback($matches)

  This is the callback function for the main parser. This routine is called
when the preg_replace_callback function within the parse_bbcode() function
matches one BBCode open/close pair. The BBCode tag components are passed in
$matches. The BBCode is converted to HTML markup according to the format
string specified in the $bbcd array member for this tag. If an attribute is
specified, it is encoded along with the tag contents to generate a valid
HTML markup snippet. If this tag is not enabled (either the 'in_post' or
'in_sig' member in $bbcd are FALSE), then the output depends upon the tag
type; If 'zombie' or 'hidden', then both the tags and contents are displayed.
If 'normal', then the open and close tags are stripped and the contents are
displayed. If 'atomic', then both the tags and contents are stripped.

Parameters:
    $matches[0];					=  ([TAG=att]..[/TAG])	// The whole match
    $matches[1];	$tagname		=  (TAG)				// The BBCode tag name.
    $matches[2];					=  (=)					// Attribute equals sign delimiter.
    $matches[3];	$attribute		= '(attribute)'			// Attribute within single quotes.
    $matches[4];	$attribute		= "(attribute)"			// Attribute within double quotes.
    $matches[5];	$attribute		=  (attribute)			// Attribute within no-or-any quotes.
    $matches[6];	$contents		=  (ontents)			// Tag contents.
*********************************************************** */
function _parse_bbcode_callback($matches)
{
    global $pd, $lang_common;
    $tagname =& $matches[1];            // TAGNAME we are currently servicing.
    $contents =& $matches[6];            // Shortcut to contents.
    $tag =& $pd['bbcd'][$tagname];        // Shortcut to bbcd array entry.
    $tag['depth']++;                    // update tag-specific nesting level

    // Set local parse enable flag based upon message type and global flags.
    $enabled = (!$pd['in_signature'] && $tag['in_post'] ||    // If in a post and post-enabled, or
        $pd['in_signature'] && $tag['in_sig']) ? true : false;    // in a sig and sig-enabled, then enabled.

    // Recursively parse any nested BBCode tag markup (unless tag type is hidden).
    if ($tag['tag_type'] !== 'hidden' && strpos($contents, '[') !== false) {
        $contents = preg_replace_callback($pd['re_bbcode'], '_parse_bbcode_callback', $contents);
    }
    // ------------------------------------------------------------------------------
    // Determine $attribute and format conversion $handler to use based on attribute.
    // ------------------------------------------------------------------------------
    if (!$matches[2]) {    // No attribute specified? Use the NO_ATTRIB handler.
        $attribute = '';
        if (isset($tag['handlers']['NO_ATTRIB'])) {            // If we have one, use this tags
            $handler =& $tag['handlers']['NO_ATTRIB'];
        }        // no-attribute handler. Otherwise...
        else {
            $handler =& $pd['bbcd']['_ROOT_']['handlers']['NO_ATTRIB'];
        } // Missing attribute! Strip tag.
    } else { // Attribute specified. Assign it from one of the three possible delimitations.
        if ($matches[3]) {                                    // Non-empty single-quoted value.
            $attribute =& $matches[3]; // Strip out escape from escapes and single quotes.
            $attribute = str_replace(array('\\\\', '\\\''), array('\\', '\''), $attribute);
        } elseif ($matches[4]) {                                // Non-empty double-quoted value.
            $attribute =& $matches[4]; // Strip out escape from escapes and double quotes.
            $attribute = str_replace(array('\\\\', '\\"'), array('\\', '"'), $attribute);
        } elseif ($matches[5]) {
            $attribute =& $matches[5];
        }        // Non-empty un-or-any-quoted value.
        else {
            $attribute = '';
        }                // Otherwise must be empty.
        // Determine which type of attribute handler: fixed or variable.
        if (isset($tag['handlers'][$attribute])) {            // If attribute matches handler key
            $handler =& $tag['handlers'][$attribute];        // use the fixed-attribute handler.
        } elseif (isset($tag['handlers']['ATTRIB'])) {        // Else if we have one, use this tags
            $handler =& $tag['handlers']['ATTRIB'];            // variable attribute handler. Otherwise...
        } else {
            $handler =& $pd['bbcd']['_ROOT_']['handlers']['NO_ATTRIB'];
        } // Missing attribute! Strip tag.
        // htmlspecialchars() was already called (but not for quotes).
        // Hide any double quotes in attribute now.
        $attribute = str_replace('"', '&quot;', $attribute);
    }

    // Set default format handler
    $format = $handler['format'];

    // -------------------------------------------
    // Perform attribute-type-specific processing:
    // -------------------------------------------
    switch ($handler['a_type']) {
    case 'width_height': // This attribute type is used for video BBCodes (YouTube, Vimeo, ...).
        if ($attribute) {
            if (preg_match('/\b(\d++)[Xx](\d++)\b/S', $attribute, $m)) {
                // Check for a "123x456" WxH spec?
                $width = (int)$m[1];                                    // Yes. Set both dimensions.
                $height = (int)$m[2];
            }
            if (preg_match('%\bw(?:idth)?+\s*+=\s*+[\'"]?+(\d++)\b%Si', $attribute, $m)) {
                $width  = (int)$m[1];
            }
            if (preg_match('%\bh(?:eight)?+\s*+=\s*+[\'"]?+(\d++)\b%Si', $attribute, $m)) {
                $height = (int)$m[1];
            }
            if (isset($width) && isset($height)) {
                $ar = (float)$width / (float)$height;
            }
            // Clean the attribute of any and all width/height specs.
            $attribute = preg_replace('/[ ;#]?\b(?:(?:w(?:idth)?+|h(?:eight)?+)\s*+=\s*+([\'"])?+|\d++X)\d++\b(?(1)\1)?+/Si',
                            '', $attribute);
            if (isset($width)) { // If set, clip to max allowed. Convert zero to global default.
                if ($width > $pd['config']['max_width']) {
                    $width = $pd['config']['max_width'];
                } elseif ($width === 0) {
                    $width = $pd['config']['def_width'];
                } // If zero, set to default.
            }
            if (isset($height)) { // If set, clip to max allowed. Convert zero to global default.
                if ($height > $pd['config']['max_height']) {
                    $height = $pd['config']['max_height'];
                } elseif ($height === 0) {
                    $height = $pd['config']['def_height'];
                } // If zero, set to default.
            }
            if (!isset($width)) {
                $format = str_replace('width="%w_str%"',  '', $format);
            }
            if (!isset($height)) {
                $format = str_replace('height="%h_str%"', '', $format);
            }
        } else {
            $width = $pd['config']['def_width'];
            $height = $pd['config']['def_height'];
        }
        if (isset($tag['x_padding']) && $width) {
            $width    += $tag['x_padding'];
        }
        if (isset($tag['y_padding']) && $height) {
            $height    += $tag['y_padding'];
        }
        break;
    default:
    }
    // ----------------------------------------------------------
    // Perform tag-specific processing of attribute and contents:
    // ----------------------------------------------------------
    switch ($tagname) {

    case 'img': // Handle disabled image, image inside of QUOTE, and width/height dimensions in attribute.
        if (!$enabled || (!$pd['config']['quote_imgs'] && isset($pd['bbcd']['quote']) && $pd['bbcd']['quote']['depth'] > 0)) { // IMG not enabled in this context. Convert to a text URL link if possible and re-enable.
            if (isset($pd['bbcd']['url']) && $pd['bbcd']['url']['depth'] > 0) {
                $format = "{%c_str%}";
            } else {
                if ($attribute) {
                    $format = '{<a href="%c_str%" title="%a_str%">'. $lang_common['Image link'] .'</a>}';
                } else {
                    $format = '{<a href="%c_str%" title="'. $lang_common['BBmsg images disabled'] .'">'.
                    $lang_common['Image link'] .'</a>}';
                }
                $enabled = true; // Re-enable to override defauslt disabled handling (i.e. dont delete.)
            }
        } else { // IMG is enabled in this context. Wrap image inside a clickable link if global option is set.
            if ($pd['config']['click_imgs'] && isset($pd['bbcd']['url']) && $pd['bbcd']['url']['depth'] === 0) {
                $format = preg_replace('/^\x01\x02([^\x01]*+)\x01$/', '<a href="%c_str%">$1</a>', $format);
            }
        }
        $format = "\1\2". $format ."\1";
        // If user provided a verbose attribute (in addition to WxH), place this in the title attribute,
        // otherwise place the URL ($contents) in the ALT attribute and remove the title attribute from format.
        if (preg_match('/^\s*$/', $attribute)) {
            $attribute = $contents;
            $format = str_replace(' title="%a_str%"', '', $format);
        }
        break;

    case 'quote': // Quotes require language-specific "wrote:" following the posters name.
        if ($attribute) { // Quote attribute specified. Convert optional #post_id to a post link.
            if (preg_match('/#(\d++)$/', $attribute, $m)) {
                // Check for optional embedded post id.
 // Attribute has optional '#1234' quoted post ID number. Convert to link back to quoted post.
                $attribute = preg_replace('/\s*#\d++$/S', '', $attribute); // Strip post id from attribute.
                $attribute .= ' '. $lang_common['wrote'];  // Append language-specific "wrote:".
                if ($pd['config']['quote_links']) {
                    $attribute = ' <a href="'. get_link('post/'.$m[1].'/#p'.$m[1]) .'">'. $attribute .'</a>';
                }
            } else {
                $attribute .= ' '. $lang_common['wrote'];
            } // If no post id, just add "wrote:".
        }
        break;

// TODO: The following is somewhat of a hack. Need to implement generic handling of tags
// which can only contain specific tags and must not contain any text (including whitespace).
// For example: the UL and OL list tags may only contain LI tags. TABLE can only have THEAD,
//  TBODY, TR, etc, and TR can only contain TD, etc...
    case 'list': // Clean whitespace cruft bordering <li> tags.
    case 'table': // Clean whitespace cruft bordering <tr> tags.
    case 'tr': // Clean whitespace cruft bordering <td> tags.
//		$contents = preg_replace(array('/^\s++/', '/\s+$/S', '%\s+(?=\x01\x02<(?:li|ol|ul)>)%S', '%(?<=</(?:li|ol|ul)>\x01)\s++%S'), '', $contents);
        $contents = preg_replace(array('/^\s++/', '/\s+$/S', '%\s+(?=\x01\x02<(?:li|ol|ul|tr|td)>)%S', '%(?<=</(?:li|ol|ul|tr|td)>\x01)\s++%S'), '', $contents);
        break;

    case 'code':
        if ($attribute) {
            if (!isset($tag['handlers'][$attribute])) {
                // Check for no matching attribute handler.
 // Yes, the attribute may be more complex. Extract the extra verbage to add to code header.
                if (preg_match('/^([\w\-.:]++)\s*+(.*)$/', $attribute, $m)) {
                    $type = strtolower($m[1]);
                    if (isset($tag['handlers'][$type])) { // Check if we recognize this first word?
                        $handler =& $tag['handlers'][$type];    // Yes. Set new fixed-attribute handler.
                        if ($m[2]) { // Check if there are extra words.
                            // Yes. Use this extra part of the attribute to augment the code header.
                            $format = str_replace('</h4>', ' - "'. $m[2] .'"</h4>', $handler['format']);
                        }
                        $attribute = $type;
                    }
                }
            }
        }
        break;

    case 'color':
    case 'colour':
        if (preg_match($pd['re_color'], $attribute, $m)) {
            if (isset($m[2]) && $m[2]) {
                $attribute = $m[1] .'; background-color: '. $m[2];
            } else {
                $attribute = $m[1];
            }
        }
    default:
    }

    // The return value depends upon whether this tag is currently enabled.
    if ($enabled) { // Tag is enabled. Add byte markers to hidden chunks to allow easy explode() later.
        if (isset($width)) {
            $format = str_replace('%w_str%', $width, $format);
        }
        if (isset($height)) {
            $format = str_replace('%h_str%', $height, $format);
        }
        // Subtitute attribute into format string.
        $format = str_replace('%a_str%', $attribute, $format);    // Encode attribute value.
        // Finally, subtitute the content into the message.
        $text = str_replace('%c_str%', $contents, $format);        // Encode contents.
    } else { // Tag is not enabled. Strip HTML tags and contents according to tag_type.
         switch ($tag['tag_type']) {
         case 'atomic':                    // Strip everything.
            $text = '';
            break;
         case 'normal':                    // Strip HTML open and close tags only.
            $text =& $contents;
            break;
         case 'hidden':                    // Hidden and 'zombie' tags are displayed without.
         case 'zombie':                    // stripping anything. All [BBCODES] are displayed.
         default:                        // Default === hidden === zombie === Strip nothing.
            $text =& $matches[0];        // $format = '['. $tagname .']%c_str%[/'. $tagname .']';
         }
    }
    if ($tag['depth'] > $tag['depth_max']) {
        $text = '';
    } // Silently clip overly-nested tags.
    $tag['depth']--;                    // restore pre-call tag specific depth
    return $text;
} // exit _parse_bbcode_callback
//
// Parse post or signature message text.
//
function parse_bbcode(&$text, $hide_smilies = 0)
{
    global $feather_config, $pd;
    
    // Get Slim current session
    $feather = \Slim\Slim::getInstance();

    if ($feather_config['o_censoring'] === '1') {
        $text = censor_words($text);
    }
    // Convert [&<>] characters to HTML entities (but preserve [""''] quotes).
    $text = htmlspecialchars($text, ENT_NOQUOTES);

    // Parse BBCode if globally enabled.
    if ($feather_config['p_message_bbcode']) {
        $text = preg_replace_callback($pd['re_bbcode'], '_parse_bbcode_callback', $text);
    }
    // Set $smile_on flag depending on global flags and whether or not this is a signature.
    if ($pd['in_signature']) {
        $smile_on = ($feather_config['o_smilies_sig'] && $feather->user->show_smilies && !$hide_smilies) ? 1 : 0;
    } else {
        $smile_on = ($feather_config['o_smilies'] && $feather->user->show_smilies && !$hide_smilies) ? 1 : 0;
    }
    // Split text into hidden and non-hidden chunks. Process the non-hidden content chunks.
    $parts = explode("\1", $text); // Hidden chunks pre-marked like so: "\1\2<code.../code>\1"
    for ($i = 0, $len = count($parts); $i < $len; ++$i) { // Loop through hidden and non-hidden text chunks.
        $part =& $parts[$i];                                    // Use shortcut alias
        if (empty($part)) {
            continue;
        }                                // Skip empty string chunks.
        if ($part[0] !== "\2") { // If not hidden, process this normal text content.
            if ($smile_on) { // If smileys enebled, do them all in one whack.
                $part = preg_replace_callback($pd['re_smilies'], '_do_smilies_callback', $part);
            }
            // Deal with newlines, tabs and multiple spaces
            $part = str_replace(
                array("\n",        "\t",                '  ',        '  '),
                array('<br />', '&#160; &#160; ',    '&#160; ',    ' &#160;'), $part);
        } else {
            $part = substr($part, 1);
        } // For hidden chunks, strip \2 marker byte.
    }
    $text = implode("", $parts); // Put hidden and non-hidden chunks back together.

    // Add paragraph tag around post, but make sure there are no empty paragraphs
    $text = str_replace('<p><br />', '<p>', $text);
    $text = str_replace('<p></p>', '', '<p>'. $text .'</p>');
    return $text;
}
//
// Helper preg_replace_callback function for smilies processing.
//
function _do_smilies_callback($matches)
{
    global $pd;
    return $pd['smilies'][$matches[0]]['html'];
}
//
// Parse message text
//
function parse_message($text, $hide_smilies)
{
    global $pd, $feather_config;
    
    // Get Slim current session
    $feather = \Slim\Slim::getInstance();
    
    $pd['in_signature'] = false;
    // Disable images via the $bbcd['in_post'] flag if globally disabled.
    if ($feather_config['p_message_img_tag'] !== '1' || $feather->user->show_img !== '1') {
        if (isset($pd['bbcd']['img'])) {
            $pd['bbcd']['img']['in_post'] = false;
        }
    }
    return parse_bbcode($text, $hide_smilies);
}
//
// Parse signature text
//
function parse_signature($text)
{
    global $pd, $feather_config;
    
    // Get Slim current session
    $feather = \Slim\Slim::getInstance();
    
    $pd['in_signature'] = true;
    // Disable images via the $bbcd['in_sig'] flag if globally disabled.
    if ($feather_config['p_sig_img_tag'] !== '1' || $feather->user->show_img_sig !== '1') {
        if (isset($pd['bbcd']['img'])) {
            $pd['bbcd']['img']['in_sig'] = false;
        }
    }
    return parse_bbcode($text);
}
