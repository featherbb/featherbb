<?php

use \FeatherBB\Core\Utils;
use \FeatherBB\Core\Url;
?>

            <div class="blockform">
                <h2><span>Add categories</span></h2>
                <div class="box">
                    <form method="post" action="/featherbb/admin/categories/add/">
                        <div class="inform">
                            <fieldset>
                                <legend>Add categories</legend>
                                <div class="infldset">
                                    <table class="aligntop">
                                        <tr>
                                            <th scope="row">Add a new category<div><input type="submit" value="Add new" tabindex="2" /></div></th>
                                            <td>
                                                <input type="hidden" name="featherbb_csrf" value="a80bf4c1a705d1458486db7b651adbf60aa3ee51">
                                                <input type="text" name="cat_name" size="35" maxlength="80" tabindex="1" />
                                                <span>The name of the new category you want to add. You can edit the name of the category later (see below). Go to <a href="/featherbb/admin/forums/">Forums</a> to add forums to your new category.</span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </fieldset>
                        </div>
                    </form>
                </div>

    <?php if (!empty($blocks)): ?>
        <h2 class="block2"><span>Delete categories</span></h2>
        <div class="box">
            <form method="post" action="/featherbb/admin/categories/delete/">
                <input type="hidden" name="featherbb_csrf" value="a80bf4c1a705d1458486db7b651adbf60aa3ee51">
                <div class="inform">
                    <fieldset>
						<div class="infldset">
							<table cellspacing="0">
    							<thead>
    								<tr>
    									<th class="tcl" scope="col">Folder name</th>
    									<th class="hidehead" scope="col">Actions</th>
    								</tr>
    							</thead>
    							<tbody>
<?php foreach ($blocks as $bid => $block): ?>
                    				<tr>
                    					<td class="tcl"><input type="text" name="folder[4]" value="Test" size="24" maxlength="30" /></td>
                    					<td><input type="submit" name="update[4]" value="Update" />&#160;<input type="submit" name="remove[4]" value="Remove" /></td>
                    				</tr>
<?php endforeach; ?>
                                </tbody>
							</table>
						</div>
					</fieldset>
                </div>
            </form>
        </div>
<?php endif; ?>
