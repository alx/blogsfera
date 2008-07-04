<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');

$arr = array();
$arr[] = fs_mkPair(1, fs_r('Administrator'));
$arr[] = fs_mkPair(2, fs_r('User'));
?>
<div class='<?php echo fs_lang_dir()?>'>
	<h3><?php fs_e('Create a new user')?></h3>
	<table>
		<tr>
			<td><label for='new_username'><?php fs_e('User name')?></label></td>
			<td><input type='text' size='30' id='new_username' value=''/></td>
		</tr>
		<tr>
			<td><label for='new_email'><?php fs_e('Email')?></label></td>
			<td><input type='text' size='30' id='new_email' value='' /></td>
		</tr>
		<tr>
			<td><label for='new_password'><?php fs_e('Password')?></label></td>
			<td><input type='password' size='30' id='new_password' value='' /></td>
		</tr>
		<tr>
			<td><label for='new_password_verify'><?php fs_e('Verify password')?></label></td>
			<td><input type='password' size='30' id='new_password_verify' value='' /></td>
		</tr>
		<tr>
			<td><label for='new_security_level'><?php fs_e('Security level')?></label></td>
			<td><?php echo fs_create_dropbox($arr,2,'new_security_level','')?></td>
		</tr>
		<tr>
			<td colspan='2'>
				<button id='create_user' class='button' onclick='FS.createUser(this)'><?php fs_e('Create user')?></button>
				<button class='button' onclick='closeParentWindow(this)'><?php fs_e('Close')?></button>
			</td>
		</tr>
	</table>
</div>
