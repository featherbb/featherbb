<?php // File: cache_parser_data.php. Automatically generated: 2015-08-23 06:16:14. DO NOT EDIT!!!
$pd = array (
  'newer_php_version' => true,
  'in_signature' => false,
  'ipass' => 0,
  'tag_stack' => 
  array (
    0 => '_ROOT_',
  ),
  'config' => 
  array (
    'textile' => true,
    'quote_links' => true,
    'quote_imgs' => false,
    'valid_imgs' => true,
    'click_imgs' => true,
    'max_size' => 100000,
    'max_width' => 800,
    'max_height' => 600,
    'def_width' => 240,
    'def_height' => 180,
    'smiley_size' => 100,
  ),
  're_smilies' => '/ # re_smilies Rev:20110220_1200
# Match special smiley character sequences within BBCode content.
(?<=^|[>\\s])                    # Only if preceeded by ">" or whitespace.
(?:\\:\\)|\\=\\)|\\:\\||\\=\\||\\:\\(|\\=\\(|\\:D|\\=D|\\:o|\\:O|;\\)|\\:\\/|\\:P|\\:p|\\:lol\\:|\\:mad\\:|\\:rolleyes\\:|\\:cool\\:)
(?=$|[\\[<\\s])                   # Only if followed by "<", "[" or whitespace.
                                /Sx',
  're_color' => '% # re_color Rev:20110220_1200
# Match a valid CSS color value. #123, #123456, or "red", "blue", etc.
^                               # Anchor to start of string.
(                               # $1: Foreground color (required).
  \\#(?:[0-9A-Fa-f]{3}){1,2}     # Either a "#" and a 3 or 6 digit hex number,
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
  're_textile' => '/ # re_textile Rev:20110220_1200
# Match textile inline phrase: _em_ *strong* @tt@ ^super^ ~sub~ -del- +ins+
([+\\-@*_\\^~])                   # $1: literal exposed start of phrase char, but
(?<=                            # only if preceded by...
  ^          [+\\-@*_]           # start of string (for _em_ *strong* -del- +ins+ @code)
| \\s         [+\\-@*_]           # or whitespace   (for _em_ *strong* -del- +ins+ @code)
| [A-Za-z0-9)}\\]>][\\^~]         # or alphanum or bracket (for ^superscript^ ~subscript~).
)                               # only if preceded by whitespace or start of string.
(                               # $2: Textile phrase contents.
  [A-Za-z0-9({\\[<]              # First char following delim must be alphanum or bracket.
  [^+\\-@*_\\^~\\n]*+              # "normal*" == Zero or more non-delim, non-newline.
  (?>                           # Begin unrolling-the-loop. "(special normal*)*"
    (?!                         # One of two conditions must be true for inside delim:
      (?:(?<=[A-Za-z0-9)}\\]>][.,;:!?])(?=\\1(?:\\s|$)))
    | (?:(?<=[A-Za-z0-9)}\\]>])(?=\\1(?:[\\s.,;:!?]|$)))
    )[+\\-@*_\\^~]                # If so then not yet at phrase end. Match delim and
    [^+\\-@*_\\^~\\n]*+            # more "normal*" non-delim, non-linefeeds.
  )*+                           # Continue unrolling. "(special normal*)*"
)                               # End $2: Textile phrase contents.
(?>
  (?:(?<=[A-Za-z0-9)}\\]>][.,;:!?])(?=\\1(?:\\s|$)))
| (?:(?<=[A-Za-z0-9)}\\]>])(?=\\1(?:[\\s.,;:!?]|$)))
)
\\1                              # Match delim end of phrase, but only if
                                /Smx',
  're_bbcode' => '% # re_bbcode Rev:20110220_1200
# First, match opening tag of syntax: "[TAGNAME (= ("\')ATTRIBUTE("\') )]";
\\[                              # Match opening bracket of outermost opening TAGNAME tag.
(?>(b|code|color|colour|del|email|em|h|img|ins|i|table|tr|th|td|list|\\*|quote|sub|sup|s|tt|url|u|center|right|left|justify|youtube|large|small|err|dbug)\\s*+) # $1:
(?>                             # Atomically group remainder of opening tag.
  (?:                           # Optional attribute.
    (=)\\s*+                     # $2: = Optional attribute\'s equals sign delimiter, ws.
    (?:                         # Group for 1-line attribute value alternatives.
      \'([^\'\\r\\n\\\\]*+(?:\\\\.[^\'\\r\\n\\\\]*+)*+)\'  # Either $3: == single quoted,
    | "([^"\\r\\n\\\\]*+(?:\\\\.[^"\\r\\n\\\\]*+)*+)"      # or     $4: == double quoted,
    | ( [^[\\]\\r\\n]*+            # or $5: == un-or-any-quoted. "normal*" == non-"[]"
        (?:                     # Begin "(special normal*)*" "Unrolling-the-loop" construct.
          \\[[^[\\]\\r\\n]*+\\]      # Allow matching [square brackets] 1 level deep. "special".
            [^[\\]\\r\\n]*+        # More "normal*" any non-"[]", non-newline characters.
        )*+                     # End "(special normal*)*" "Unrolling-the-loop" construct.
      )                         # End $5: Un-or-any-quoted attribute value.
    )                           # End group of attribute values alternatives.
    \\s*+                        # Optional whitespace following quoted values.
  )?                            # End optional attribute group.
  \\]                            # Match closing bracket of outermost opening TAGNAME tag.
)                               # End atomic group with opening tag remainder.
# Second, match the contents of the tag.
(                               # $6: Non-trimmed contents of TAGNAME tag.
  (?>                           # Atomic group for contents alternatives.
    [^\\[]++                     # Option 1: Match non-tag chars (starting with non-"[").
    (?:                         # Begin "(special normal*)*" "Unrolling-the-loop" construct.
      (?!\\[/?+\\1[\\]=\\s])\\[      # "special" = "[" if not start of [TAGNAME*] or [/TAGNAME].
      [^\\[]*+                   # More "normal*".
    )*+                         # Zero or more "special normal*"s allowed for option 1.
  | (?:                         # or Option 2: Match non-tag chars (starting with "[").
      (?!\\[/?+\\1[\\]=\\s])\\[      # "special" = "[" if not start of [TAGNAME*] or [/TAGNAME].
      [^\\[]*+                   # More "normal*".
    )++                         # One or more "special normal*"s required for option 2.
  | (?R)                        # Or option 3: recursively match nested [TAGNAME]..[/TAGNAME].
  )*+                           # One of these three options as many times as necessary.
)                               # End $6: Non-trimmed contents of TAGNAME tag.
# Finally, match the closing tag.
\\[/\\1\\s*+\\]                     # Match outermost closing [/  TAGNAME  ]
                                %ix',
  're_bbtag' => '%# re_bbtag Rev:20110220_1200
# Match open or close BBtag.
\\[/?+                           # Match opening bracket of outermost opening TAGNAME tag.
(?>(b|code|color|colour|del|email|em|h|img|ins|i|table|tr|th|td|list|\\*|quote|sub|sup|s|tt|url|u|center|right|left|justify|youtube|large|small|err|dbug)\\s*+) #$1:
(?:                             # Optional attribute.
  (=)\\s*+                       # $2: = Optional attribute\'s equals sign delimiter, ws.
  (?:                           # Group for 1-line attribute value alternatives.
    \'([^\'\\r\\n\\\\]*+(?:\\\\.[^\'\\r\\n\\\\]*+)*+)\'  # Either $3: == single quoted,
  | "([^"\\r\\n\\\\]*+(?:\\\\.[^"\\r\\n\\\\]*+)*+)"      # or     $4: == double quoted,
  | ( [^[\\]\\r\\n]*+              # or $5: == un-or-any-quoted. "normal*" == non-"[]"
      (?:                       # Begin "(special normal*)*" "Unrolling-the-loop" construct.
        \\[[^[\\]\\r\\n]*+\\]        # Allow matching [square brackets] 1 level deep. "special".
          [^[\\]\\r\\n]*+          # More "normal*" any non-"[]", non-newline characters.
      )*+                       # End loop construct. See: "Mastering Regular Expressions".
    )                           # End $5: Un-or-any-quoted attribute value.
  )                             # End group of attribute values alternatives.
  \\s*+                          # Optional whitespace following quoted values.
)?                              # End optional attribute.
\\]                              # Match closing bracket of outermost opening TAGNAME tag.
                                %ix',
  're_fixlist_1' => '%# re_fixlist_1 Rev:20110220_1200
# Match and repair invalid characters at start of LIST tag (before first [*]).
^                               # Anchor to start of subject text.
(                               # $1: Substring with invalid chars to be enclosed.
  \\s*+                          # Optional whitespace before first invalid char.
  (?!\\[(?:\\*|/list)\\])          # Assert invalid char(s). (i.e. valid if [*] or [/list]).
  [^[]*                         # (Normal*) Zero or more non-[.
  (?:                           # Begin (special normal*)* "Unroll-the-loop- construct.
    (?!\\[(?:\\*|/list)\\])        # If this [ is not the start of [*] or [/list], then
    \\[                          # go ahead and match non-[*], non-[/list] left bracket.
    [^[]*                       # More (normal*).
  )*                            # End (special normal*)* "unroll-the-loop- construct.
)                               # End $1: non-whitespace before first [*] (or [/list]).
(?<!\\s)                         # Backtrack to exclude any trailing whitespace.
(?=\\s*\\[(?:\\*|/list)\\])         # Done once we reach a [*] or [/list].
								%ix',
  're_fixlist_2' => '%# re_fixlist_2 Rev:20110220_1200
# Match and repair invalid characters between [/*] and next [*] (or [/list]].
\\[/\\*\\]                         # Match [/*] close tag.
(                               # $1: Substring with invalid chars to be enclosed.
  \\s*+                          # Optional whitespace before first invalid char.
  (?!\\[(?:\\*|/list)\\])          # Assert invalid char(s). (i.e. valid if [*] or [/list]).
  [^[]*                         # (Normal*) Zero or more non-[.
  (?:                           # Begin (special normal*)* "Unroll-the-loop- construct.
    (?!\\[(?:\\*|/list)\\])        # If this [ is not the start of [*] or [/list], then
    \\[                          # go ahead and match non-[*], non-[/list] left bracket.
    [^[]*                       # More (normal*).
  )*                            # End (special normal*)* "unroll-the-loop- construct.
)                               # End $1: non-whitespace before first [*] (or [/list]).
(?<!\\s)                         # Backtrack to exclude any trailing whitespace.
(?=\\s*\\[(?:\\*|/list)\\])         # Done once we reach a [*] or [/list].
								%ix',
  'smilies' => 
  array (
    ':)' => 
    array (
      'file' => 'smile.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/smile.png" alt="Smile" title="Smile" />',
    ),
    '=)' => 
    array (
      'file' => 'smile.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/smile.png" alt="Smile" title="Smile" />',
    ),
    ':|' => 
    array (
      'file' => 'neutral.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/neutral.png" alt="Neutral" title="Neutral" />',
    ),
    '=|' => 
    array (
      'file' => 'neutral.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/neutral.png" alt="Neutral" title="Neutral" />',
    ),
    ':(' => 
    array (
      'file' => 'sad.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/sad.png" alt="Sad" title="Sad" />',
    ),
    '=(' => 
    array (
      'file' => 'sad.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/sad.png" alt="Sad" title="Sad" />',
    ),
    ':D' => 
    array (
      'file' => 'big_smile.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/big_smile.png" alt="Big Smile" title="Big Smile" />',
    ),
    '=D' => 
    array (
      'file' => 'big_smile.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/big_smile.png" alt="Big Smile" title="Big Smile" />',
    ),
    ':o' => 
    array (
      'file' => 'yikes.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/yikes.png" alt="Yikes" title="Yikes" />',
    ),
    ':O' => 
    array (
      'file' => 'yikes.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/yikes.png" alt="Yikes" title="Yikes" />',
    ),
    ';)' => 
    array (
      'file' => 'wink.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/wink.png" alt="Wink" title="Wink" />',
    ),
    ':/' => 
    array (
      'file' => 'hmm.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/hmm.png" alt="Hmm" title="Hmm" />',
    ),
    ':P' => 
    array (
      'file' => 'tongue.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/tongue.png" alt="Tongue" title="Tongue" />',
    ),
    ':p' => 
    array (
      'file' => 'tongue.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/tongue.png" alt="Tongue" title="Tongue" />',
    ),
    ':lol:' => 
    array (
      'file' => 'lol.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/lol.png" alt="Lol" title="Lol" />',
    ),
    ':mad:' => 
    array (
      'file' => 'mad.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/mad.png" alt="Mad" title="Mad" />',
    ),
    ':rolleyes:' => 
    array (
      'file' => 'roll.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/roll.png" alt="Roll" title="Roll" />',
    ),
    ':cool:' => 
    array (
      'file' => 'cool.png',
      'html' => '<img width="15" height="15" src="/featherbb/img/smilies/cool.png" alt="Cool" title="Cool" />',
    ),
  ),
  'bbcd' => 
  array (
    'b' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<strong>%c_str%</strong>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'code' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'ATTRIB' => 
        array (
          'a_type' => 'text',
          'c_type' => 'text',
          'format' => '</p><div class="codebox"><h4>Code: "%a_str%"</h4><pre>%c_str%</pre></div><p>',
        ),
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '</p><div class="codebox"><pre>%c_str%</pre></div><p>',
        ),
      ),
      'html_type' => 'block',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'tag_type' => 'hidden',
      'tags_excluded' => 
      array (
      ),
    ),
    'color' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'ATTRIB' => 
        array (
          'a_type' => 'color',
          'c_type' => 'text',
          'format' => '<span style="color: %a_str%;">%c_str%</span>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'colour' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'ATTRIB' => 
        array (
          'a_type' => 'color',
          'c_type' => 'text',
          'format' => '<span style="color: %a_str%;">%c_str%</span>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'del' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<del>%c_str%</del>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'email' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'ATTRIB' => 
        array (
          'a_type' => 'email',
          'c_type' => 'text',
          'format' => '<a href="mailto:%a_str%" rel="nofollow">%c_str%</a>',
        ),
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'email',
          'format' => '<a href="mailto:%c_str%" rel="nofollow">%c_str%</a>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'email' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'url' => true,
        'dbug' => true,
      ),
    ),
    'em' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<em>%c_str%</em>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'h' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '</p><h5>%c_str%</h5><p>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'img' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'ATTRIB' => 
        array (
          'a_type' => 'width_height',
          'c_type' => 'url',
          'format' => '<img src="%c_str%" alt="%a_str%" title="%a_str%" width="%w_str%" height="%h_str%" />',
        ),
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'url',
          'format' => '<img src="%c_str%" alt="%c_str%" />',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'atomic',
      'tags_excluded' => 
      array (
        'b' => true,
        'code' => true,
        'color' => true,
        'colour' => true,
        'del' => true,
        'email' => true,
        'em' => true,
        'h' => true,
        'ins' => true,
        'i' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'sub' => true,
        'sup' => true,
        's' => true,
        'tt' => true,
        'url' => true,
        'u' => true,
        'center' => true,
        'right' => true,
        'left' => true,
        'justify' => true,
        'youtube' => true,
        'large' => true,
        'small' => true,
        'err' => true,
        'dbug' => true,
      ),
    ),
    'ins' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<ins>%c_str%</ins>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'i' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<em>%c_str%</em>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'table' => 
    array (
      'depth' => 0,
      'depth_max' => 5,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '</p><table>%c_str%</table><p>',
        ),
      ),
      'html_type' => 'block',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'b' => true,
        'code' => true,
        'color' => true,
        'colour' => true,
        'del' => true,
        'email' => true,
        'em' => true,
        'h' => true,
        'img' => true,
        'ins' => true,
        'i' => true,
        'table' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'sub' => true,
        'sup' => true,
        's' => true,
        'tt' => true,
        'url' => true,
        'u' => true,
        'center' => true,
        'right' => true,
        'left' => true,
        'justify' => true,
        'youtube' => true,
        'large' => true,
        'small' => true,
        'dbug' => true,
      ),
      'tags_only' => true,
    ),
    'tr' => 
    array (
      'depth' => 0,
      'depth_max' => 5,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<tr>%c_str%</tr>',
        ),
      ),
      'html_type' => 'block',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'parents' => 
      array (
        'table' => true,
      ),
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'b' => true,
        'code' => true,
        'color' => true,
        'colour' => true,
        'del' => true,
        'email' => true,
        'em' => true,
        'h' => true,
        'img' => true,
        'ins' => true,
        'i' => true,
        'table' => true,
        'tr' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'sub' => true,
        'sup' => true,
        's' => true,
        'tt' => true,
        'url' => true,
        'u' => true,
        'center' => true,
        'right' => true,
        'left' => true,
        'justify' => true,
        'youtube' => true,
        'large' => true,
        'small' => true,
        'dbug' => true,
      ),
      'tags_only' => true,
    ),
    'th' => 
    array (
      'depth' => 0,
      'depth_max' => 5,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<th><p>%c_str%</p></th>',
        ),
      ),
      'html_type' => 'block',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'parents' => 
      array (
        'tr' => true,
      ),
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
      ),
    ),
    'td' => 
    array (
      'depth' => 0,
      'depth_max' => 5,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<td><p>%c_str%</p></td>',
        ),
      ),
      'html_type' => 'block',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'parents' => 
      array (
        'tr' => true,
      ),
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
      ),
    ),
    'list' => 
    array (
      'depth' => 0,
      'depth_max' => 5,
      'handlers' => 
      array (
        1 => 
        array (
          'a_type' => 'text',
          'c_type' => 'text',
          'format' => '</p><ol class="decimal">%c_str%</ol><p>',
        ),
        'a' => 
        array (
          'a_type' => 'text',
          'c_type' => 'text',
          'format' => '</p><ol class="alpha">%c_str%</ol><p>',
        ),
        '*' => 
        array (
          'a_type' => 'text',
          'c_type' => 'text',
          'format' => '</p><ul>%c_str%</ul><p>',
        ),
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '</p><ul>%c_str%</ul><p>',
        ),
      ),
      'html_type' => 'block',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'b' => true,
        'code' => true,
        'color' => true,
        'colour' => true,
        'del' => true,
        'email' => true,
        'em' => true,
        'h' => true,
        'img' => true,
        'ins' => true,
        'i' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'quote' => true,
        'sub' => true,
        'sup' => true,
        's' => true,
        'tt' => true,
        'url' => true,
        'u' => true,
        'center' => true,
        'right' => true,
        'left' => true,
        'justify' => true,
        'youtube' => true,
        'large' => true,
        'small' => true,
        'err' => true,
        'dbug' => true,
      ),
      'tags_only' => true,
    ),
    '*' => 
    array (
      'depth' => 0,
      'depth_max' => 5,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<li><p>%c_str%</p></li>',
        ),
      ),
      'html_type' => 'block',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'parents' => 
      array (
        'list' => true,
      ),
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
      ),
    ),
    'quote' => 
    array (
      'depth' => 0,
      'depth_max' => 5,
      'handlers' => 
      array (
        'ATTRIB' => 
        array (
          'a_type' => 'text',
          'c_type' => 'text',
          'format' => '</p><div class="quotebox"><cite>%a_str%</cite><blockquote><div><p>%c_str%</p></div></blockquote></div><p>',
        ),
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '</p><div class="quotebox"><blockquote><div><p>%c_str%</p></div></blockquote></div><p>',
        ),
      ),
      'html_type' => 'block',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'clip',
      'tag_type' => 'zombie',
      'tags_excluded' => 
      array (
      ),
    ),
    'sub' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<sub>%c_str%</sub>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'sup' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<sup>%c_str%</sup>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    's' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<span class="bbs">%c_str%</span>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'tt' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<tt>%c_str%</tt>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'hidden',
      'tags_excluded' => 
      array (
      ),
    ),
    'url' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'ATTRIB' => 
        array (
          'a_type' => 'url',
          'c_type' => 'text',
          'format' => '<a href="%a_str%" rel="nofollow">%c_str%</a>',
        ),
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'url',
          'format' => '<a href="%c_str%" rel="nofollow">%c_str%</a>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'email' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'url' => true,
        'dbug' => true,
      ),
    ),
    'u' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<span class="bbu">%c_str%</span>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'center' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '</p><div align="center"><p>%c_str%</p></div><p>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'right' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '</p><div align="right"><p>%c_str%</p></div><p>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'left' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '</p><div align="left"><p>%c_str%</p></div><p>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'justify' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '</p><div align="justify"><p>%c_str%</p></div><p>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'youtube' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'ATTRIB' => 
        array (
          'a_type' => 'width_height',
          'c_regex' => '%(?:^|\\bv[=/])(\\w{10,12})\\b%S',
          'c_type' => 'text',
          'format' => '<object type="application/x-shockwave-flash" width="%w_str%" height="%h_str%" data="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1&amp;rel=0"><param name="movie" value="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1" /><param name="allowFullScreen" value="true" /></object>',
        ),
        'NO_ATTRIB' => 
        array (
          'a_type' => 'width_height',
          'c_regex' => '%(?:^|\\bv[=/])(\\w{10,12})\\b%S',
          'c_type' => 'width_height',
          'format' => '<object type="application/x-shockwave-flash" width="%w_str%" height="%h_str%" data="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1&amp;rel=0"><param name="movie" value="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1" /><param name="allowFullScreen" value="true" /></object>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => false,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'b' => true,
        'code' => true,
        'color' => true,
        'colour' => true,
        'del' => true,
        'email' => true,
        'em' => true,
        'h' => true,
        'img' => true,
        'ins' => true,
        'i' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'sub' => true,
        'sup' => true,
        's' => true,
        'tt' => true,
        'url' => true,
        'u' => true,
        'center' => true,
        'right' => true,
        'left' => true,
        'justify' => true,
        'youtube' => true,
        'large' => true,
        'small' => true,
        'err' => true,
        'dbug' => true,
      ),
      'x_padding' => 20,
      'y_padding' => 45,
    ),
    'large' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<span style="font-size: larger;">%c_str%</span>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'small' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<span style="font-size: smaller;">%c_str%</span>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
        'code' => true,
        'table' => true,
        'tr' => true,
        'th' => true,
        'td' => true,
        'list' => true,
        '*' => true,
        'quote' => true,
        'dbug' => true,
      ),
    ),
    'err' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'ATTRIB' => 
        array (
          'a_type' => 'text',
          'c_type' => 'text',
          'format' => '<span class="err" title="%a_str%">%c_str%</span>',
        ),
        'NO_ATTRIB' => 
        array (
          'a_type' => 'none',
          'c_type' => 'text',
          'format' => '<span class="err">%c_str%</span>',
        ),
      ),
      'html_type' => 'inline',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'fix',
      'tag_type' => 'hidden',
      'tags_excluded' => 
      array (
      ),
    ),
    'dbug' => 
    array (
      'depth' => 0,
      'depth_max' => 5,
      'handlers' => 
      array (
        'ATTRIB' => 
        array (
          'a_type' => 'text',
          'c_type' => 'text',
          'format' => '</p><p class="debug" title="%a_str%">%c_str%</p><p>',
        ),
      ),
      'html_type' => 'block',
      'in_post' => true,
      'in_sig' => true,
      'nest_type' => 'err',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
      ),
    ),
    '_ROOT_' => 
    array (
      'depth' => 0,
      'depth_max' => 1,
      'handlers' => 
      array (
        'NO_ATTRIB' => 
        array (
          'a_type' => 'text',
          'c_type' => 'text',
          'format' => '<span class="err" title="_ROOT_">%c_str%</span>',
        ),
      ),
      'html_type' => 'block',
      'in_post' => false,
      'in_sig' => false,
      'nest_type' => 'err',
      'tag_type' => 'normal',
      'tags_excluded' => 
      array (
      ),
    ),
  ),
);
?>