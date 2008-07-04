<?php
@header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
?>

<!-- if the javascript for this page was not already loaded, load it now -->
<script type="text/javascript">
//<![CDATA[
if (typeof(FS.newUserDialog) != "function")
{
	FS.loadJavaScript("<?php echo fs_url('js/page-users.js.php').fs_get_request_suffix()?>");
	FS.loadCSS("<?php echo fs_url('css/page-users.css.php').fs_get_request_suffix()?>");
}
//]]>

</script>
<div id="fs_users_div">
	<!-- Use table for base layout -->
	<table>
		<tr>
			<td>
				<div id="fs_users_table_holder" class="fwrap">
					<h2><?php fs_e('Manage users')?></h2>
					<div id="fs_users_table">
						<?php echo fs_get_users_manage_table()?>
					</div>
				</div>
			</td>
			<td>
				<div id="fs_users_tab_help" class="fwrap">
					<h2><?php fs_e('Help')?></h2>
					<?php fs_e('You can manage users here')?>
				</div>
			</td>
		</tr>
	</table>
</div>
