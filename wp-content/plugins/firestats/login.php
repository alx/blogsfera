<script type="text/javascript">
//<![CDATA[
function login()
{
	sendRequest('action=login&username='+$F('username')+'&password='+$F('password')+'&remember_me='+$F('remember_me'));
}
//]]>
</script>
<div class="fwrap" style="width: 400px; margin: 0 auto;margin-bottom: 10px">
	<h3><?php fs_e('Login')?></h3>
	<table>
		<tr>
			<td><label for="username"><?php fs_e('Username')?></label></td>
			<td><input type="text" size="30" id="username" onkeypress="return trapEnter(event,'login()')" value="" /></td>
		</tr>
		<tr>
			<td><label for="password"><?php fs_e('Password')?></label></td>
			<td><input type="password" size="30" id="password"  onkeypress="return trapEnter(event,'login()')" value="" /></td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" id="remember_me" onkeypress="return trapEnter(event,'login()')" checked="checked"/>
				<label for="remember_me"><?php fs_e('Remember me for the next 14 days')?></label>
				<input type="button" onclick="login()" value="<?php fs_e('Login')?>"/>
			</td>
		</tr>
		<tr>
			<td colspan='2'>
			<?php echo sprintf(fs_r("Forgot your password? click %s."),fs_link("tools.php?file_id=reset_password",fs_r("here")))?>
			</td>
		</tr>
	</table>
	<script type="text/javascript">
	//<![CDATA[
	var user = readCookie("FS_LAST_USERNAME");
	var pass = readCookie("FS_LAST_PASSWORD_MD5");
	var remember = $('remember_me').checked = readCookie("FS_REMEMBER_ME") == 'on' ? 'checked' : '';
	if (user != null) $('username').value = user;
	if (pass  != null) $('password').value = pass ; 
	$('remember_me').checked = readCookie("FS_REMEMBER_ME") == 'on' ? 'checked' : '';
	if ($F('username') != '' && $F('password') != '' && $('remember_me').checked == true)
	{
		login();
	} 
	//]]>
	</script>
</div>
<script type="text/javascript">
//<![CDATA[
$('username').focus();
//]]>
</script>
