<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');

$user = fs_get_user($_GET['user_id']);
if ($user === false) die(fs_db_error());
if ($user === null) die(fs_r("No such user"));
	
$arr = array();
$arr[] = fs_mkPair(1, fs_r('Administrator'));
$arr[] = fs_mkPair(2, fs_r('User'));
?>
<div class='<?php echo fs_lang_dir()?>'>
	<h3><?php fs_e('Edit user')?></h3>
	<table>
		<tr>
			<td><label for='new_username'><?php fs_e('User name')?></label></td>
			<td><input type='text' size='30' id='new_username' value='<?php echo $user->username?>'/></td>
		</tr>
		<tr>
			<td><label for='new_email'><?php fs_e('Email')?></label></td>
			<td><input type='text' size='30' id='new_email' value='<?php echo $user->email?>' /></td>
		</tr>
		<tr>
			<td><label for='new_password'><?php fs_e('New password (Optional)')?></label></td>
			<td><input type='password' size='30' id='new_password' value='' /></td>
		</tr>
		<tr>
			<td><label for='new_password_verify'><?php fs_e('Verify new password')?></label></td>
			<td><input type='password' size='30' id='new_password_verify' value='' /></td>
		</tr>
		<tr>
			<td><label for='new_security_level'><?php fs_e('Security level')?></label></td>
			<td><?php echo fs_create_dropbox($arr,$user->security_level,'new_security_level','')?></td>
		</tr>
		<tr>
			<td colspan='2'>
				<button id='create_user' class='button' onclick='FS.updateUser(<?php echo $_GET['user_id']?>,this)'><?php fs_e('Update')?></button>
				<button class='button' onclick='closeParentWindow(this)'><?php fs_e('Close')?></button>
			</td>
		</tr>
	</table>
</div>
