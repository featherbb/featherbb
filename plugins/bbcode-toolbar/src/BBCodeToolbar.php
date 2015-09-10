<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Plugins;

use FeatherBB\Core\Plugin as BasePlugin;

class BBCodeToolbar extends BasePlugin
{

    public function run()
    {
        $this->hooks->bind('post.create', [$this, 'addToolbar']);
        $this->hooks->bind('post.edit', [$this, 'addToolbar']);
        $this->hooks->bind('topic.display', [$this, 'addToolbar']);
        // $this->hooks->bind('view.alter_data', function($data){
        //     var_dump($data);
        // });
    }

    public function addToolbar()
    {
        $this->feather->template->addAsset('css', 'style/imports/bbeditor.css', array('type' => 'text/javascript', 'rel' => 'stylesheet'));
        $this->feather->template->addAsset('css', 'style/imports/colorPicker.css', array('type' => 'text/javascript', 'rel' => 'stylesheet'));
        $this->feather->template->addAsset('js', 'style/imports/bbeditor.js', array('type' => 'text/javascript'));
        $this->feather->template->addAsset('js', 'style/imports/colorPicker.js', array('type' => 'text/javascript'));
        return true;
    }

}
