<?php
require_once('admin.php');

$title = __('Writing Settings');
$parent_file = 'options-general.php';

include('admin-header.php');
?>

<div class="wrap">
<h2><?php _e('Writing Settings') ?></h2>
<form method="post" action="options.php">
<?php wp_nonce_field('writing-options') ?>
<input type='hidden' name='option_page' value='writing' />
<table class="form-table">
<tr valign="top">
<th scope="row"><label for="default_post_edit_rows"> <?php _e('Size of the post box') ?></label></th>
<td><input name="default_post_edit_rows" type="text" id="default_post_edit_rows" value="<?php form_option('default_post_edit_rows'); ?>" size="2" style="width: 1.5em;" />
<?php _e('lines') ?></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('Formatting') ?></th>
<td><fieldset><legend class="hidden"><?php _e('Formatting') ?></legend>
<label for="use_smilies">
<input name="use_smilies" type="checkbox" id="use_smilies" value="1" <?php checked('1', get_option('use_smilies')); ?> />
<?php _e('Convert emoticons like <code>:-)</code> and <code>:-P</code> to graphics on display') ?></label><br />
<label for="use_balanceTags"><input name="use_balanceTags" type="checkbox" id="use_balanceTags" value="1" <?php checked('1', get_option('use_balanceTags')); ?> /> <?php _e('WordPress should correct invalidly nested XHTML automatically') ?></label>
</fieldset></td>
</tr>
<tr valign="top">
<th scope="row"><label for="default_category"><?php _e('Default Post Category') ?></label></th>
<td><select name="default_category" id="default_category">
<?php
$categories = get_categories('get=all');
foreach ($categories as $category) :
$category = sanitize_category($category);
if ($category->term_id == get_option('default_category')) $selected = " selected='selected'";
else $selected = '';
echo "\n\t<option value='$category->term_id' $selected>$category->name</option>";
endforeach;
?>
</select></td>
</tr>
<tr valign="top">
<th scope="row"><label for="default_link_category"><?php _e('Default Link Category') ?></label></th>
<td><select name="default_link_category" id="default_link_category">
<?php
$link_categories = get_terms('link_category', 'get=all');
foreach ($link_categories as $category) :
$category = sanitize_term($category, 'link_category');
if ($category->term_id == get_option('default_link_category')) $selected = " selected='selected'";
else $selected = '';
echo "\n\t<option value='$category->term_id' $selected>$category->name</option>";
endforeach;
?>
</select></td>
</tr>
</table>

<h3><?php _e('Remote Publishing') ?></h3>
<p><?php printf(__('To post to WordPress from a desktop blogging client or remote website that uses the Atom Publishing Protocol or one of the XML-RPC publishing interfaces you must enable them below.')) ?></p>
<table class="form-table">
<tr valign="top">
<th scope="row"><?php _e('Atom Publishing Protocol') ?></th>
<td><fieldset><legend class="hidden"><?php _e('Atom Publishing Protocol') ?></legend>
<label for="enable_app">
<input name="enable_app" type="checkbox" id="enable_app" value="1" <?php checked('1', get_option('enable_app')); ?> />
<?php _e('Enable the Atom Publishing Protocol.') ?></label><br />
</fieldset></td>
</tr>
<tr valign="top">
<th scope="row"><?php _e('XML-RPC') ?></th>
<td><fieldset><legend class="hidden"><?php _e('XML-RPC') ?></legend>
<label for="enable_xmlrpc">
<input name="enable_xmlrpc" type="checkbox" id="enable_xmlrpc" value="1" <?php checked('1', get_option('enable_xmlrpc')); ?> />
<?php _e('Enable the WordPress, Movable Type, MetaWeblog and Blogger XML-RPC publishing protocols.') ?></label><br />
</fieldset></td>
</tr></table>


<p class="submit">
<input type="hidden" name="action" value="update" />
<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
</p>
</form>
</div>

<?php include('./admin-footer.php') ?>
