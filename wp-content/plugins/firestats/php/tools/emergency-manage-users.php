<?php
$enabled = false;

require_once(dirname(dirname(__FILE__))."/init.php");
require_once(FS_ABS_PATH."/php/html-utils.php");

if (!$enabled)
{
	$msg = "<h3>".fs_r("Message")."</h3>";
	$msg .= fs_r("This page will allow you to change the password of any user in FireStats.")."<br/>";
	$msg .= sprintf(fs_r("For security reasons, you must first edit the file %s"),"<b>".__FILE__."</b>")."<br/>";
	$msg .= sprintf(fs_r("and change the line %s to %s and then refresh this page."),"<b>\$enabled = false;</b>","<b>\$enabled = true;</b>")."<br/>";	
	$msg .= fs_r("Don't forget to change it back when you are done.")."<br/>";
	fs_show_page($msg, false);
}
else
{
	echo sprintf("%s Don't forget to change the line back to %s when you are done.",sprintf("<b>%s:</b>",fs_r("IMPORTANT")), "<b>\$enabled = false;</b>")."<br/>";
	$user = new stdClass();
	$user->name = "Admin";
	$user->id = 1;
	$user->security_level = SEC_ADMIN;
	fs_start_user_session($user);
	fs_show_page(FS_ABS_PATH."/php/page-users.php");
}
?>
