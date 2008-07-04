<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');

$user = fs_get_user($_GET['user_id']);
if ($user === false) die(fs_db_error());
if ($user === null) die(fs_r("No such user"));
?>
<div class='<?php echo fs_lang_dir()?>'>
	<h3><?php fs_e('Delete user')?></h3>
	<table>
		<tr>
			<td colspan='2'><?php fs_e("Are you sue you want to delete the user <b>$user->username</b>?")?></td>
		</tr>
		<tr>
			<td colspan='2'>
				<button class='button' onclick='FS.deleteUser(<?php echo $_GET['user_id']?>,this)'><?php fs_e('Delete')?></button>
				<button class='button' onclick='closeParentWindow(this)'><?php fs_e('Close')?></button>
			</td>
		</tr>
	</table>
</div>
