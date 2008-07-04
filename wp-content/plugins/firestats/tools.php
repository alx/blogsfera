<?php
/**
 * This is a relativly clean hack to make files from some inner directory be relative to the root directory.
 */
$valid = false;
if (isset($_REQUEST['file_id']))
{
	$valid = true;
	switch($_REQUEST['file_id'])
	{
		case "import_bots":
			require_once(dirname(__FILE__)."/php/import-bots-list.php");
			break;
		case "system_test":
			require_once(dirname(__FILE__)."/php/tools/system_test.php");
			break;
		case "manage_users":
			require_once(dirname(__FILE__)."/php/tools/emergency-manage-users.php");
			break;
		case "reset_password":
			require_once(dirname(__FILE__)."/php/tools/reset-password.php");
			break;
		default:
			$valid = false;
		break;
	}
}

if (!$valid)
{
	require_once(dirname(__FILE__)."/php/init.php");
	require_once(FS_ABS_PATH."/php/html-utils.php");
	fs_show_page(FS_ABS_PATH."/php/tools-menu.php");
}
?>