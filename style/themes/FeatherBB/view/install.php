<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

// Make sure no one attempts to run this script "directly"
if (!defined('FEATHER')) {
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php _e('FeatherBB Installation') ?></title>
    <link rel="stylesheet" type="text/css" href="style/themes/<?php echo $feather->forum_env['FORUM_NAME'] ?>/style.css" />
</head>

<body>
    <div id="puninstall" class="pun">
        <div class="top-box"><div><!-- Top Corners --></div></div>
        <div class="punwrap">

            <section class="container">
                <div id="brdheader" class="block">
                    <div class="box">
                        <div id="brdtitle" class="inbox">
                            <h1><span><?php _e('FeatherBB Installation') ?></span></h1>
                            <div id="brddesc"><p><?php _e('Welcome') ?></p></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="container">
                <div id="brdmain">
                    <?php if (count($languages) > 1): ?>
                        <div class="blockform">
                            <h2><span><?php _e('Choose install language') ?></span></h2>
                            <div class="box">
                                <form id="install" method="post" action="">
                                    <input type="hidden" name="choose_lang" value="1">
                                    <div class="inform">
                                        <fieldset>
                                            <legend><?php _e('Install language') ?></legend>
                                            <div class="infldset">
                                                <p><?php _e('Choose install language info') ?></p>
                                                <label><strong><?php _e('Install language') ?></strong>
                                                    <br /><select name="install_lang">
                                                        <?php

                                                        foreach ($languages as $lang) {
                                                            echo "\t\t\t\t\t".'<option value="'.$lang.'" '.($data['default_lang'] == $lang ? 'selected' : '').'>'.$lang.'</option>'."\n";
                                                        }

                                                        ?>
                                                    </select>
                                                    <br /></label>
                                                </div>
                                            </fieldset>
                                        </div>
                                        <p class="buttons"><input type="submit" value="<?php _e('Change language') ?>" /></p>
                                    </form>
                                </div>
                            </div>
                        <?php endif;
                        ?>

                        <div class="blockform">
                            <h2><span><?php echo sprintf(__('Install'), FORUM_VERSION) ?></span></h2>
                            <div class="box">
                                <form id="install" method="post" action="">
                                    <?php if (!empty($errors)): ?>
                                        <div class="inform">
                                            <div class="forminfo error-info">
                                                <h3><?php _e('Errors') ?></h3>
                                                <ul class="error-list">
                                                    <?php
                                                    $errors = (!is_array($errors)) ? array($errors) : $errors;
                                                    foreach ($errors as $error) {
                                                        echo "\t\t\t\t\t\t".'<li><strong>'.$error.'</strong></li>'."\n";
                                                    }
                                                    ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <?php endif;?>

                                    <div class="inform">
                                        <div class="forminfo">
                                            <h3><?php _e('Database setup') ?></h3>
                                            <p><?php _e('Info 1') ?></p>
                                        </div>
                                        <fieldset>
                                            <legend><?php _e('Select database') ?></legend>
                                            <div class="infldset">
                                                <p><?php _e('Info 2') ?></p>
                                                <label class="required"><strong><?php _e('Database type') ?> <span><?php _e('Required') ?></span></strong></label>
                                                <select name="install[db_type]" required>
                                                    <?php

                                                    foreach ($supported_dbs as $id => $db_type) {
                                                        echo "\t\t\t\t\t\t\t".'<option value="'.$id.'">'.$db_type.'</option>'."\n";
                                                    }

                                                    ?>
                                                </select>
                                            </div>
                                        </fieldset>
                                    </div>

                                    <div class="inform">
                                        <fieldset>
                                            <legend><?php _e('Database hostname') ?></legend>
                                            <div class="infldset">
                                                <p><?php _e('Info 3') ?></p>
                                                <label class="required"><strong><?php _e('Database server hostname') ?> <span><?php _e('Required') ?></span></strong></label>
                                                <input type="text" name="install[db_host]" size="50" required />
                                            </div>
                                        </fieldset>
                                    </div>

                                    <div class="inform">
                                        <fieldset>
                                            <legend><?php _e('Database enter name') ?></legend>
                                            <div class="infldset">
                                                <p><?php _e('Info 4') ?></p>
                                                <label class="required"><strong><?php _e('Database name') ?> <span><?php _e('Required') ?></span></strong></label>
                                                <input type="text" name="install[db_name]" size="30" required />
                                            </div>
                                        </fieldset>
                                    </div>

                                    <div class="inform">
                                        <fieldset>
                                            <legend><?php _e('Database enter informations') ?></legend>
                                            <div class="infldset">
                                                <p><?php _e('Info 5') ?></p>
                                                <label class="conl"><?php _e('Database username') ?></label>
                                                <input type="text" name="install[db_user]" size="30" />
                                                <label class="conl"><?php _e('Database password') ?></label>
                                                <input type="password" name="install[db_pass]" size="30" />
                                                <div class="clearer"></div>
                                            </div>
                                        </fieldset>
                                    </div>

                                    <div class="inform">
                                        <fieldset>
                                            <legend><?php _e('Database enter prefix') ?></legend>
                                            <div class="infldset">
                                                <p><?php _e('Info 6') ?></p>
                                                <label><?php _e('Table prefix') ?></label>
                                                <input type="text" name="install[db_prefix]" size="20" maxlength="30" />
                                            </div>
                                        </fieldset>
                                    </div>

                                    <div class="inform">
                                        <div class="forminfo">
                                            <h3><?php _e('Administration setup') ?></h3>
                                            <p><?php _e('Info 7') ?></p>
                                        </div>
                                        <fieldset>
                                            <legend><?php _e('Administration setup') ?></legend>
                                            <div class="infldset">
                                                <p><?php _e('Info 8') ?></p>
                                                <label class="required"><strong><?php _e('Administrator username') ?> <span><?php _e('Required') ?></span></strong></label>
                                                <input type="text" name="install[username]" size="25" maxlength="25" required />
                                                <label class="conl required"><strong><?php _e('Password') ?> <span><?php _e('Required') ?></span></strong></label>
                                                <input type="password" name="install[password]" size="16" required />
                                                <label class="conl required"><strong><?php _e('Confirm password') ?> <span><?php _e('Required') ?></span></strong></label>
                                                <input type="password" name="install[password_conf]" size="16" required />
                                                <!-- <div class="clearer"></div> -->
                                                <label class="required"><strong><?php _e('Administrator email') ?> <span><?php _e('Required') ?></span></strong></label>
                                                <input type="text" name="install[email]" size="50" maxlength="80" required />
                                            </div>
                                        </fieldset>
                                    </div>

                                    <div class="inform">
                                        <div class="forminfo">
                                            <h3><?php _e('Board setup') ?></h3>
                                            <p><?php _e('Info 11') ?></p>
                                        </div>
                                        <fieldset>
                                            <legend><?php _e('General information') ?></legend>
                                            <div class="infldset">
                                                <label class="required"><strong><?php _e('Board title') ?> <span><?php _e('Required') ?></span></strong></label>
                                                <input type="text" name="install[title]" value="<? echo $data['title'] ?>" size="60" maxlength="255" required />
                                                <label><?php _e('Board description') ?></label>
                                                <input type="text" name="install[description]" value="<? echo $data['description'] ?>" size="60" maxlength="255" required />
                                                <label class="required"><strong><?php _e('Base URL') ?> <span><?php _e('Required') ?></span></strong></label>
                                                <input type="text" name="install[base_url]" value="<? echo $data['base_url'] ?>" size="60" maxlength="100" required />
                                                <label class="required"><strong><?php _e('Default language') ?> <span><?php _e('Required') ?></span></strong></label>
                                                <select name="install[default_lang]" required />
                                                <?php
                                                foreach ($languages as $lang) {
                                                    echo "\t\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$lang.'" '.($data['default_lang'] == $lang ? 'selected' : '').' >'.$lang.'</option>'."\n";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </fieldset>
                                </div>

                                <p class="buttons"><input type="submit" value="<?php _e('Start install') ?>" /></p>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="end-box"><div><!-- Bottom Corners --></div></div>
    </div>
</body>
</html>
