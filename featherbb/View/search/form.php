<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
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
    <h2><span><?= __('Search') ?></span></h2>
    <div class="box">
        <form id="search" method="get" action="<?= Router::pathFor('search') ?>">
            <div class="inform">
                <fieldset>
                    <legend><?= __('Search criteria legend') ?></legend>
                    <div class="infldset">
                        <input type="hidden" name="action" value="search" />
                        <label class="conl"><?= __('Keyword search') ?><br /><input type="text" name="keywords" size="40" maxlength="100" pattern=".{<?= ForumEnv::get('FEATHER_SEARCH_MIN_WORD') ?>,}" autofocus required /><br /></label>
                        <label class="conl"><?= __('Author search') ?><br /><input id="author" type="text" name="author" size="25" maxlength="25" /><br /></label>
                        <p class="clearb"><?= __('Search info') ?></p>
                    </div>
                </fieldset>
            </div>
            <div class="inform">
                <fieldset>
                    <legend><?= __('Search in legend') ?></legend>
                    <div class="infldset">
                    <?= $forums ?>
                        <label class="conl"><?= __('Search in') ?>
                        <br /><select id="search_in" name="search_in">
                            <option value="0"><?= __('Message and subject') ?></option>
                            <option value="1"><?= __('Message only') ?></option>
                            <option value="-1"><?= __('Topic only') ?></option>
                        </select>
                        <br /></label>
                        <p class="clearl"><?= __('Search in info') ?></p>
<?php echo(ForumSettings::get('o_search_all_forums') == '1' || User::isAdminMod() ? '<p>'.__('Search multiple forums info').'</p>' : '') ?>
                    </div>
                </fieldset>
            </div>
            <div class="inform">
                <fieldset>
                    <legend><?= __('Search results legend') ?></legend>
                    <div class="infldset">
                        <label class="conl"><?= __('Sort by') ?>
                        <br /><select name="sort_by">
                            <option value="0"><?= __('Sort by post time') ?></option>
                            <option value="1"><?= __('Sort by author') ?></option>
                            <option value="2"><?= __('Sort by subject') ?></option>
                            <option value="3"><?= __('Sort by forum') ?></option>
                        </select>
                        <br /></label>
                        <label class="conl"><?= __('Sort order') ?>
                        <br /><select name="sort_dir">
                            <option value="DESC"><?= __('Descending') ?></option>
                            <option value="ASC"><?= __('Ascending') ?></option>
                        </select>
                        <br /></label>
                        <label class="conl"><?= __('Show as') ?>
                        <br /><select name="show_as">
                            <option value="topics"><?= __('Show as topics') ?></option>
                            <option value="posts"><?= __('Show as posts') ?></option>
                        </select>
                        <br /></label>
                        <p class="clearb"><?= __('Search results info') ?></p>
                    </div>
                </fieldset>
            </div>
            <p class="buttons"><input type="submit" name="search" value="<?= __('Submit') ?>" accesskey="s" /></p>
        </form>
    </div>
</div>

<?php
Container::get('hooks')->fire('view.search.form.end');
