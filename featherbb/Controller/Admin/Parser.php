<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Error;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Parser
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Parser();
        Lang::load('admin/parser');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.parser.display');

        // This is where the parser data lives and breathes.
        $cache_file = ForumEnv::get('FEATHER_ROOT').'cache/cache_parser_data.php';

        // If RESET button pushed, or no cache file, re-compile master bbcode source file.
        if (Input::post('reset') || !file_exists($cache_file)) {
            require_once(ForumEnv::get('FEATHER_ROOT').'featherbb/Core/parser/bbcd_source.php');
            require_once(ForumEnv::get('FEATHER_ROOT').'featherbb/Core/parser/bbcd_compile.php');
            return Router::redirect(Router::pathFor('adminParser'), __('reset_success'));
        }

        // Load the current BBCode $pd array from featherbb/Core/parser/parser_data.inc.php.
        require_once($cache_file);            // Fetch $pd compiled global regex data.
        $bbcd = $pd['bbcd'];                // Local scratch copy of $bbcd.
        $smilies = $pd['smilies'];            // Local scratch copy of $smilies.
        $config = $pd['config'];            // Local scratch copy of $config.
        $count = count($bbcd);

        if (Input::post('form_sent')) {

            // Upload new smiley image to style/img/smilies
            if (Input::post('upload') && isset($_FILES['new_smiley']) && isset($_FILES['new_smiley']['error'])) {
                $f = $_FILES['new_smiley'];
                switch ($f['error']) {
                    case 0: // 0: Successful upload.
                        $name = str_replace(' ', '_', $f['name']);            // Convert spaces to underscoree.
                        $name = preg_replace('/[^\w\-.]/S', '', $name);        // Weed out all unsavory filename chars.
                        if (preg_match('/^[\w\-.]++$/', $name)) {            // If we have a valid filename?
                            if (preg_match('%^image/%', $f['type'])) {        // If we have an image file type?
                                if ($f['size'] > 0 && $f['size'] <= ForumSettings::get('o_avatars_size')) {
                                    if (move_uploaded_file($f['tmp_name'], ForumEnv::get('FEATHER_ROOT') .'style/img/smilies/'. $name)) {
                                        return Router::redirect(Router::pathFor('adminParser'), __('upload success'));
                                    } else { //  Error #1: 'Smiley upload failed. Unable to move to smiley folder.'.
                                        throw new Error(__('upload_err_1'), 500);
                                    }
                                } else { // Error #2: 'Smiley upload failed. File is too big.'
                                    throw new Error(__('upload_err_2'), 400);
                                }
                            } else { // Error #3: 'Smiley upload failed. File type is not an image.'.
                                throw new Error(__('upload_err_3'), 400);
                            }
                        } else { // Error #4: 'Smiley upload failed. Bad filename.'
                            throw new Error(__('upload_err_4'), 400);
                        }
                        break;
                    case 1: // case 1 similar to case 2 so fall through...
                    case 2: throw new Error(__('upload_err_2'), 400);    // File exceeds MAX_FILE_SIZE.
                    case 3: throw new Error(__('upload_err_5'), 400);    // File only partially uploaded.
                    //        case 4: break; // No error. Normal response when this form element left empty
                    case 4: throw new Error(__('upload_err_6'), 400);    // No filename.
                    case 6: throw new Error(__('upload_err_7'), 500);    // No temp folder.
                    case 7: throw new Error(__('upload_err_8'), 500);    // Cannot write to disk.
                    default: throw new Error(__('upload_err_9'), 500);        // Generic/unknown error
                }
            }

            // Set new $config values:
            if (Input::post('config')) {
                $pcfg = Input::post('config');

                if (isset($pcfg['textile'])) {
                    if ($pcfg['textile'] == '1') {
                        $config['textile'] = true;
                    } else {
                        $config['textile'] = false;
                    }
                }
                if (isset($pcfg['quote_links'])) {
                    if ($pcfg['quote_links'] == '1') {
                        $config['quote_links'] = true;
                    } else {
                        $config['quote_links'] = false;
                    }
                }
                if (isset($pcfg['quote_imgs'])) {
                    if ($pcfg['quote_imgs'] == '1') {
                        $config['quote_imgs'] = true;
                    } else {
                        $config['quote_imgs'] = false;
                    }
                }
                if (isset($pcfg['valid_imgs'])) {
                    if ($pcfg['valid_imgs'] == '1') {
                        $config['valid_imgs'] = true;
                    } else {
                        $config['valid_imgs'] = false;
                    }
                }
                if (isset($pcfg['click_imgs'])) {
                    if ($pcfg['click_imgs'] == '1') {
                        $config['click_imgs'] = true;
                    } else {
                        $config['click_imgs'] = false;
                    }
                }
                if (isset($pcfg['max_size']) && preg_match('/^\d++$/', $pcfg['max_size'])) {
                    $config['max_size'] = (int)$pcfg['max_size'];
                }
                if (isset($pcfg['max_width']) && preg_match('/^\d++$/', $pcfg['max_width'])) {
                    $config['max_width'] = (int)$pcfg['max_width']; // Limit default to maximum.
                    if ($config['def_width'] > $config['max_width']) {
                        $config['def_width'] = $config['max_width'];
                    }
                }
                if (isset($pcfg['max_height']) && preg_match('/^\d++$/', $pcfg['max_height'])) {
                    $config['max_height'] = (int)$pcfg['max_height']; // Limit default to maximum.
                    if ($config['def_height'] > $config['max_height']) {
                        $config['def_height'] = $config['max_height'];
                    }
                }
                if (isset($pcfg['def_width']) && preg_match('/^\d++$/', $pcfg['def_width'])) {
                    $config['def_width'] = (int)$pcfg['def_width']; // Limit default to maximum.
                    if ($config['def_width'] > $config['max_width']) {
                        $config['def_width'] = $config['max_width'];
                    }
                }
                if (isset($pcfg['def_height']) && preg_match('/^\d++$/', $pcfg['def_height'])) {
                    $config['def_height'] = (int)$pcfg['def_height']; // Limit default to maximum.
                    if ($config['def_height'] > $config['max_height']) {
                        $config['def_height'] = $config['max_height'];
                    }
                }
                if (isset($pcfg['smiley_size']) && preg_match('/^\s*+(\d++)\s*+%?+\s*+$/', $pcfg['smiley_size'], $m)) {
                    $config['smiley_size'] = (int)$m[1]; // Limit default to maximum.
                }
            }

            // Set new $bbcd values:
            foreach ($bbcd as $tagname => $tagdata) {
                if ($tagname == '_ROOT_') {
                    continue; // Skip last pseudo-tag
                }
                $tag =& $bbcd[$tagname];
                if (Input::post($tagname.'_in_post') && Input::post($tagname.'_in_post') == '1') {
                    $tag['in_post']    = true;
                } else {
                    $tag['in_post']    = false;
                }
                if (Input::post($tagname.'_in_sig') && Input::post($tagname.'_in_sig') == '1') {
                    $tag['in_sig']    = true;
                } else {
                    $tag['in_sig']    = false;
                }
                if (Input::post($tagname.'_depth_max') && preg_match('/^\d++$/', Input::post($tagname.'_depth_max'))) {
                    $tag['depth_max'] = (int)Input::post($tagname.'_depth_max');
                }
            }

            // Set new $smilies values:
            if (Input::post('smiley_text') && is_array(Input::post('smiley_text')) &&
                Input::post('smiley_file') && is_array(Input::post('smiley_file')) &&
                count(Input::post('smiley_text')) === count(Input::post('smiley_file'))) {
                $stext = Input::post('smiley_text');
                $sfile = Input::post('smiley_file');
                $len = count($stext);
                $good = '';
                $smilies = [];
                for ($i = 0; $i < $len; ++$i) { // Loop through all posted smileys.
                    if ($stext[$i] && $sfile !== 'select new file') {
                        $smilies[$stext[$i]] = ['file' => $sfile[$i]];
                    }
                }
            }

            require_once('featherbb/Core/parser/bbcd_compile.php'); // Compile $bbcd and save into $pd['bbcd']
            return Router::redirect(Router::pathFor('adminParser'), __('save_success'));
        }

        AdminUtils::generateAdminMenu('parser');

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Parser')],
                'active_page' => 'admin',
                'admin_console' => true,
                'tag_summary' => $this->model->tagSummary(),
                'smiley_files' => $this->model->get_smiley_files(),
                'bbcd' =>   $bbcd,
                'config' => $config,
                'smilies' =>    $smilies,
                'i'     =>  -1,
            ]
        )->addTemplate('admin/parser.php')->display();
    }
}
