<script type="text/javascript">
//<![CDATA[
function createAdmin()
{
    var user    = $F('admin_user');
    var email    = $F('admin_email');
    var pass1    = $F('admin_password');
    var pass2    = $F('admin_password_verify');
    var security_level  = 1; // admin
    var params = 'action=createUser&username='+user+'&email='+email+'&pass1='+pass1+'&pass2='+pass2+'&security_level=' + security_level;
    sendRequest(params, function(response)
    {
		if (response.status == 'ok')
		{
			window.location.reload();
		}
    });
}
//]]>
</script>
<div class="fwrap" style="width: 650px; margin: 0 auto;margin-bottom: 10px">
	<h3><?php fs_e('Create administrator user')?></h3>
	<div style="margin-top: 20px;margin-bottom: 20px">
		<?php fs_e("You need to create an administrator user for FireStats.")?><br/>
		<?php fs_e("The administrator has full control over FireStats, and will be able to create other users/administrators from the administration interface")?>
	</div>
	<table>
		<tr>
			<td><label for="admin_user"><?php fs_e('User name')?></label></td>
			<td><input type="text" size="30" id="admin_user" onkeypress="return trapEnter(event,'createAdmin()')" value="admin" /></td>
		</tr>
		<tr>
			<td><label for="admin_email"><?php fs_e('Email')?></label></td>
			<td><input type="text" size="30" id="admin_email" value=""/></td>
		</tr>
		<tr>
			<td><label for="admin_password"><?php fs_e('Password')?></label></td>
			<td><input type="password" size="30" id="admin_password" onkeypress="return trapEnter(event,'createAdmin()')" value="" /></td>
		</tr>
		<tr>
			<td><label for="admin_password_verify"><?php fs_e('Verify password')?></label></td>
			<td><input type="password" size="30" id="admin_password_verify" onkeypress="return trapEnter(event,'createAdmin()')" value="" /></td>
		<tr>
			<td></td>
			<td><button id="create_admin" class="button" onclick="createAdmin()"><?php fs_e('Create user');?></button></td>
		</tr>
	</table>
</div>
