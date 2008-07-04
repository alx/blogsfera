<?php
require_once(dirname(__FILE__).'/db-hit.php');
$site_id = 0;
if (!empty($GLOBALS['FS_SITE_ID']))
{
	$site_id = $GLOBALS['FS_SITE_ID'];
}
fs_add_site_hit($site_id);
?>
