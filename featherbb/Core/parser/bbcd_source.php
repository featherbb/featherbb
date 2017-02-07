<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * Parser (C) 2011 Jeff Roberson (jmrware.com)
 * based on code by (C) 2008-2015 FluxBB
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
$config = [
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
]; // End $config array.

// Array of smileys. These files are located in the style/img/smilies folder).
$smilies = [
    ':)'            => ['file'    => 'smile.png'],
    '=)'            => ['file'    => 'smile.png'],
    ':|'            => ['file'    => 'neutral.png'],
    '=|'            => ['file'    => 'neutral.png'],
    ':('            => ['file'    => 'sad.png'],
    '=('            => ['file'    => 'sad.png'],
    ':D'            => ['file'    => 'big_smile.png'],
    '=D'            => ['file'    => 'big_smile.png'],
    ':o'            => ['file'    => 'yikes.png'],
    ':O'            => ['file'    => 'yikes.png'],
    ';)'            => ['file'    => 'wink.png'],
    ':/'            => ['file'    => 'hmm.png'],
    ':P'            => ['file'    => 'tongue.png'],
    ':p'            => ['file'    => 'tongue.png'],
    ':lol:'        => ['file'    => 'lol.png'],
    ':mad:'        => ['file'    => 'mad.png'],
    ':rolleyes:'    => ['file'    => 'roll.png'],
    ':cool:'        => ['file'    => 'cool.png']
]; // End $smilies array.

/*
FluxBB 1.4.3 Old parser tags:
array('quote', 'code', 'b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'url', 'email', 'img', 'list', '*', 'h')
array('quote', 'code', 'b', 'i', 'u',
*/
$bbcd = [ // Array of recognised BBCode tag structures (arrays).
    'b' => [
        'html_name'                => 'strong'
    ],
    'code' => [
        'html_name'                => 'pre',
        'tag_type'                => 'hidden',
        'html_type'                => 'block',
        'handlers'                => [
            'ATTRIB'            => [
                'format'        => '
                    </p>
                    <div class="codebox">
                        <h4>Code: "%a_str%"</h4>
                        <pre>%c_str%</pre>
                    </div>
                    <p>'
            ],
            'NO_ATTRIB'            => [
                'format'        => '
                    </p>
                    <div class="codebox">
                        <pre>%c_str%</pre>
                    </div>
                    <p>'
            ]
        ]
    ],
    'color' => [
        'html_name'                => 'span',
        'nest_type'                => 'err',
        'handlers'                => [
            'ATTRIB'            => [
                'a_type'        => 'color',
                'format'        => '<span style="color: %a_str%;">%c_str%</span>'
            ]
        ]
    ],
    'colour' => [
        'html_name'                => 'span',
        'nest_type'                => 'err',
        'handlers'                => [
            'ATTRIB'            => [
                'a_type'        => 'color',
                'format'        => '<span style="color: %a_str%;">%c_str%</span>'
            ]
        ]
    ],
    'del' => [
        'html_name'                => 'del'
    ],
    'email' => [
        'html_name'                => 'a',
        'nest_type'                => 'err',
        'tags_excluded'            => ['email' => true, 'url' => true],
        'handlers'                => [
            'ATTRIB'            => [
                'a_type'        => 'email',
                'c_type'        => 'text',
                'format'        => '<a href="mailto:%a_str%" rel="nofollow">%c_str%</a>'
            ],
            'NO_ATTRIB'            => [
                'a_type'        => 'none',
                'c_type'        => 'email',
                'format'        => '<a href="mailto:%c_str%" rel="nofollow">%c_str%</a>'
            ]
        ]
    ],
    'em' => [
        'html_name'                => 'em'
    ],
    'h' => [
        'html_name'                => 'h5',
        'handlers'                => [
            'NO_ATTRIB'            => [
                'format'        => '</p><h5>%c_str%</h5><p>'
            ]
        ]
    ],
    'img' => [
        'html_name'                => 'img',
        'tag_type'                => 'atomic',
        'tags_allowed'            => ['img' => true],
        'handlers'                => [
            'ATTRIB'            => [
                'a_type'        => 'width_height',
                'c_type'        => 'url',
                'format'        => '<img src="%c_str%" alt="%a_str%" title="%a_str%" width="%w_str%" height="%h_str%" />'
            ],
            'NO_ATTRIB'            => [
                'a_type'        => 'none',
                'c_type'        => 'url',
                'format'        => '<img src="%c_str%" alt="%c_str%" />'
            ]
        ]
    ],
    'ins' => [
        'html_name'                => 'ins'
    ],
    'i' => [
        'html_name'                => 'em'
    ],


    'table' => [
        'html_name'                => 'table',
        'html_type'                => 'block',
        'handlers'        => [
            'NO_ATTRIB'            => ['format' => '</p><table>%c_str%</table><p>']
        ],
        'tags_only'                => true,
        'tags_allowed'            => [
            'tr'                =>    true,
            'err'                =>    true,
        ]
    ],
    'tr' => [
        'html_name'                => 'tr',
        'html_type'                => 'block',
        'parents'                => ['table' => true],
        'handlers'        => [
            'NO_ATTRIB'            => ['format' => '<tr>%c_str%</tr>']
        ],
        'tags_only'                => true,
        'tags_allowed'            => [
            'th'                =>    true,
            'td'                =>    true,
            'err'                =>    true,
        ]
    ],
    'th' => [
        'html_name'                => 'th',
        'html_type'                => 'block',
        'parents'                => ['tr' => true],
        'handlers'        => [
            'NO_ATTRIB'            => ['format' => '<th><p>%c_str%</p></th>']
        ],
    ],
    'td' => [
        'html_name'                => 'td',
        'html_type'                => 'block',
        'parents'                => ['tr' => true],
        'handlers'        => [
            'NO_ATTRIB'            => ['format' => '<td><p>%c_str%</p></td>']
        ],
    ],


    'list' => [
        'html_name'                => 'ul',
        'html_type'                => 'block',
        'handlers'        => [
            '1'                    => ['format' => '</p><ol class="decimal">%c_str%</ol><p>'],
            'a'                    => ['format' => '</p><ol class="alpha">%c_str%</ol><p>'],
            '*'                    => ['format' => '</p><ul>%c_str%</ul><p>'],
            'NO_ATTRIB'            => ['format' => '</p><ul>%c_str%</ul><p>']
        ],
        'tags_only'                => true,
        'tags_allowed'            => [
            'list'                =>    true,
            '*'                    =>    true]
    ],
    '*' => [
        'html_name'                => 'li',
        'html_type'                => 'block',
        'parents'                => ['list' => true],
        'handlers'        => [
            'NO_ATTRIB'            => ['format' => '<li><p>%c_str%</p></li>']
        ]
    ],
    'quote' => [
        'html_name'                => 'blockquote',
        'html_type'                => 'block',
        'tag_type'                => 'zombie',
        'nest_type'                => 'clip',
//        'depth_max'                => 3,
        'handlers'                => [
            'ATTRIB'            => [
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
            ],
            'NO_ATTRIB'            => [
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
            ],
        ],
    ],
    'sub' => [
        'html_name'                => 'sub'
    ],
    'sup' => [
        'html_name'                => 'sup'
    ],
    's' => [
        'html_name'                => 'span',
        'handlers'                => [
            'NO_ATTRIB'            => [
                'format'        => '<span class="bbs">%c_str%</span>'
            ]
        ]
    ],
    'tt' => [
        'html_name'                => 'tt',
        'tag_type'                => 'hidden',
        'handlers'    => [ // count == 1
            'NO_ATTRIB'    => [ // count == 3
                'a_type'    => 'none',
                'c_type'    => 'text',
                'format'    => '<tt>%c_str%</tt>'
            ]
        ],


    ],
    'url' => [
        'html_name'                => 'a',
//        'nest_type'                => 'err',
        'tags_excluded'            => ['email' => true, 'url' => true],
        'handlers'                => [
            'ATTRIB'            => [
                'a_type'        => 'url',
                'c_type'        => 'text',
                'format'        => '<a href="%a_str%" rel="nofollow">%c_str%</a>'
            ],
            'NO_ATTRIB'            => [
                'a_type'        => 'none',
                'c_type'        => 'url',
                'format'        => '<a href="%c_str%" rel="nofollow">%c_str%</a>'
            ]
        ]
    ],
    'u' => [
        'html_name'                => 'span',
        'handlers'                => [
            'NO_ATTRIB'            => [
                'format'        => '<span class="bbu">%c_str%</span>'
            ]
        ]
    ],

    'center' => [
        'html_name'                => 'div',
        'handlers'                => [
            'NO_ATTRIB'            => [
                'format'        => '</p><div align="center"><p>%c_str%</p></div><p>'
            ]
        ]
    ],
    'right' => [
        'html_name'                => 'div',
        'handlers'                => [
            'NO_ATTRIB'            => [
                'format'        => '</p><div align="right"><p>%c_str%</p></div><p>'
            ]
        ]
    ],
    'left' => [
        'html_name'                => 'div',
        'handlers'                => [
            'NO_ATTRIB'            => [
                'format'        => '</p><div align="left"><p>%c_str%</p></div><p>'
            ]
        ]
    ],
    'justify' => [
        'html_name'                => 'div',
        'handlers'                => [
            'NO_ATTRIB'            => [
                'format'        => '</p><div align="justify"><p>%c_str%</p></div><p>'
            ]
        ]
    ],


    'youtube' => [
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
        'tags_allowed'            => [],
        'x_padding'                => 20,
        'y_padding'                => 45,
        'handlers'                => [
            'ATTRIB'            => [
                'a_type'        => 'width_height',
                'c_type'        => 'text',
                'c_regex'        => '%(?:^|\bv[=/])(\w{10,12})\b%S',
                'format'        => '
                    <object type="application/x-shockwave-flash" width="%w_str%" height="%h_str%"
                        data="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1&amp;rel=0">
                        <param name="movie" value="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1" />
                        <param name="allowFullScreen" value="true" />
                    </object>'
            ],
            'NO_ATTRIB'            => [
                'a_type'        => 'width_height',
                'c_type'        => 'width_height',
                'c_regex'        => '%(?:^|\bv[=/])(\w{10,12})\b%S',
                'format'        => '
                    <object type="application/x-shockwave-flash" width="%w_str%" height="%h_str%"
                        data="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1&amp;rel=0">
                        <param name="movie" value="http://www.youtube.com/v/%c_str%&amp;hl=en_US&amp;fs=1&amp;border=1" />
                        <param name="allowFullScreen" value="true" />
                    </object>'
            ]
        ]
    ],

    'large' => [
        'html_name'                => 'span',
        'handlers'                => [
            'NO_ATTRIB'            => [
                'format'        => '<span style="font-size: larger;">%c_str%</span>'
            ]
        ]
    ],
    'small' => [
        'html_name'                => 'span',
        'handlers'                => [
            'NO_ATTRIB'            => [
                'format'        => '<span style="font-size: smaller;">%c_str%</span>'
            ]
        ]
    ],







    // System Tags. DO NOT DISABLE
    'err' => [
        'html_name'                => 'span',
        'tag_type'                => 'hidden',
        'html_type'                => 'inline',
        'handlers'                => [
            'ATTRIB'            => [
                'format'        =>
                    '<span class="err" title="%a_str%">%c_str%</span>'
            ],
            'NO_ATTRIB'            => [
                'format'        =>
                    '<span class="err">%c_str%</span>'
            ]
        ]
    ],
    '_ROOT_' => [
        'in_post'                => false,
        'in_sig'                => false,
        'html_name'                => 'div',
        'tag_type'                => 'normal',
        'html_type'                => 'block',
        'depth_max'                => 1,
        'handlers'                => [ // Default handler for erroneously defined tag.
            'NO_ATTRIB'            => [
                'a_type'        => 'text',
                'c_type'        => 'text',
                'format'        => "\1\2<span class=\"err\" title=\"_ROOT_\">%c_str%</span>\1",
            ]
        ]
    ]
] // End $bbcd array.
;
