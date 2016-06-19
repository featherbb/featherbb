<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * Parser (C) 2011 Jeff Roberson (jmrware.com)
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

class Parser
{
    // Helper public function returns array of smiley image files
    //   stored in the style/img/smilies directory.
    public function get_smiley_files()
    {
        $imgfiles = array();
        $filelist = scandir(ForumEnv::get('FEATHER_ROOT').'style/img/smilies');
        $filelist = Container::get('hooks')->fire('model.admin.parser.get_smiley_files.filelist', $filelist);
        foreach ($filelist as $file) {
            if (preg_match('/\.(?:png|gif|jpe?g)$/', $file)) {
                $imgfiles[] = $file;
            }
        }
        $imgfiles = Container::get('hooks')->fire('model.admin.parser.get_smiley_files.imgfiles', $imgfiles);
        return $imgfiles;
    }
    
    // Array of BBCode text (title) elements
    public function tagSummary() 
    {
        $tagSummary = array(
            'unknown' => 'Unrecognized Tag',
            'code'    => 'Computer Code',
            'quote'   => 'Block Quotation',
            'list'    => 'Ordered or Unordered',
            '*'       => 'List Item',
            'h'       => 'Header 5',
            'img'     => 'Inline Image',
            'url'     => 'Hypertext Link',
            'b'       => 'Strong Emphasis',
            'i'       => 'Emphasis',
            's'       => 'Strike-through',
            'u'       => 'Underlined Text',
            'color'   => 'Color',
            'tt'      => 'Teletype Text',
            'center'  => 'Centered Block',
            'err'     => 'Error Codes',
        );
        $tagSummary = Container::get('hooks')->fire('model.admin.parser.tagSummary', $tagSummary);
        
        return $tagSummary;
    }
}
