<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.search.form.start');
?>

<div id="searchform" class="blockform">
    <h2><span><?php _e('Search') ?></span></h2>
    <div class="box">
        <form id="search" method="get" action="<?= Router::pathFor('search') ?>">
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Search criteria legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="action" value="search" />
                        <label class="conl"><?php _e('Keyword search') ?><br /><input type="text" name="keywords" size="40" maxlength="100" /><br /></label>
                        <label class="conl"><?php _e('Author search') ?><br /><input id="author" type="text" name="author" size="25" maxlength="25" /><br /></label>
                        <p class="clearb"><?php _e('Search info') ?></p>
                    </div>
                </fieldset>
            </div>
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Search in legend') ?></legend>
                    <div class="infldset">
                    <?= $forums ?>
                        <label class="conl"><?php _e('Search in') ?>
                        <br /><select id="search_in" name="search_in">
                            <option value="0"><?php _e('Message and subject') ?></option>
                            <option value="1"><?php _e('Message only') ?></option>
                            <option value="-1"><?php _e('Topic only') ?></option>
                        </select>
                        <br /></label>
                        <p class="clearl"><?php _e('Search in info') ?></p>
<?php echo(ForumSettings::get('o_search_all_forums') == '1' || User::get()->is_admmod ? '<p>'.__('Search multiple forums info').'</p>' : '') ?>
                    </div>
                </fieldset>
            </div>
            <div class="inform">
                <fieldset>
                    <legend><?php _e('Search results legend') ?></legend>
                    <div class="infldset">
                        <label class="conl"><?php _e('Sort by') ?>
                        <br /><select name="sort_by">
                            <option value="0"><?php _e('Sort by post time') ?></option>
                            <option value="1"><?php _e('Sort by author') ?></option>
                            <option value="2"><?php _e('Sort by subject') ?></option>
                            <option value="3"><?php _e('Sort by forum') ?></option>
                        </select>
                        <br /></label>
                        <label class="conl"><?php _e('Sort order') ?>
                        <br /><select name="sort_dir">
                            <option value="DESC"><?php _e('Descending') ?></option>
                            <option value="ASC"><?php _e('Ascending') ?></option>
                        </select>
                        <br /></label>
                        <label class="conl"><?php _e('Show as') ?>
                        <br /><select name="show_as">
                            <option value="topics"><?php _e('Show as topics') ?></option>
                            <option value="posts"><?php _e('Show as posts') ?></option>
                        </select>
                        <br /></label>
                        <p class="clearb"><?php _e('Search results info') ?></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="search" value="<?php _e('Submit') ?>" accesskey="s" /></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.search.form.end');