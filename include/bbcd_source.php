<?php

/**
 * Copyright (C) 2015 FeatherBB
 * Parser (C) 2011 Jeff Roberson (jmrware.com)
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */


// File: $bbcd_source.php Rev:20110403_2100
// Contains master default: $config, $syntaxes, $smilies and $bbcd arrays.
// This file is not used during runtime. It is used to compile the actual runtime
// cache file: cache_parser_data.php whenever the parser options need to be reset
// or initialized. These are the "factory default" settings. This file is designed
// to be hand edited.

// Global parser options (These should eventually be migrated to the config db table?)
$config = array(
    'textile'        => true,        // Allow simple textile phrase extensions.
    'quote_links'    => true,        // Make quote citation a link back to source post.
    'quote_imgs'    => false,        // Allow IMG tags withing QUOTEs flag.
    'valid_imgs'    => true,        // Validate images and clip size during pre-parsing.
    'click_imgs'    => true,        // Wrap IMG tags in a url link to the original image.
    'max_size'        => 100000,        // Maximum remote filesize for posting IMG links.
    'max_width'        => 800,            // Max width of visual media objects in pixels.
    'max_height'    => 600,            // Max height of visual media objects in pixels.
    'def_width'        => 240,            // Default width of visual media objects in pixels.
    'def_height'    => 180,            // Default height of visual media objects in pixels.
    'smiley_size'   => 100,            // Percent size adjust for display of smilies.
); // End $config array.

// Array of smileys. These files are located in the img/smilies folder).
$smilies = array(
    ':)'            => array('file'    => 'smile.png'),
    '=)'            => array('file'    => 'smile.png'),
    ':|'            => array('file'    => 'neutral.png'),
    '=|'            => array('file'    => 'neutral.png'),
    ':('            => array('file'    => 'sad.png'),
    '=('            => array('file'    => 'sad.png'),
    ':D'            => array('file'    => 'big_smile.png'),
    '=D'            => array('file'    => 'big_smile.png'),
    ':o'            => array('file'    => 'yikes.png'),
    ':O'            => array('file'    => 'yikes.png'),
    ';)'            => array('file'    => 'wink.png'),
    ':/'            => array('file'    => 'hmm.png'),
    ':P'            => array('file'    => 'tongue.png'),
    ':p'            => array('file'    => 'tongue.png'),
    ':lol:'        => array('file'    => 'lol.png'),
    ':mad:'        => array('file'    => 'mad.png'),
    ':rolleyes:'    => array('file'    => 'roll.png'),
    ':cool:'        => array('file'    => 'cool.png')
); // End $smilies array.

/*
FluxBB 1.4.3 Old parser tags:
array('quote', 'code', 'b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'url', 'email', 'img', 'list', '*', 'h')
array('quote', 'code', 'b', 'i', 'u',
*/
$bbcd = array( // Array of recognised BBCode tag structures (arrays).
    'b' => array(
        'html_name'                => 'strong'
    ),
    'code' => array(
        'html_name'                => 'pre',
        'tag_type'                => 'hidden',
        'html_type'                => 'block',
        'handlers'                => array(
            'ATTRIB'            => array(
                'format'        => '
					</p>
					<div class="codebox">
						<h4>Code: "%a_str%"</h4>
						<pre>%c_str%</pre>
					</div>
					<p>'
            ),
            'NO_ATTRIB'            => array(
                'format'        => '
					</p>
					<div class="codebox">
						<pre>%c_str%</pre>
					</div>
					<p>'
            )
        )
    ),
    'color' => array(
        'html_name'                => 'span',
        'nest_type'                => 'err',
        'handlers'                => array(
            'ATTRIB'            => array(
                'a_type'        => 'color',
                'format'        => '<span style="color: %a_str%;">%c_str%</span>'
            )
        )
    ),
    'colour' => array(
        'html_name'                => 'span',
        'nest_type'                => 'err',
        'handlers'                => array(
            'ATTRIB'            => array(
                'a_type'        => 'color',
                'format'        => '<span style="color: %a_str%;">%c_str%</span>'
            )
        )
    ),
    'del' => array(
        'html_name'                => 'del'
    ),
    'email' => array(
        'html_name'                => 'a',
        'nest_type'                => 'err',
        'tags_excluded'            => array('email' => true, 'url' => true),
        'handlers'                => array(
            'ATTRIB'            => array(
                'a_type'        => 'email',
                'c_type'        => 'text',
                'format'        => '<a href="mailto:%a_str%" rel="nofollow">%c_str%</a>'
            ),
            'NO_ATTRIB'            => array(
                'a_type'        => 'none',
                'c_type'        => 'email',
                'format'        => '<a href="mailto:%c_str%" rel="nofollow">%c_str%</a>'
            )
        )
    ),
    'em' => array(
        'html_name'                => 'em'
    ),
    'h' => array(
        'html_name'                => 'h5',
        'handlers'                => array(
            'NO_ATTRIB'            => array(
                'format'        => '</p><h5>%c_str%</h5><p>'
            )
        )
    ),
    'img' => array(
        'html_name'                => 'img',
        'tag_type'                => 'atomic',
        'tags_allowed'            => array('img' => true),
        'handlers'                => array(
            'ATTRIB'            => array(
                'a_type'        => 'width_height',
                'c_type'        => 'url',
                'format'        => '<img src="%c_str%" alt="%a_str%" title="%a_str%" width="%w_str%" height="%h_str%" />'
            ),
            'NO_ATTRIB'            => array(
                'a_type'        => 'none',
                'c_type'        => 'url',
                'format'        => '<img src="%c_str%" alt="%c_str%" />'
            )
        )
    ),
    'ins' => array(
        'html_name'                => 'ins'
    ),
    'i' => array(
        'html_name'                => 'em'
    ),


    'table' => array(
        'html_name'                => 'table',
        'html_type'                => 'block',
        'handlers'        => array(
            'NO_ATTRIB'            => array('format' => '</p><table>%c_str%</table><p>' )
        ),
        'tags_only'                => true,
        'tags_allowed'            => array(
            'tr'                =>    true,
            'err'                =>    true,
        )
    ),
    'tr' => array(
        'html_name'                => 'tr',
        'html_type'                => 'block',
        'parents'                => array('table' => true),
        'handlers'        => array(
            'NO_ATTRIB'            => array('format' => '<tr>%c_str%</tr>' )
        ),
        'tags_only'                => true,
        'tags_allowed'            => array(
            'th'                =>    true,
            'td'                =>    true,
            'err'                =>    true,
        )
    ),
    'th' => array(
        'html_name'                => 'th',
        'html_type'                => 'block',
        'parents'                => array('tr' => true),
        'handlers'        => array(
            'NO_ATTRIB'            => array('format' => '<th><p>%c_str%</p></th>' )
        ),
    ),
    'td' => array(
        'html_name'                => 'td',
        'html_type'                => 'block',
        'parents'                => array('tr' => true),
        'handlers'        => array(
            'NO_ATTRIB'            => array('format' => '<td><p>%c_str%</p></td>' )
        ),
    ),


    'list' => array(
        'html_name'                => 'ul',
        'html_type'                => 'block',
        'handlers'        => array(
            '1'                    => array('format' => '</p><ol class="decimal">%c_str%</ol><p>'),
            'a'                    => array('format' => '</p><ol class="alpha">%c_str%</ol><p>'),
            '*'                    => array('format' => '</p><ul>%c_str%</ul><p>'),
            'NO_ATTRIB'            => array('format' => '</p><ul>%c_str%</ul><p>' )
        ),
        'tags_only'                => true,
        'tags_allowed'            => array(
            'list'                =>    true,
            '*'                    =>    true)
    ),
    '*' => array(
        'html_name'                => 'li',
        'html_type'                => 'block',
        'parents'                => array('list' => true),
        'handlers'        => array(
            'NO_ATTRIB'            => array('format' => '<li><p>%c_str%</p></li>' )
        )
    ),
    'quote' => array(
        'html_name'                => 'blockquote',
        'html_type'                => 'block',
        'tag_type'                => 'zombie',
        'nest_type'                => 'clip',
//		'depth_max'				=> 3,
        'handlers'                => array(
            'ATTRIB'            => array(
                'format'        => '
				</p>
				<div class="quotebox">
					<cite>%a_str%</cite>
					<blockquote>
						<div>
							<p>%c_str%</p>
						</div>
					</blockquote>
				</div>
				<p>'
            ),
            'NO_ATTRIB'            => array(
                'format'        => '
				</p>
				<div class="quotebox">
					<blockquote>
						<div>
							<p>%c_str%</p>
						</div>
					</blockquote>
				</div>
				<p>'
            ),
        ),
    ),
    'sub' => array(
        'html_name'                => 'sub'
    ),
    'sup' => array(
        'html_name'                => 'sup'
    ),
    's' => array(
        'html_name'                => 'span',
        'handlers'                => array(
            'NO_ATTRIB'            => array(
                'format'        => '<span class="bbs">%c_str%</span>'
            )
        )
    ),
    'tt' => array(
        'html_name'                => 'tt',
        'tag_type'                => 'hidden',
        'handlers'    => array( // count == 1
            'NO_ATTRIB'    => array( // count == 3
                'a_type'    => 'none',
                'c_type'    => 'text',
                'format'    => '<tt>%c_str%</tt>'
            )
        ),


    ),
    'url' => array(
        'html_name'                => 'a',
//		'nest_type'				=> 'err',
        'tags_excluded'            => array('email' => true, 'url' => true),
        'handlers'                => array(
            'ATTRIB'            => array(
                'a_type'        => 'url',
                'c_type'        => 'text',
                'format'        => '<a href="%a_str%" rel="nofollow">%c_str%</a>'
            ),
            'NO_ATTRIB'            => array(
                'a_type'        => 'none',
                'c_type'        => 'url',
                'format'        => '<a href="%c_str%" rel="nofollow">%c_str%</a>'
            )
        )
    ),
    'u' => array(
        'html_name'                => 'span',
        'handlers'                => array(
            'NO_ATTRIB'            => array(
                'format'        => '<span class="bbu">%c_str%</span>'
            )
        )
    ),

    'center' => array(
        'html_name'                => 'div',
        'handlers'                => array(
            'NO_ATTRIB'            => array(
                'format'        => '</p><div align="center"><p>%c_str%</p></div><p>'
            )
        )
    ),
    'right' => array(
        'html_name'                => 'div',
        'handlers'                => array(
            'NO_ATTRIB'            => array(
                'format'        => '</p><div align="right"><p>%c_str%</p></div><p>'
            )
        )
    ),
    'left' => array(
        'html_name'                => 'div',
        'handlers'                => array(
            'NO_ATTRIB'            => array(
                'format'        => '</p><div align="left"><p>%c_str%</p></div><p>'
            )
        )
    ),
    'justify' => array(
        'html_name'                => 'div',
        'handlers'                => array(
            'NO_ATTRIB'            => array(
                'format'        => '</p><div align="justify"><p>%c_str%</p></div><p>'
            )
        )
    ),


    'youtube' => array(
    /* Supplied in one of three acceptable formats:  (Note: smallest good youtube dimensions: 260x225)
        1. XWlhKllqnAk
        2. http://www.youtube.com/watch?v=XWlhKllqnAk
        3. <object width="480" height="385"><param name="movie" value="http://www.youtube.com/v/XWlhKllqnAk?fs=1&amp;hl=en_US&amp;rel=0"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/XWlhKllqnAk?fs=1&amp;hl=en_US&amp;rel=0" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="480" height="385"></embed></object>
        SIZING:
        With wxh set to 480x385 and no borders, the object uses 480x385 and the image takes 480x360.
        With wxh set to 480x385 and borders, the object uses 480x385 and the image takes 460x340.
          Border width = 10px. Controller height = 25.
    */
        'in_post'                => true,
        'in_sig'                => false,
        'html_name'                => 'object',
        'tags_allowed'            => array(),
        'x_padding'                => 20,
        'y_padding'                => 45,
        'handlers'                => array(
            'ATTRIB'            => array(
                'a_type'        => 'width_height',
                'c_type'        => 'text',
                'c_regex'        => '%(?:^|\bv[=/])(\w{10,12})\b%S',
                'format'        => '
					<object type="application/x-shockwave-flash" width="%w_str%" height="%h_str%"
						data="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1&amp;rel=0">
						<param name="movie" value="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1" />
						<param name="allowFullScreen" value="true" />
					</object>'
            ),
            'NO_ATTRIB'            => array(
                'a_type'        => 'width_height',
                'c_type'        => 'width_height',
                'c_regex'        => '%(?:^|\bv[=/])(\w{10,12})\b%S',
                'format'        => '
					<object type="application/x-shockwave-flash" width="%w_str%" height="%h_str%"
						data="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1&amp;rel=0">
						<param name="movie" value="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1" />
						<param name="allowFullScreen" value="true" />
					</object>'
            )
        )
    ),

    'large' => array(
        'html_name'                => 'span',
        'handlers'                => array(
            'NO_ATTRIB'            => array(
                'format'        => '<span style="font-size: larger;">%c_str%</span>'
            )
        )
    ),
    'small' => array(
        'html_name'                => 'span',
        'handlers'                => array(
            'NO_ATTRIB'            => array(
                'format'        => '<span style="font-size: smaller;">%c_str%</span>'
            )
        )
    ),







    // System Tags. DO NOT DISABLE
    'err' => array(
        'html_name'                => 'span',
        'tag_type'                => 'hidden',
        'html_type'                => 'inline',
        'handlers'                => array(
            'ATTRIB'            => array(
                'format'        =>
                    '<span class="err" title="%a_str%">%c_str%</span>'
            ),
            'NO_ATTRIB'            => array(
                'format'        =>
                    '<span class="err">%c_str%</span>'
            )
        )
    ),
    'dbug' => array(
        'html_name'                => 'div',
        'html_type'                => 'block',
        'handlers'                => array(
            'ATTRIB'            => array(
                'format'        =>
                    '</p><p class="debug" title="%a_str%">%c_str%</p><p>'
            )
        )
    ),
    '_ROOT_' => array(
        'in_post'                => false,
        'in_sig'                => false,
        'html_name'                => 'div',
        'tag_type'                => 'normal',
        'html_type'                => 'block',
        'depth_max'                => 1,
        'handlers'                => array( // Default handler for erroneously defined tag.
            'NO_ATTRIB'            => array(
                'a_type'        => 'text',
                'c_type'        => 'text',
                'format'        => "\1\2<span class=\"err\" title=\"_ROOT_\">%c_str%</span>\1",
            )
        )
    )
) // End $bbcd array.
;
