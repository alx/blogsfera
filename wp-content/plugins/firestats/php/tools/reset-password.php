<?php
require_once(dirname(dirname(__FILE__))."/init.php");
require_once(FS_ABS_PATH."/php/html-utils.php");

global $show;
if (!isset($show) || isset($_GET['show']))
{
	$show = true;
	fs_show_page(FS_ABS_PATH."/php/tools/reset-password.php");
	return;
}
?>

<div class="fwrap"
	style="width: 500px; margin: 0 auto; margin-bottom: 10px">
<h3><?php fs_e('Reset password')?></h3>
<?php
$instructions = sprintf(fs_r("You can request an password reset email from here. If it does not work or you don't know your username or email, you can also change your password using the %s"),fs_link("tools.php?file_id=manage_users",fs_r('Emergency user management page')))."</br>";
echo '<div style="margin-bottom: 30px">';
if (isset($_POST['username']))
{
	echo $instructions;
	$username = $_POST['username'];
	$email = isset($_POST['email']) ? $_POST['email'] : "";
	$user = fs_get_user_by_username_and_email($username, $email);
	if ($user === null)
	{
		echo "<div class='error'>".fs_r("A user with this username and email was not found")."</div>";
	}
	else
	if (is_object($user))
	{
		$ok = fs_session_start();
		if ($ok !== true)
		{
			$msg = "Error starting session";
			if (is_string($ok)) $msg .= " :$ok";
			$msg .= "<br/>"; 
			echo $msg;
			return false;
		}
		
		global $FS_SESSION;
		$FS_SESSION['user'] = $user;
		fs_store_session();		
		$sid = fs_get_session_id();
		$headers = "Content-Type: text/html; charset=\"UTF-8\"\r\n";
		$headers .= "MIME-Version: 1.0 ";
		$subject = "=?UTF-8?B?".base64_encode(fs_r("FireStats password recovery"))."?=";
		$msg = sprintf(fs_r("Click %s to change your FireStats password, this link will work for a short time"), fs_link(fs_get_absolute_url($_SERVER['REQUEST_URI']."&reset&sid=$sid"),fs_r("here"), true));
		$res = mail($email, $subject, $msg, $headers);
		if ($res === true)
		{
			echo "<div class='info'>".fs_r("Email sent")."</div>";
		}
		else
		{
			echo "<div class='error'>".fs_r("Failed to send email")."</div>";
		}
	}
	else
	{
		echo "<div class='error'>".$user."</div>";
	}
	?> <?php
}
else
if (isset($_GET['reset']))
{
	$res = fs_resume_user_session();
	if ($res !== true)
	{
		echo "<div class='error'>".sprintf(fs_r("Failed, maybe too much time have passed since you generated the email? %s"),fs_link("tools.php?file_id=reset_password",fs_r("try again")))."</div>";
	}
	else
	{
?>
<script type="text/javascript">
//<![CDATA[
FS.changePassword = function(id)
{
	var user = $F('username');
	var pass1 = $F('new_password');
	var pass2 = $F('new_password_verify');
	var request = 'action=changePassword&id='+id+'&username='+user+'&pass1='+pass1+'&pass2='+pass2;
	sendRequest(request);
}
//]]>
</script> 
<table>
	<tr>
		<td><label for=''><?php fs_e('User name')?></label></td>
		<td><input type='text' size='30' id='username' readonly="readonly" value='<?php echo $user->username?>'/></td>
	</tr>
	<tr>
		<td><label for='new_password'><?php fs_e('New password')?></label></td>
		<td><input type='password' size='30' id='new_password' value='' /></td>
	</tr>
	<tr>
		<td><label for='new_password_verify'><?php fs_e('Verify new password')?></label></td>
		<td><input type='password' size='30' id='new_password_verify' value='' /></td>
	</tr>
	<tr>
		<td colspan='2'>
			<button id='change_password' class='button' onclick='FS.changePassword(<?php echo fs_current_user_id()?>)'><?php fs_e('Change password')?></button>
		</td>
	</tr>
</table>
<?php		
	}
}
else
{
	echo $instructions;
	?></div>
<form action="<?php fs_url("php/tools/reset-password.php?show")?>"
	method="POST">
<table>
	<tr>
		<td><label for='username'><?php fs_e('User name')?></label></td>
		<td><input id='username' name='username' type='text' size="25"></input><br />
		</td>
	</tr>
	<tr>
		<td><label for='email'><?php fs_e('Email address')?></label></td>
		<td><input id='email' name='email' type='text' size="25"></input></td>
	</tr>
	<tr>
		<td colspan="2"><input type='submit'
			value='<?php fs_e('Send password reset email')?>'></input></td>
	</tr>
</table>
</form>
	<?php }?>
</div>
</div>
