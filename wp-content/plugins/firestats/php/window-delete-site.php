<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
$site_id = isset($_GET['site_id']) ? $_GET['site_id'] : null;
?>

<div class='<?php echo fs_lang_dir()?>'>
	<h3><?php fs_e('Delete site')?></h3>
	<table>
		<tr>
			<td colspan='2'>
				<span class="notice"><?php fs_e("This will delete this site from the database, the operation is irreversible!")?></span><br/>
				<?php fs_e("What do you want to do with the site hits?")?><br/>
				
				<select id="delete_type" onchange="updateDeleteDialog()">
					<option value="delete"><?php fs_e('Delete all the hits')?></option>
					<option value="change"><?php fs_e('Transfer the hits to another site')?></option>
				</select><br/>
				<span id="transfer_option" class="hidden">
				<?php fs_e("Enter an existing site ID to transfer the hits to")?>
				<input type="text" id="transfer_site_id" value=""/>
				</span>
				<input type="hidden" id="delete_site_id" value=""/>
			</td>
		</tr>
		<tr>
			<td colspan='2'>
				<button id='fs_site_delete_button' class='button' onclick='FS.deleteSite(<?php echo $site_id?>, this)'><?php fs_e('Delete')?></button>
				<button class='button' onclick='closeParentWindow(this)'><?php fs_e('Close')?></button>
			</td>
		</tr>
	</table>
	
</div>	
