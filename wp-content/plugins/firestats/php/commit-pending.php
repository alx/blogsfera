<?php
define('FS_NO_SESSION', true);
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-hit.php');

if (!defined('FS_COMMIT_MAX_CHUNK_SIZE'))
{
	define('FS_COMMIT_MAX_CHUNK_SIZE', 1000);
}

/**
 * This flushed the hits from the pending table to the actual database.
 * this can be seriously optimized.
 */
$fsdb = &fs_get_db_conn();
$pending = fs_pending_date_table();
while(true)
{
	$sql = "SELECT COUNT(*) FROM `$pending`";
	$c = $fsdb->get_var($sql);
	if ($c === false)
	{
		die(fs_db_error());
	}
	
	if (((int)$c) === 0) break;
	
	$sql = "SELECT * FROM `$pending` LIMIT 0,".FS_COMMIT_MAX_CHUNK_SIZE;
	$res = $fsdb->get_results($sql);
	if ($res === false)
	{
		die(fs_db_error());
	}
	
	foreach ($res as $d)
	{
		$_SERVER['REMOTE_ADDR'] = $d->ip;
		$_SERVER['HTTP_USER_AGENT'] = $d->useragent;
		$_SERVER['REQUEST_URI'] = $d->url;
		$_SERVER['HTTP_REFERER'] = $d->referer;
		
		$res = fs_add_hit_immediate__($d->user_id, $d->site_id, $d->timestamp);
		if ($res !== true)
		{
			die("Error : ".$res);
		}
		
		$r = $fsdb->query("DELETE FROM `$pending` WHERE `id` = '$d->id'");
		if ($r === false) die(fs_db_error());
	}
}
?>