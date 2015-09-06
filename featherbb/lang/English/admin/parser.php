<?php
// Language definitions used by parser admin panel
$lang_admin_parser = array(
    'Parser head'            =>    'Parser Options, BBCodes and Smilies',
    'reset_success'            =>    'Parser Options, BBCodes and Smilies successfully reset to FluxBB default settings.',
    'save_success'            =>    'Parser changes successfully saved.',
    'upload success'        =>    'Smiley file upload successful.',
    'reset defaults'            =>    'Restore default settings',

/* *********************************************************************** */
    'Remotes subhead'        =>    'Remote image validation',
    'unavailable'            =>    'This function is unavailable.',

/* *********************************************************************** */
    'Config subhead'        =>    'Parser options',

    'syntax style'            =>    'CODE syntax style',
    'syntax style help'        =>    'Select a style sheet file from this list of currently installed SyntaxHighlighter files.',

    'textile'                =>    'Enable Textile shortcuts',
    'textile help'            =>    'Enable some textile tag keystroke shortcuts: _emphasized_, *strong*, @inline code@, super^script^, sub~script~, -deleted- and +inserted+ text. Textile shortcuts for bulleted (*) and numbered (#) lists are also supported. When posting a new message, (or editing an existing one), during the pre-parsing phase, the textile shortcut delimiters are converted to their equivalent BBCode tags before being stored in the database.',

    'quote_imgs'            =>    'Display IMG in QUOTE ',
    'quote_imgs help'        =>    'Display images contained within QUOTE tags. If this option is set to "No" (the default setting), then images within a quote are not displayed graphically but are instead displayed as a simple text hyperlink to the image (with the link text set to: "{IMG}"). If this option is set to "Yes", then images within quotes are displayed normally (as a graphic object).',

    'quote_links'            =>    'Linkify QUOTE citation',
    'quote_links help'        =>    'Make the "Poster wrote:" citation text a link back to the original post for quotes having original post number metadata included in the tag attribute (the post number, preceded by a: \'#\', is specified at the end of the attribute like so: "[quote=Admin #101]...[/quote]").',

    'click_imgs'            =>    'Make IMG clickable',
    'click_imgs help'        =>    'Display images and also make each image a clickable link to the full sized original.',

    'max_xy'                =>    'Maximum media XY',
    'max_xy help'            =>    'Maximum pixel width and height allowable for new visual media objects (images, videos etc).',

    'smiley_size'            =>    'Smiley display size',
    'smiley_size help'        =>    'Percent size adjustment for smiley box dimensions (default 160% == 24x24 pixels).',

/* *********************************************************************** */
    'valid_imgs'            =>    'Validate remote IMG',
    'valid_imgs help'        =>    'When posting a message having IMG tags, validate the existence of the remote image files by requesting their file header information. Check each remote image filesize and do NOT display if too big (to save the forum viewer\'s bandwidth). If any image has a dimension greater than "Max width" or "Max height", then limit the displayed image to fit. (Note that this option is disabled if the PHP variable "allow_url_fopen" is FALSE.)',

    'max_size'                =>    'Maximum IMG filesize',
    'max_size help'            =>    'Maximum remote image filesize to be allowed on forum pages. When creating a new post, the remote image file size is checked against this value and reported as an error if it is too big and image validation is on.',

    'def_xy'                =>    'Default media XY',
    'def_xy help'            =>    'Default pixel width and height for new visual media objects (images, videos etc). When a post has an IMG tag and the "Validate remote IMG" option is turned on, then the remote file information is scaled to fit within these dimensions and retain the original aspect ratio.',

/* *********************************************************************** */
    'Smilies subhead'        =>    'Smilies',
    'smiley_text_label'        =>    'Smiley text',
    'smiley_file_label'        =>    'Smiley image file',
    'smiley_upload'            =>    'Upload new smiley image',
    'New smiley image'        =>    'New smiley image',
    'upload_err_1'            =>    'Smiley upload failed. Unable to move to smiley folder.',
    'upload_err_2'            =>    'Smiley upload failed. File is too big.',
    'upload_err_3'            =>    'Smiley upload failed. File type is not an image.',
    'upload_err_4'            =>    'Smiley upload failed. Bad filename.',
    'upload_err_5'            =>    'Smiley upload failed. File only partially uploaded.',
    'upload_err_6'            =>    'Smiley upload failed. No filename.',
    'upload_err_7'            =>    'Smiley upload failed. No temporary folder.',
    'upload_err_8'            =>    'Smiley upload failed. Cannot write file to disk.',
    'upload_err_9'            =>    'Smiley upload failed. Unknown error!',
    'upload_off'            =>    'Uploading files is currently disabled.',
    'upload_button'            =>    'Upload File',

/* *********************************************************************** */
    'BBCodes subhead'        =>    'BBCodes',
    'tagname_label'            =>    'BBCode Tag Name',
    'tagtype_label'            =>    'Tag type',
    'in_post_label'            =>    'Allow in posts?',
    'in_sig_label'            =>    'Allow in signatures?',
    'depth_max'                =>    'Max tag nesting depth.',
    'tag_summary'        =>    array(
        'unknown'        =>    'Unrecognized tag - (need to update language file).',
        'code'            =>    'Computer Code. [attrib=language].',
        'quote'            =>    'Block Quotation. [attrib == citation].',
        'list'            =>    'Ordered or Unordered list. (*=bulleted | a=alpha | 1=numeric).',
        '*'                =>    'List Item.',
        'h'                =>    'Header 5. [attrib=TITLE].',
        'img'            =>    'Inline Image. [attrib=ALT=TITLE].',
        'url'            =>    'Hypertext Link. [attrib=URL].',
        'b'                =>    'Strong Emphasis. (Bold).',
        'i'                =>    'Emphasis. (Italic).',
        's'                =>    'Strike-through Text.',
        'u'                =>    'Underlined Text.',
        'color'            =>    'Color. attrib=[#FFF | #FFFFFF | red].',
        'tt'            =>    'Teletype Text.',
        'center'        =>    'Centered Block.',
        'err'            =>    'Error codes generated by parser for invalid BBCode. [attrib=TITLE].',
    ),
);
