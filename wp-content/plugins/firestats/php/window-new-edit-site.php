<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');
$site_id = isset($_GET['site_id']) ? $_GET['site_id'] : null;
$views = '';
$visits = '';
if (isset($site_id))
{
	$site = fs_get_site($site_id);
	$baseline = fs_get_site_baseline_values($site_id);
	$views = $baseline->views;
	$visits = $baseline->visits;
}
?>
<div class='<?php echo fs_lang_dir()?>'>
	<h3><?php isset($site_id) ? fs_e('Edit site') : fs_e('Create a new site')?></h3>
	<table>
		<tr>
			<td><label for='new_username'><?php fs_e('ID')?></label></td>
			<td>
				<input type="hidden" id="original_site_id" value="<?php echo isset($site_id) ? $site_id : fs_r('Automatic')?>"/>
				<input type="text" id="site_edit_id" onkeypress="$('fs_clear_site_id').style.display='inline'" onfocus="this.select()" value="<?php echo isset($site_id) ? $site_id : fs_r('Automatic')?>"/>
				<?php fs_create_wiki_help_link('SiteID')?>
				<input id="fs_clear_site_id" style="display:none" type='image' title="<?php fs_e('Clear')?>" class='img_btn' src="<?php echo fs_url("img/clear.png")?>"
					onclick="FS.clearSiteID()"
				/>
			</td>
		</tr>
		<tr>
			<td><label for='site_edit_name'><?php fs_e('Name')?></label></td>
			<td><input type="text" size='20' id="site_edit_name" value="<?php echo 	isset($site) ? $site->name : ""?>"/></td>
		</tr>
		<tr>
			<td><label for='site_edit_type'><?php fs_e('Type')?></label></td>
			<td><select id="site_edit_type"><?php echo fs_get_site_type_options(isset($site) ? $site->type : null)?></select></td>
		</tr>
		<tr>
			<td><label for='baseline_views'><?php fs_e('Baseline page views')?></label></td>
			<td><input type='text' size='20' id='baseline_views' value='<?php echo $views?>' onfocus="this.select()"/><?php fs_create_wiki_help_link('BaselineValues')?></td>
		</tr>
		<tr>
			<td><label for='baseline_visitors'><?php fs_e('Baseline visitors')?></label></td>
			<td><input type='text' size='20' id='baseline_visitors' value='<?php echo $visits?>' onfocus="this.select()"/><?php fs_create_wiki_help_link('BaselineValues')?></td>
		</tr>
		<tr>
			<td colspan='2'>
				<button id='create_user' class='button' onclick='<?php echo isset($site_id) ? "FS.updateSite($site_id, this)": "FS.createSite(this)" ?>'><?php fs_e('Save')?></button>
				<button class='button' onclick='closeParentWindow(this)'><?php fs_e('Close')?></button>
			</td>
		</tr>
	</table>
</div>
