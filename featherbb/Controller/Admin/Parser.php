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
use FeatherBB\Model\Cache;

class Parser
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Parser();
        Lang::load('admin/parser');
        if (!User::isAdmin()) {
            throw new Error(__('No permission'), '403');
        }

        if (!Container::get('cache')->isCached('smilies')) {
            Container::get('cache')->store('smilies', Cache::getSmilies());
        }
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.parser.display');

        $smilies = Container::get('cache')->retrieve('smilies');

        if (Input::post('form_sent')) {
            // Upload new smiley image to style/img/smilies
            if (Input::post('upload') && isset($_FILES['new_smiley']) && isset($_FILES['new_smiley']['error'])) {
                $f = $_FILES['new_smiley'];
                switch ($f['error']) {
                    case 0: // 0: Successful upload.
                        $name = str_replace(' ', '_', $f['name']);            // Convert spaces to underscore.
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

            // Set new $smilies values:
            if (Input::post('smiley_text') && is_array(Input::post('smiley_text')) &&
                Input::post('smiley_file') && is_array(Input::post('smiley_file')) &&
                count(Input::post('smiley_text')) === count(Input::post('smiley_file'))) {
                $stext = Input::post('smiley_text');
                $sfile = Input::post('smiley_file');
                $len = count(Input::post('smiley_text'));
                $smilies = [];
                for ($i = 0; $i < $len; ++$i) { // Loop through all posted smileys.
                    if (isset($stext[$i]) && $stext[$i] != '' && $sfile !== 'select new file') {
                        echo $i.'<br>';
                        $smilies[$stext[$i]] = $sfile[$i];
                    }
                }

                Container::get('cache')->store('smilies', $smilies);
            }

            return Router::redirect(Router::pathFor('adminParser'), __('save_success'));
        }

        AdminUtils::generateAdminMenu('parser');

        return View::setPageInfo([
                'title' => [Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Parser')],
                'active_page' => 'admin',
                'admin_console' => true,
                'smiley_files' => $this->model->getSmileyFiles(),
                'smilies' =>    $smilies,
                'urlBase' => URL::base().'/style/img/smilies/',
            ]
        )->addTemplate('@forum/admin/parser')->display();
    }
}
