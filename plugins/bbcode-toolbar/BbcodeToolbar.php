<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins;

use FeatherBB\Core\Plugin as BasePlugin;

class BbcodeToolbar extends BasePlugin
{

    public function run()
    {
        // Add language files into javascript footer block
        $this->feather->hooks->bind('view.alter_data', [$this, 'addLanguage']);
        // Support default actions
        $this->hooks->bind('controller.post.create', [$this, 'addToolbar']);
        $this->hooks->bind('controller.post.edit', [$this, 'addToolbar']);
        $this->hooks->bind('controller.topic.display', [$this, 'addToolbar']);
        // Support PMs plugin
        $this->hooks->bind('conversationsPlugin.send.preview', [$this, 'addToolbar']);
        $this->hooks->bind('conversationsPlugin.send.display', [$this, 'addToolbar']);
    }

    public function addLanguage($data)
    {
        load_textdomain('bbcode-toolbar', dirname(__FILE__).'/lang/'.$this->feather->user->language.'/bbeditor.mo');
        $lang_bbeditor = array(
            'btnBold' => __('btnBold', 'bbcode-toolbar'),
            'btnItalic' => __('btnItalic', 'bbcode-toolbar'),
            'btnUnderline' => __('btnUnderline', 'bbcode-toolbar'),
            'btnColor' => __('btnColor', 'bbcode-toolbar'),
            'btnLeft' => __('btnLeft', 'bbcode-toolbar'),
            'btnRight' => __('btnRight', 'bbcode-toolbar'),
            'btnJustify' => __('btnJustify', 'bbcode-toolbar'),
            'btnCenter' => __('btnCenter', 'bbcode-toolbar'),
            'btnLink' => __('btnLink', 'bbcode-toolbar'),
            'btnPicture' => __('btnPicture', 'bbcode-toolbar'),
            'btnList' => __('btnList', 'bbcode-toolbar'),
            'btnQuote' => __('btnQuote', 'bbcode-toolbar'),
            'btnCode' => __('btnCode', 'bbcode-toolbar'),
            'promptImage' => __('promptImage', 'bbcode-toolbar'),
            'promptUrl' => __('promptUrl', 'bbcode-toolbar'),
            'promptQuote' => __('promptQuote', 'bbcode-toolbar')
        );
        $data['jsVars']['bbcodeToolbar'] = json_encode($lang_bbeditor);
        return $data;
    }

    public function addToolbar()
    {
        $this->feather->template->addAsset('css', 'plugins/bbcode-toolbar/style/bbeditor.css', array('type' => 'text/css', 'rel' => 'stylesheet'));
        $this->feather->template->addAsset('css', 'plugins/bbcode-toolbar/style/colorPicker.css', array('type' => 'text/css', 'rel' => 'stylesheet'));
        $this->feather->template->addAsset('js', 'plugins/bbcode-toolbar/style/bbeditor.js', array('type' => 'text/javascript'));
        $this->feather->template->addAsset('js', 'plugins/bbcode-toolbar/style/colorPicker.js', array('type' => 'text/javascript'));
        return true;
    }

}
