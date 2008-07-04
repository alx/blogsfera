<?php
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-common.php');

function fs_rebuild_cache_calc_max()
{
	return 1;
}

function fs_rebuild_cache_desc($value)
{
	switch($value)
	{
		case 0:
			return fs_r("Recalculating matching bots");
		break;
		default:
			return "Unsupported step number : $value";
	}
}
	
function fs_rebuild_cache($value)
{
	switch($value)
	{
		case 0:
			$res = fs_recalculate_match_bots();
		break;
		default:
			return "Unsupported step number : $value";
	}
	
	if ($res !== true) 
	{
		return $res;
	}else 
		return 1;
}

function fs_recalculate_match_bots()
{
	$fsdb = &fs_get_db_conn();
	$useragents = fs_useragents_table();
	$bots = fs_bots_table();

	$res = $fsdb->get_results("SELECT ua.id id,count(wildcard) c
								FROM $bots RIGHT JOIN $useragents ua ON useragent 
								REGEXP wildcard GROUP BY useragent");
	if ($res === false) return $fsdb->last_error;
	if (count($res) > 0)
	{
		foreach($res as $r)
		{	
			$useragent_id = $r->id;
			$count = $r->c;
			if ($fsdb->query("UPDATE $useragents SET match_bots='$count' WHERE id='$useragent_id'") === false)
			{
				return $fsdb->last_error;
			}
		}
	}
	return true;
}
?>
