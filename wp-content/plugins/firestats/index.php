<?php
/*
 This file is the standalone entry point for FireStats
 Its not called when FireStats is installed inside WordPress or other systems
 */
require_once(dirname(__FILE__).'/php/init.php');
require_once(dirname(__FILE__).'/php/utils.php');
require_once(dirname(__FILE__).'/php/html-utils.php');
?>
<?php
if (fs_in_wordpress())
{
	// security check.
	// prevent uncontroled access to FireStats installed inside another system
	$msg = "<h3>".fs_r('Error')."</h3>".fs_r('Access denied');
	fs_show_page($msg, false, false);
	return;
}
require_once(FS_ABS_PATH.'/php/auth.php');
require_once(FS_ABS_PATH.'/php/db-common.php');

$db = fs_get_db_status();
if ($db['status'] != FS_DB_VALID)
{
	$show_db = false;
	switch($db['status'])
	{
		case FS_DB_NOT_INSTALLED:
		case FS_DB_NOT_CONFIGURED:
			$show_db = true;
		break;
		case FS_DB_NEED_UPGRADE:
			if ($db['status'] == FS_DB_NEED_UPGRADE && $db['ver'] < 11)
			{
				$show_db = true;
			}
		break;
	}
	if ($show_db)
	{
		fs_dummy_auth();
		fs_show_page(FS_ABS_PATH.'/php/page-database.php');
		return;
	}
	
	// any other case, procceed with normal login screen.
}
else
if (fs_no_admin())
{
	fs_dummy_auth();
	fs_show_page(FS_ABS_PATH.'/php/page-add-admin.php');
	return;
}

// to force login in DEMO mode, append ?login to the firestats url.
if (defined('DEMO') && !isset($_GET['login']))
{
	$user = new stdClass();
	$user->name = "Demo";
	$user->id = 1;
	$user->security_level = SEC_USER;
	$res = fs_start_user_session($user);
}
else
{
	$res = fs_resume_user_session();
}
// if authenticated or database is not yet configured propertly, show main page (that will show db configuration or admin user creation pages)
if ($res === true)
{
	fs_show_page('php/tabbed-pane.php');
	return;
}
else
{
	fs_show_page(FS_ABS_PATH.'/login.php', true, true, true);
}
?>
