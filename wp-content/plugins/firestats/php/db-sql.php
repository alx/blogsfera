<?php
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-common.php');
require_once(dirname(__FILE__).'/utils.php');
require_once(dirname(__FILE__).'/db-config-utils.php');

function fs_get_site_baseline_values($site_id)
{
	$archive_sites = fs_archive_sites();
	$fsdb = &fs_get_db_conn();
	$res = $fsdb->get_row("SELECT visits,views FROM `$archive_sites` WHERE `site_id` = '$site_id' AND `range_id` = '1'");
	if ($res === false)
	{
		$res = new stdClass();
		$res->visits = 0;
		$res->views = 0;
	}
	return $res;
}

function fs_get_num_old_days()
{
	$DAY = 60 * 60 * 24;
	$archive_older_than_days = fs_get_archive_older_than();
	$older_than = time() - $archive_older_than_days * $DAY;
	
	$hits = fs_hits_table();
	$sql = "SELECT DISTINCT SUBSTRING(timestamp,1,10) start, DATE_ADD(SUBSTRING(timestamp,1,10), INTERVAL 1 DAY) end FROM `$hits` WHERE timestamp < FROM_UNIXTIME('$older_than') ORDER BY `timestamp`";
	$fsdb = &fs_get_db_conn();
	$days = $fsdb->get_results($sql);
	if ($days === false) return fs_db_error(false);
	return count($days);
}

function fs_archive_old_data($older_than, $max_days_to_archive)
{
	$fp = fopen(__FILE__, "r");
	if (!$fp) return "Error opening lock file";
	if (flock($fp, LOCK_EX + LOCK_NB) == FALSE) return fs_r("Data compacting is already in progress");
	$res = fs_archive_old_data_impl($older_than, $max_days_to_archive);
	flock($fp, LOCK_UN);
	return $res;
}

function fs_archive_old_data_impl($older_than, $max_days_to_archive)
{
	
	if (!isset($max_days_to_archive) || $max_days_to_archive <= 0)
		return "Invalid max value : $max_days_to_archive";
	$hits = fs_hits_table();
	$ranges = fs_archive_ranges();
	$archive_sites = fs_archive_sites();
	$archive_pages = fs_archive_pages();
	$archive_referrers = fs_archive_referrers();			
	$archive_useragents = fs_archive_useragents();
	$archive_countries = fs_archive_countries();

	$supports_subquery = fs_mysql_newer_than("4.1.14"); // mysql bug http://bugs.mysql.com/bug.php?id=13385
	if (!$supports_subquery) return sprintf(fs_r("MySQL 4.1.14 or newer is required for data compacting support"));

	$fsdb = &fs_get_db_conn();
	
	// no need to archive excluded entries
	// its faster to purge them now than to consider them when archiving.
	fs_purge_excluded_entries($older_than);
	$sql = "SELECT DISTINCT SUBSTRING(timestamp,1,10) start, DATE_ADD(SUBSTRING(timestamp,1,10), INTERVAL 1 DAY) end FROM `$hits` WHERE timestamp < FROM_UNIXTIME('$older_than') ORDER BY `timestamp`";
	$days = $fsdb->get_results($sql);
	if ($days === false) return fs_db_error();
	$num_processed = 0;
	if (count($days) > 0)
	{
		foreach($days as $d)
		{
			if ($num_processed >= $max_days_to_archive) break;
			
			if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
			
			$start = $d->start." 00:00:00";
			$end = $d->end." 00:00:00";
			$sql = "INSERT IGNORE INTO `$ranges` ( `range_id` , `range_start` , `range_end` )	VALUES (NULL , '$start','$end')";
			$r = $fsdb->query($sql);
			if ($r === false) return fs_db_error(true);
			
			$r = $fsdb->get_var("SELECT LAST_INSERT_ID()");
			if ($r === false) return fs_db_error(true);
			
			$range_id = $r;
			// $range_id will be 0 if the range was already in the database.
			if ($range_id == "0")
			{
				$range_id = $fsdb->get_var("SELECT `range_id` FROM `$ranges` WHERE `range_start` = '$start' AND `range_end` = '$end'");
				if (!$range_id)  return fs_db_error(true);
			}
			
			// Sites archive - views
			$sql =  "INSERT INTO `$archive_sites` (`range_id`,`site_id`,`views`)".
						"SELECT '$range_id',`site_id`,COUNT(`ip`) ".
						"FROM `$hits`".
						"WHERE `timestamp` >= '$start' AND `timestamp` < '$end'".
						"GROUP BY `site_id` ".
					"ON DUPLICATE KEY UPDATE `views`=`views`+VALUES(`views`)";
			$r = $fsdb->query($sql);
			if ($r === false) return fs_db_error(true);

			// Sites archive - visits
			$sql =  "INSERT INTO `$archive_sites`(`range_id`,`site_id`,`visits`)".
						"SELECT '$range_id',`site_id`,COUNT(DISTINCT(`ip`)) ".
						"FROM `$hits` ".
						"WHERE `timestamp` >= '$start' AND `timestamp` < '$end'".
						"GROUP BY `site_id` ".
					"ON DUPLICATE KEY UPDATE `visits`=`visits`+VALUES(`visits`)";
			$r = $fsdb->query($sql);		
			if ($r === false) return fs_db_error(true);

			// pages archive - views
			$sql =  "INSERT INTO `$archive_pages` (`range_id`,`site_id`,`url_id`,`views`)".
						"SELECT '$range_id',`site_id`,`url_id`,COUNT(`ip`) ".
						"FROM `$hits` ".
						"WHERE `timestamp` >= '$start' AND `timestamp` < '$end'".
						"GROUP BY `site_id`,`url_id` ".
					"ON DUPLICATE KEY UPDATE `views`=`views`+VALUES(`views`)";
			$r = $fsdb->query($sql);
			if ($r === false) return fs_db_error(true);
			
			// pages archive - visits			
			$sql =  "INSERT INTO `$archive_pages`(`range_id`,`site_id`,`url_id`,`visits`)".
						"SELECT '$range_id',`site_id`,`url_id`, COUNT(DISTINCT(`ip`)) ".
						"FROM `$hits`".
						"WHERE `timestamp` >= '$start' AND `timestamp` < '$end'".
						"GROUP BY `site_id`,`url_id` ".
					"ON DUPLICATE KEY UPDATE `visits`=`visits`+VALUES(`visits`)";
	
			$r = $fsdb->query($sql);		
			if ($r === false) return fs_db_error(true);
			
			// referrers archive - views
			$sql =  "INSERT INTO `$archive_referrers` (`range_id`,`site_id`,`url_id`,`views`)".
						"SELECT '$range_id',`site_id`,`referer_id`,COUNT(`ip`) ".
						"FROM `$hits` ".
						"WHERE `timestamp` >= '$start' AND `timestamp` < '$end'".
						"GROUP BY `site_id`,`referer_id` ".
					"ON DUPLICATE KEY UPDATE `views`=`views`+VALUES(`views`)";
			$r = $fsdb->query($sql);
			if ($r === false) return fs_db_error(true);
			
			// referrers archive - visits			
			$sql =  "INSERT INTO `$archive_referrers`(`range_id`,`site_id`,`url_id`,`visits`)".
						"SELECT '$range_id',`site_id`,`referer_id`, COUNT(DISTINCT(`ip`)) ".
						"FROM `$hits`".
						"WHERE `timestamp` >= '$start' AND `timestamp` < '$end'".
						"GROUP BY `site_id`,`referer_id` ".
					"ON DUPLICATE KEY UPDATE `visits`=`visits`+VALUES(`visits`)";
					
			// useragents archive - views
			$sql =  "INSERT INTO `$archive_useragents` (`range_id`,`site_id`,`useragent_id`,`views`)".
						"SELECT '$range_id',`site_id`,`useragent_id`,COUNT(`ip`) ".
						"FROM `$hits` ".
						"WHERE `timestamp` >= '$start' AND `timestamp` < '$end'".
						"GROUP BY `site_id`,`useragent_id` ".
					"ON DUPLICATE KEY UPDATE `views`=`views`+VALUES(`views`)";
			$r = $fsdb->query($sql);
			if ($r === false) return fs_db_error(true);
			
			// useragents archive - visits			
			$sql =  "INSERT INTO `$archive_useragents`(`range_id`,`site_id`,`useragent_id`,`visits`)".
						"SELECT '$range_id',`site_id`,`useragent_id`, COUNT(DISTINCT(`ip`)) ".
						"FROM `$hits`".
						"WHERE `timestamp` >= '$start' AND `timestamp` < '$end'".
						"GROUP BY `site_id`,`useragent_id` ".
					"ON DUPLICATE KEY UPDATE `visits`=`visits`+VALUES(`visits`)";
			$r = $fsdb->query($sql);		
			if ($r === false) return fs_db_error(true);			
			
			// countries archive - views
			$sql =  "INSERT INTO `$archive_countries` (`range_id`,`site_id`,`country_code`,`views`)".
						"SELECT '$range_id',`site_id`,`country_code`,COUNT(`country_code`) ".
						"FROM `$hits` ".
						"WHERE `timestamp` >= '$start' AND `timestamp` < '$end'".
						"GROUP BY `site_id`,`country_code` ".
					"ON DUPLICATE KEY UPDATE `views`=`views`+VALUES(`views`)";
			$r = $fsdb->query($sql);
			if ($r === false) return fs_db_error(true);
			
			// countries archive - visits			
			$sql =  "INSERT INTO `$archive_countries`(`range_id`,`site_id`,`country_code`,`visits`)".
						"SELECT '$range_id',`site_id`,`country_code`, COUNT(DISTINCT(`ip`)) ".
						"FROM `$hits`".
						"WHERE `timestamp` >= '$start' AND `timestamp` < '$end'".
						"GROUP BY `site_id`,`country_code` ".
					"ON DUPLICATE KEY UPDATE `visits`=`visits`+VALUES(`visits`)";
			$r = $fsdb->query($sql);		
			if ($r === false) return fs_db_error(true);			
			
	
			if ($fsdb->query("DELETE FROM `$hits` WHERE `timestamp` >= '$start' AND `timestamp` < '$end'") === false)
				return fs_db_error(true);
			
			if ($fsdb->query("COMMIT") === false) return fs_db_error(true);
			$num_processed++;
		}
	}
	
	return $num_processed;
}

function fs_get_database_size()
{
	$fsdb = &fs_get_db_conn();
    $res = $fsdb->get_results( "SHOW TABLE STATUS");
    if ($res === false) return fs_db_error();
    $dbsize = 0;
    $tables = fs_get_tables_list();
    foreach($res as $table) 
   	{
   		if (in_array($table->Name, $tables))
   		{
	        $dbsize += $table->Data_length + $table->Index_length;
   		}
    }
    return $dbsize;
}

/**
 * $site_id = null, registers a new empty site and return its site id.
 * else registers a new site with the specified id.
 *
 * @return the new site id or false in case of error.
 */
function fs_register_site($site_id = null)
{
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$sid = $site_id === null ? "NULL" : $fsdb->escape($site_id); 
	$sql = "INSERT INTO `$sites` ( `id` , `type` , `name` ) VALUES   ($sid , '0', '')";
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		return false;
	}

	return $fsdb->insert_id;
}

/**
 * Creates a new site with the specified id.
 * if new_sid is 'auto', the site id is chosen automatically, otherwise the new_sid is used as the site id.
 *
 * @param $new_sid the site id to use, 'auto' to choose automatically.
 * @param $name site name
 * @param $type site type as defined in constants.php
 * @param $baseline_views the initial value for views for this site. (default 0)
 * @param $baseline_visitors the initial value for visitor for this site. (default 0)
 *  *  
 * @return true on success, or error message on failure.
 */

function fs_create_new_site($new_sid, $name, $type, $baseline_views = 0, $baseline_visitors = 0)
{
	if (empty($name)) return fs_r('Site name not specified');
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();

	if ($new_sid == 'auto')
	{
		$newSite = true;
		$new_sid = fs_register_site();
		if ($new_sid === false) return fs_db_error();
	}
	else
	{
		if (!is_numeric($new_sid) || (int)($new_sid) <= 0) return fs_r('Site ID must be a positive number');
		$exists = fs_site_exists($new_sid);
		if ($exists === null) return fs_db_error();
		if ($exists === true) return sprintf(fs_r("A site with the ID %s already exists"),$new_sid);
	}

	$new_sid = $fsdb->escape($new_sid);
	$type = $fsdb->escape($type);
	$name = $fsdb->escape($name);
	$sql = "REPLACE INTO `$sites` (`id`,`type`,`name`) VALUES ($new_sid,$type,$name)";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error();

	if (!is_numeric($baseline_views)) $baseline_views = 0;
	if (!is_numeric($baseline_visitors)) $baseline_visitors = 0;
	$baseline_views = $fsdb->escape($baseline_views);
	$baseline_visitors = $fsdb->escape($baseline_visitors);
	$archive_sites = fs_archive_sites();
	$sql = "REPLACE INTO  `$archive_sites` (`range_id`,`site_id`,`views`,`visits`) VALUES(1,$new_sid,$baseline_views,$baseline_visitors)";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error();
	return true;
}

function fs_update_site_params($new_sid,$orig_sid, $name,$type, $baseline_views = null, $baseline_visitors = null)
{
	if (empty($name)) return fs_r('Site name not specified');
	if (empty($orig_sid)) return "Uspecified site id";

	$changing_sid = $new_sid != $orig_sid;
	if (!is_numeric($new_sid) || (int)($new_sid) <= 0) return fs_r('Site ID must be a positive number');

	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();

	$exists = fs_site_exists($orig_sid);
	if ($exists === null) return fs_db_error();
	if ($exists === false) return sprintf(fs_r("No site with the id %s exists"),$new_sid);

	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();


	if ($changing_sid)
	{
		$exists = fs_site_exists($new_sid);
		if ($exists === null) return fs_db_error(true);
		if ($exists === true) 
		{
			$fsdb->query("ROLLBACK");
			return sprintf(fs_r("A site with the ID %s already exists"),$new_sid);
		}

		$r = fs_transfer_site_hits($orig_sid, $new_sid);
		if ($r === false) return fs_db_error(true);
	}

	$orig_sid = $fsdb->escape($orig_sid);
	$new_sid = $fsdb->escape($new_sid);
	$type = $fsdb->escape($type);
	$name = $fsdb->escape($name);

	$sql = "UPDATE `$sites` SET `type` = $type, `name` = $name, `id` = $new_sid WHERE `id` = $orig_sid";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error(true);

	if ($baseline_views !== null && $baseline_visitors !== null)
	{
		if (!is_numeric($baseline_views)) $baseline_views = 0;
		if (!is_numeric($baseline_visitors)) $baseline_visitors = 0;
		$baseline_views = $fsdb->escape($baseline_views);
		$baseline_visitors = $fsdb->escape($baseline_visitors);
		$archive_sites = fs_archive_sites();
		$sql = "REPLACE INTO  `$archive_sites` (`range_id`,`site_id`,`views`,`visits`) VALUES(1,$new_sid,$baseline_views,$baseline_visitors)";
		$r = $fsdb->query($sql);
		if ($r === false) return fs_db_error();
	}
	if($fsdb->query("COMMIT") === false) return fs_db_error(true);
	return true;
}

function fs_site_exists($site_id)
{
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$site_id = $fsdb->escape($site_id);
	$sql = "SELECT count(*) FROM `$sites` WHERE `id` = $site_id";
	$r = $fsdb->get_var($sql);
	if ($r === false)
	{
		return null;
	}
	return $r != "0";
}

function fs_delete_site($site_id, $action, $new_sid)
{
	if (empty($site_id)) return "Uspecified site id";
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$hits = fs_hits_table();
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();

	$exists = fs_site_exists($site_id);
	if ($exists === null) return fs_db_error(true);
	if ($exists === false) 
	{
		$fsdb->query("ROLLBACK");
		return sprintf(fs_r("No site with the id %s exists"),$site_id);
	}

	if ($action == "delete")
	{
		$id = $fsdb->escape($site_id);
		$sql = "DELETE FROM `$hits` WHERE site_id = $id";
		$r = $fsdb->query($sql);
		if ($r === false) return fs_db_error(true);
		
		$archives = array
		(
			fs_archive_sites(),
			fs_archive_pages(),
			fs_archive_referrers(),
			fs_archive_useragents(),
			fs_archive_countries()
		);
		
		foreach($archives as $archive)
		{
			$sql = "DELETE FROM `$archive` WHERE site_id = $id";
			$r = $fsdb->query($sql);
			if ($r === false) return fs_db_error(true);
		}
	}
	else
	if ($action == "change")
	{
		if (empty($new_sid)) 
		{
			$fsdb->query("ROLLBACK");
			return fs_r("New site_id must not be empty");
		}
		
		if ($site_id == $new_sid)
		{
			$fsdb->query("ROLLBACK");
			return fs_r("Can't move the hits to the same site");
		}
		

		$exists = fs_site_exists($new_sid);
		if ($exists === null) return fs_db_error(true);
		if ($exists === false) 
		{
			$fsdb->query("ROLLBACK");
			return sprintf(fs_r("No site with the id %s exists"),$new_sid);
		}
		$r = fs_transfer_site_hits($site_id, $new_sid);
		if ($r === false) return fs_db_error(true);
	}
	else
	{
		$fsdb->query("ROLLBACK");
		return "Unknown action $action";
	}
	$id = $fsdb->escape($site_id);
	$sql = "DELETE FROM `$sites` WHERE `id` = $id";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error(true);

	if($fsdb->query("COMMIT") === false) return fs_db_error(true);
	return true;
}

function fs_transfer_site_hits($old_sid, $new_sid)
{
	$fsdb = &fs_get_db_conn();
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
	
	$archive_sites = fs_archive_sites();
	$base_old= fs_get_site_baseline_values($old_sid);
	$base_new = fs_get_site_baseline_values($new_sid);
	$visits = $base_new->visits + $base_old->visits;
	$views = $base_new->views + $base_old->views;
	$sql = "DELETE FROM `$archive_sites` WHERE `range_id` = '1' AND `site_id` = '$old_sid'";
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		$fsdb->query("ROLLBACK");
		return false;
	}
	$sql = "REPLACE INTO `$archive_sites` (`range_id`,`site_id`,`views`,`visits`) VALUES(1,$new_sid,$views,$visits)";
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		$fsdb->query("ROLLBACK");
		return false;
	}
	
	$archives = array();
	$archives[] = fs_hits_table();
	$archives[] = fs_archive_sites();
	$archives[] = fs_archive_pages();
	$archives[] = fs_archive_referrers();
	$archives[] = fs_archive_useragents();
	$archives[] = fs_archive_countries();
	foreach($archives as $archive)
	{
		$sql = "UPDATE `$archive` SET `site_id` = '$new_sid' WHERE `site_id` = $old_sid";
		$r = $fsdb->query($sql);
		if ($r === false)
		{
			$fsdb->query("ROLLBACK");
			return false;
		}
	}
	$fsdb->query("COMMIT");
	return true;
}

function fs_get_orphan_site_ids()
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$sites = fs_sites_table();
	$sql = "SELECT DISTINCT `site_id` AS `id` FROM `$hits` WHERE `site_id` NOT IN (SELECT `id` FROM `$sites`)";
	return $fsdb->get_results($sql,ARRAY_A);
}

// adds an ip address to exclude.
// returns an error message, or an empty string if okay.
function fs_add_excluded_ip($ip)
{
	if (!fs_is_admin()) return "Access denied : fs_add_excluded_ip";
	$fsdb = &fs_get_db_conn();
	$v = ip2long($ip);
	if ($v == false || $v == -1)
	{
		return sprintf(fs_r("Invalid IP address: %s"),$ip);
	}
	else
	{
		$ip = $fsdb->escape($ip);
		$ips = fs_excluded_ips_table();
		if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
		$sql = "SELECT DISTINCT ip FROM `$ips` WHERE `ip` LIKE $ip";
		$r = $fsdb->get_var($sql);
		if($r === false) return fs_db_error(true);
		if ($r != null)
		{
			$fsdb->query("ROLLBACK");
			return sprintf(fs_r("The IP address %s is already in the database"),$ip);
		}
		$sql = "INSERT INTO `$ips` (`id`, `ip`) VALUES (NULL, $ip)";
		if($fsdb->query($sql) === false)
		{
			return fs_db_error(true);
		}
		else
		{
			$hits = fs_hits_table();
			$sql = "UPDATE `$hits` SET `excluded_ip`='1' WHERE `ip`=$ip";
			if($fsdb->query($sql)===false) return fs_db_error(true);
			if($fsdb->query("COMMIT") === false) return fs_db_error(true);
			return "";
		}
	}
}

function fs_remove_excluded_ip($ip)
{
	if (!fs_is_admin()) return "Access denied : fs_remove_excluded_ip";

	$fsdb = &fs_get_db_conn();

	// this is a bit nasty, but it let us return a resonable error when the stupid user try to remove the 'empty' string.
	$v = ip2long($ip);
	if ($v == false || $v == -1)
	return sprintf(fs_r("Invalid IP address: %s"),$ip);

	$exip = fs_excluded_ips_table();
	$hits = fs_hits_table();
	$ip = $fsdb->escape($ip);

	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();

	if($fsdb->query("DELETE from `$exip` WHERE ip = $ip") === false)
	{
		return fs_db_error(true);
	}
	else
	{
		if ($fsdb->query("UPDATE `$hits` SET `excluded_ip`='0' WHERE `ip`=$ip") === false) return fs_db_error(true);
		if ($fsdb->query("COMMIT") === false) return fs_db_error(true);
		return "";
	}

}


function fs_add_bot($wildcard1, $fail_if_exists = true)
{
	if (!fs_is_admin()) return "Access denied : fs_add_bot";

	$fsdb = &fs_get_db_conn();
	$wildcard = $fsdb->escape(trim($wildcard1));
	$bots_table = fs_bots_table();
	$hits_table = fs_hits_table();
	$ua_table = fs_useragents_table();

	// check for duplicate wildcard
	if ($fsdb->get_var("SELECT DISTINCT wildcard FROM `$bots_table` WHERE `wildcard` = $wildcard") != null)
	{
		if ($fail_if_exists) return sprintf(fs_r("The bot wildcard %s is already in the database"),$wildcard);
		else return "";
	}

	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
	// insert wildcard to table
	if ($fsdb->query("INSERT INTO `$bots_table` (`wildcard`) VALUES ($wildcard)") === false)
	{
		return fs_db_error(true);
	}
	else
	{
		$search_wildcard = $fsdb->escape(trim($wildcard1));
		if ($fsdb->query("UPDATE `$ua_table`
			SET match_bots=match_bots+1 
			WHERE useragent REGEXP $search_wildcard") === false)
		{
			return fs_db_error(true);
		}
		if ($fsdb->query("COMMIT") === false) return fs_db_error(true);
		return "";
	}
}

function fs_remove_bot($bot_id)
{
	if (!fs_is_admin()) return "Access denied : fs_remove_bot";

	$fsdb = &fs_get_db_conn();
	$bot_id = $fsdb->escape($bot_id);
	$bots_table = fs_bots_table();
	$ua_table = fs_useragents_table();
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();

	$wildcard = $fsdb->get_var("SELECT `wildcard` FROM `$bots_table` WHERE `id`='$bot_id'");
	if ($wildcard === false) return fs_db_error(true);
	$wildcard = $fsdb->escape($wildcard);
	if ($fsdb->query("UPDATE `$ua_table`  SET match_bots=match_bots-1 WHERE useragent REGEXP $wildcard") === false)
	{
		return fs_db_error(true);
	}

	if ($fsdb->query("DELETE from `$bots_table` WHERE `id` = '$bot_id'") === false) return fs_db_error(true);
	if ($fsdb->query("COMMIT") === false) return fs_db_error(true);
	return "";
}

function fs_clear_bots_list()
{
	$res = fs_get_bots();
	if ($res)
	{
		foreach($res as $r)
		{
			$id = $r['id'];
			$res1 = fs_remove_bot($id);
			if ($res1 != '') return $res1;
		}
	}
	return '';
}

function fs_get_unique_hit_count($days_ago = NULL, $site_id = true, $url_id = null)
{
	return fs_get_unique_hit_count_range($site_id, true,fs_days_ago_to_unix_time($days_ago), null, $url_id);
}

/**
 * returns the number of unique hits in the specified time range
 * range is half inclusive : [). 
 *
 * $site_id site id to work on, or false for all sites, or true for current site in options table. (see not_excluded() doc).
 * $is_unix_time true if the start and end times are unix time, false for mysql datetime
 * $start_time timestamp of start time
 * $end_time timestamp of end time.
 * $url_id if specified, the value returned will be for the url with this id only.
 * returns number of unique hits.
 */
function fs_get_unique_hit_count_range($site_id, $is_unix_time, $start_time, $end_time, $url_id)
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$ua = fs_useragents_table();
	$not_excluded = not_excluded($site_id);
	$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
	$and_url_id = $url_id ? " AND `url_id` = '$url_id'" : "";	
	
	if (fs_mysql_newer_than("4.1.0")) 
	{
		$select = "SELECT COUNT(DISTINCT(ip)) c
					FROM `$hits` h,`$ua` u
					WHERE  h.useragent_id=u.id AND $not_excluded AND $timestamp_between $and_url_id 
					GROUP BY SUBSTRING(`timestamp`,1,10),site_id";
		
		$sql = "SELECT SUM(u.c) c FROM ($select) u";
		$res = $fsdb->get_var($sql);
		if ($res === false) return fs_db_error();
		$non_archive_count = $res;
	}
	else
	{
		$select = "SELECT COUNT(ip) c
					FROM `$hits` h,`$ua` u
					WHERE  h.useragent_id=u.id AND $not_excluded AND $timestamp_between $and_url_id 
					GROUP BY SUBSTRING(`timestamp`,1,10),site_id,ip";
		$res = $fsdb->get_results($select);
		if ($res === false) return fs_db_error();
		$non_archive_count = count($res);
	}

	$r = fs_get_unique_hit_count_range_from_archive($site_id, $is_unix_time, $start_time, $end_time, $url_id);
	if ($r === false) return fs_db_error();
	
	return $r + $non_archive_count;
}

function fs_get_hit_count($days_ago = null, $site_id = true, $url_id = null)
{
	return fs_get_page_views_range($site_id,true,fs_days_ago_to_unix_time($days_ago), null, $url_id);
}

/**
 * returns the number of page views in the specified time range
 * range is half inclusive : [). 
 *
 * $site_id site id to work on, or false for all sites, or true for current site in options table. (see not_excluded() doc).
 * $is_unix_time true if the start and end times are unix time, false for mysql datetime
 * $start_time timestamp of start time
 * $end_time timestamp of end time
 * $url_id if specified, the value returned will be for the url with this id only.
 * returns number of unique hits.
 */
function fs_get_page_views_range($site_id, $is_unix_time, $start_time, $end_time, $url_id)
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$ua = fs_useragents_table();
	$not_excluded = not_excluded($site_id);
	
	$ts_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
	$sql = "SELECT COUNT(*) FROM `$hits` h,`$ua` u WHERE h.useragent_id=u.id AND $not_excluded AND $ts_between";
	
	if($url_id)
	{
		$sql .= " AND `url_id` = '$url_id'";	
	}
	$non_archive_count = $fsdb->get_var($sql);
	if ($non_archive_count === false) return fs_db_error();
	
	$r = fs_get_page_views_range_from_archive($site_id, $is_unix_time, $start_time, $end_time, $url_id);
	if ($r === false) return fs_db_error();
	return $r + $non_archive_count;
}

function fs_get_page_views_range_from_archive($site_id, $is_unix_time, $start_time, $end_time, $url_id)
{
	if ($url_id == null)
	{
		$table = fs_archive_sites();
	}
	else
	{
		$table = fs_archive_pages();
	}
	return fs_get_data_count_from_archive($table, "views",$site_id, $is_unix_time, $start_time, $end_time, $url_id);
}

function fs_get_unique_hit_count_range_from_archive($site_id, $is_unix_time, $start_time, $end_time, $url_id)
{	
	if ($url_id == null)
	{
		$table = fs_archive_sites();
	}
	else
	{
		$table = fs_archive_pages();
	}
	return fs_get_data_count_from_archive($table,"visits",$site_id, $is_unix_time, $start_time, $end_time, $url_id);
}

function fs_get_data_count_from_archive($table_name, $row_name, $site_id, $is_unix_time, $start_time, $end_time, $url_id)
{
 	$fsdb = &fs_get_db_conn();
 	$ranges = fs_archive_ranges();
 	$from_site = fs_get_site_id_query($site_id);
 	$range_between = fs_time_range_between($is_unix_time, $start_time, $end_time);
	$sql = "SELECT SUM(`$row_name`) FROM `$ranges` r,`$table_name` d WHERE d.range_id=r.range_id AND $from_site AND $range_between";
	
	if($url_id)
	{
		$sql .= " AND `url_id` = '$url_id'";	
	}
	return $fsdb->get_var($sql);
}

function fs_get_num_excluded()
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$ua = fs_useragents_table();
	$not_excluded = not_excluded(false);
	$sql = "SELECT COUNT(ip) FROM `$hits` h,`$ua` u WHERE h.useragent_id=u.id AND NOT ($not_excluded)";
	$res = $fsdb->get_var($sql);
	if ($res === false) return fs_db_error();
	return $res;
}

function fs_not_filtered()
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$arr = array();
	$arr['firestats_ht_ip_filter'] 	= 'ip';
	$arr['firestats_ht_url_filter'] 	= "urls.url";
	$arr['firestats_ht_referrer_filter'] 	= 'referers.url';
	$arr['firestats_ht_useragent_filter'] = 'useragent';
	$res = "";
	foreach($arr as $k=>$v)
	{
		$param = fs_get_option($k);
		if (!empty($param))
		{
			$param = $fsdb->escape($param);
			$cond = "$v REGEXP $param";
			if ($res == "") $res = $cond;
			else
			$res .= " AND $cond";
		}
	}

	if ($res == "")
	$res = "1";

	return $res;
}

/**
 * returns a query string to match currently not excluded hits.
 * $exclude_by_site : 
 * 	true to exclude all sites but the one in the sites_filter option.
 * 	false to include all sites.
 * 	a specific number to exclude all other sites (number is site id to include).
 */
function not_excluded($exclude_by_site = true, $site_table_name = null)
{
	$site_ex = fs_get_site_id_query($exclude_by_site, $site_table_name);
	return "`excluded_ip` = '0'
			AND `excluded_by_user` = '0' 
			AND `excluded_user` = '0' 
			AND `match_bots`='0'
			AND $site_ex";
}

function fs_get_site_id_query($site_id,$site_table_name = null)
{
	$sql = "";
	if (is_numeric($site_id))
	{
		$sql = "`site_id` = '$site_id'";
	}
	else
	{
		if ($site_id)
		{
			$site = fs_get_local_option('firestats_sites_filter','all');
			if ($site != 'all')
			{
				if ($site_table_name == null)
				{
					$sql = "`site_id` = '$site'";
				}
				else
				{
					$sql = "$site_table_name.`site_id` = '$site'";
				}
			}
		}
	}
	return $sql != "" ? $sql : "'1'";
}

function fs_purge_excluded_entries($older_than = null)
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$ua = fs_useragents_table();
	$not_excluded = not_excluded(false);
	$sql = "DELETE `$hits` FROM `$hits` ,`$ua` u WHERE $hits.useragent_id=u.id AND NOT ($not_excluded)";
	if ($older_than)
	{
		$sql .= " AND `timestamp` < FROM_UNIXTIME('$older_than')";
	}
	$res = $fsdb->get_var($sql);
	return $res;
}

# Fetches entries in DB
function fs_getentries()
{
	$amount = fs_get_num_hits_in_table();
	$timezone = fs_get_option('firestats_user_timezone','system');
	$db_support_tz = (ver_comp("4.1.3",fs_mysql_version()) <= 0);

	$ts = $db_support_tz && $timezone != 'system' ? "CONVERT_TZ(`timestamp`,'system','$timezone')" : "timestamp";
	if ($amount === false) return false;

	$hits = fs_hits_table();
	$ua = fs_useragents_table();
	$urls = fs_urls_table();
	$not_excluded = not_excluded(true, 'hits');
	$not_filtered = fs_not_filtered();
	$sql = "SELECT hits.id,ip,useragent,referers.url as referer,referers.search_terms,urls.url as url,$ts as timestamp,country_code,urls.title as url_title, referers.title as referrer_title
					FROM `$hits` AS hits,`$ua` AS agents,`$urls` AS urls,`$urls` AS referers
					WHERE 
						hits.useragent_id = agents.id AND 
						hits.url_id = urls.id AND 
						hits.referer_id = referers.id 
						AND $not_excluded 
						AND $not_filtered
					ORDER BY timestamp DESC";
	$sql .= " LIMIT $amount";

	$fsdb = &fs_get_db_conn();
	return $fsdb->get_results($sql);
}

function fs_get_excluded_ips()
{
	$fsdb = &fs_get_db_conn();
	return $fsdb->get_results("SELECT ip from ".fs_excluded_ips_table(), ARRAY_A);
}

function fs_get_bots()
{
	$fsdb = &fs_get_db_conn();
	return $fsdb->get_results("SELECT id,wildcard from ".fs_bots_table(). " ORDER BY wildcard", ARRAY_A);
}

function fs_ensure_initialized(&$x)
{
	if (!isset($x)) $x = 0;
}

function fs_group_others($list)
{
	$MIN = 2;
	$others = array();
	$others['name'] = 'Others'; // not translated, cause tree layout problems with hebrew
	$others['image'] = fs_pri_get_image_url('others', 'Others');
	$others['count'] = 0;
	$others['percent'] = 0;
	foreach ($list as $code=>$data)
	{
		if ($data['percent'] < 2)
		{
			$others['count'] += $data['count'];
			$others['percent'] += $data['percent'];
			$others['sublist'][$code]=$data;
			unset($list[$code]);
		}
	}
	if ($others['count'] > 0)
	{
		$list['others'] = $others;
	}
	return $list;
}

function fs_get_useragents_count($days_ago = null, $site_id = true)
{
	return fs_get_useragent_views_range(true, $site_id,fs_days_ago_to_unix_time($days_ago), null, null);
}

/**
 * returns a table mapping useragent_id to the number of times it viewed the specified site_id in the specified time range.
 * range is half inclusive : [). 
 *
 * $site_id site id to work on, or false for all sites, or true for current site in options table. (see not_excluded() doc).
 * $is_unix_time true if the start and end times are unix time, false for mysql datetime
 * $start_time timestamp of start time
 * $end_time timestamp of end time
 * returns number of unique hits.
 */
function fs_get_useragent_views_range($site_id, $is_unix_time, $start_time, $end_time)
{
	$hits = fs_hits_table();
	$useragents = fs_useragents_table();
	$archive_useragents = fs_archive_useragents();
	$ranges = fs_archive_ranges();
	$not_excluded = not_excluded($site_id, 'h');
	$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
	
	if (fs_mysql_newer_than("4.1.14")) 
	{
		$from_site = fs_get_site_id_query($site_id);
		$select1 = "SELECT DISTINCT(`useragent_id`),COUNT(`useragent_id`) AS `c`
					FROM `$hits` h, `$useragents` u 
					WHERE h.useragent_id = u.id AND $not_excluded AND $timestamp_between 
					GROUP BY `useragent_id`";
		
		$range_between = fs_time_range_between($is_unix_time, $start_time, $end_time);
		$select2 = "SELECT `useragent_id`,`views` AS `c`
					FROM `$archive_useragents` u,`$ranges` r
					WHERE r.range_id = u.range_id AND $range_between AND $from_site";
							
		$sql = "SELECT `useragent`,`useragent_id`, SUM(`c`) `c`
				FROM ($select1 UNION ALL $select2) `u`,`$useragents` `u2` 
				WHERE u2.id = u.useragent_id
				GROUP BY `useragent_id` 
				ORDER BY `c` DESC";
	}
	else
	{
		$sql = "SELECT DISTINCT(`useragent_id`),`useragent`,COUNT(`useragent_id`) AS `c`
				FROM `$hits` h, `$useragents` u 
				WHERE h.useragent_id = u.id AND $not_excluded AND $timestamp_between GROUP BY `useragent_id`";
	}
	$fsdb = &fs_get_db_conn();
	$results = $fsdb->get_results($sql,ARRAY_A);
	if ($results === false) return false;
	return $results;	
}

function fs_get_site($id)
{
	if (!fs_is_admin()) return "Access denied : fs_get_site";
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$id = $fsdb->escape($id);
	$sql = "SELECT * FROM $sites WHERE id=$id";
	return $fsdb->get_row($sql);
}

function fs_get_sites()
{
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$sql = "SELECT * FROM $sites";
	return $fsdb->get_results($sql,ARRAY_A);
}

function fs_get_users()
{
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$sql = "SELECT `id`,`username`,`email`,`security_level` FROM $users ORDER BY `id`";
	return $fsdb->get_results($sql);
}

function fs_get_user($id)
{
	$fsdb = &fs_get_db_conn();
	$id = $fsdb->escape($id);
	$users = fs_users_table();
	$sql = "SELECT `id`,`username`,`email`,`security_level` FROM $users WHERE `id` = $id";
	return $fsdb->get_row($sql);
}



function fs_stats_sort($stats_data)
{
	$foo = create_function('$a, $b', 'return $b["count"] - $a["count"];');
	uasort($stats_data,$foo);
	$size=count($stats_data);

	foreach($stats_data as $key=>$value)
	{
		$ar = $value['sublist'];
		if ($ar != NULL)
		{
			uasort($ar, $foo);
		}
	}
	return $stats_data;
}

// TODO:
// use classes here for the tree model. this is getting silly.
function fs_get_os_statistics($days_ago = NULL)
{
	$results = fs_get_useragents_count($days_ago);
	if ($results !== false && count($results) > 1)
	{
		$total = 0;
		foreach ($results as $r)
		{
			$total += $r['c'];
		}

		foreach ($results as $r)
		{
			$ua = $r['useragent'];
			$count = $r['c'];

			$a = fs_pri_detect_browser($ua);
			$os_name 	= $a[3];$os_code 	= $a[4];$os_ver		= $a[5];

			$os_img = fs_pri_get_image_url($os_code != '' ? $os_code : 'unknown', $os_name);

			fs_ensure_initialized($os[$os_code]['count']);
			fs_ensure_initialized($os[$os_code]['sublist'][$os_ver]['count']);

			// operating systems
			$os[$os_code]['name']=$os_name != '' ? $os_name : fs_r('Unknown');
			$os[$os_code]['image']=$os_img;
			$os[$os_code]['count'] += (int)$count;
			$os_total = $os[$os_code]['count'];
			$os[$os_code]['percent'] = (float)($os_total / $total) * 100;
			$os[$os_code]['sublist'][$os_ver]['count'] += (int)$count;
			$os_ver_count = $os[$os_code]['sublist'][$os_ver]['count'];
			$os[$os_code]['sublist'][$os_ver]['percent'] = (float)($os_ver_count / $total) * 100;
			$os[$os_code]['sublist'][$os_ver]['useragent'] = $ua;
			$os[$os_code]['sublist'][$os_ver]['name'] = $os_name;
			$os[$os_code]['sublist'][$os_ver]['image'] = $os_img;
		}
		return fs_stats_sort(fs_group_others($os));
	}
	else
	{
		return null;
	}
}

// TODO:
// use classes here for the tree model. this is getting silly.
function fs_get_browser_statistics($days_ago = NULL)
{
	$results = fs_get_useragents_count($days_ago);
	if ($results !== false && count($results) > 1)
	{
		$total = 0;
		foreach ($results as $r)
		{
			$total += $r['c'];
		}

		foreach ($results as $r)
		{
			$ua = $r['useragent'];
			$count = $r['c'];

			$a = fs_pri_detect_browser($ua);
			$br_name 	= $a[0];$br_code 	= $a[1];$br_ver		= $a[2];

			$br_img = fs_pri_get_image_url($br_code != '' ? $br_code : 'unknown', $br_name);

			fs_ensure_initialized($br[$br_code]['count']);
			fs_ensure_initialized($br[$br_code]['sublist'][$br_ver]['count']);

			$br[$br_code]['name'] = $br_name != '' ? $br_name : fs_r('Unknown');
			$br[$br_code]['image'] = $br_img;

			// browsers
			$br[$br_code]['count'] += (int)$count;
			$browser_total = $br[$br_code]['count'];
			$br[$br_code]['percent'] = (float)($browser_total / $total) * 100;
			$br[$br_code]['sublist'][$br_ver]['count'] += (int)$count;
			$br_ver_count = $br[$br_code]['sublist'][$br_ver]['count'];
			$br[$br_code]['sublist'][$br_ver]['percent'] = (float)($br_ver_count / $total) * 100;
			$br[$br_code]['sublist'][$br_ver]['useragent'] = $ua;
			$br[$br_code]['sublist'][$br_ver]['name'] = $br_name;
			$br[$br_code]['sublist'][$br_ver]['image'] = $br_img;
		}

		return fs_stats_sort(fs_group_others($br));
	}
	else
	{
		return null;
	}
}

function fs_save_excluded_users($list)
{
	if (!fs_is_admin()) return "Access denied : fs_save_excluded_users";

	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	if($fsdb->query("START TRANSACTION") === false) return fs_db_error();
	$sql = "UPDATE `$hits` SET `excluded_user`=IF(`user_id` IS NOT NULL AND `user_id` in (".($list ? $list : 'NULL')."),'1','0')";
	if($fsdb->query($sql) === false) return fs_db_error(true);
	if(fs_update_local_option('firestats_excluded_users', $list) === false) return fs_db_error(true);
	if($fsdb->query("COMMIT") === false) return fs_db_error(true);
}

function fs_get_recent_search_terms($num_limit, $days_ago = null,$search_term = null)
{
	return fs_get_recent_search_terms_range($num_limit, true, fs_days_ago_to_unix_time($days_ago), null, true, ORDER_BY_HIGH_COUNT_FIRST,$search_term);
}


function fs_get_recent_search_terms_range($num_limit, $is_unix_time, $start_time, $end_time, $site_id, $order_by = ORDER_BY_HIGH_COUNT_FIRST, $search_terms = null)
{
	$fsdb = &fs_get_db_conn();
	$num_limit = $fsdb->escape($num_limit);
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$ua = fs_useragents_table();
	$ranges = fs_archive_ranges();
	$archive_referrers = fs_archive_referrers();
	$order_by_str = '';
	switch($order_by)
	{
		case ORDER_BY_HIGH_COUNT_FIRST:
			$order_by_str = "ORDER BY `c` DESC,`referer_id`";
		break;
		case ORDER_BY_RECENT_FIRST:
			$order_by_str = "ORDER BY `ts` DESC,`referer_id`";
		break;	
	}
	
	if (fs_mysql_newer_than("4.1.14")) 
	{
		$not_excluded = not_excluded($site_id);
		$get_timestamp = $order_by == ORDER_BY_RECENT_FIRST ? ",MAX(SUBSTRING(timestamp,1,10)) `ts`" : "";
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
		$select1 = "SELECT `site_id`,`referer_id`,COUNT(`referer_id`) `c` $get_timestamp 
					FROM `$hits` h,`$ua` ua 
					WHERE h.useragent_id = ua.id AND $not_excluded AND $timestamp_between
					GROUP BY `referer_id`";
		
		$get_timestamp = $order_by == ORDER_BY_RECENT_FIRST ? ",SUBSTRING(`range_start`,1,10) AS `ts`" : "";
		$range_between = fs_time_range_between($is_unix_time, $start_time, $end_time);
		$from_site = fs_get_site_id_query($site_id);
		$select2 = "SELECT `site_id`,`url_id` AS `referer_id`, SUM(`views`) `c` $get_timestamp 
					FROM `$archive_referrers` `d`,`$ranges` `r` 
					WHERE d.range_id = r.range_id AND $range_between AND $from_site
					GROUP BY `url_id`";

		$limit = $num_limit ? " LIMIT $num_limit" : "";
		
		$get_timestamp = $order_by == ORDER_BY_RECENT_FIRST ? ",`ts`" : "";
		
		/**
		 * first level
		 */
		if ($search_terms == null) 
		{
			$sql = "SELECT u.`site_id`,`search_terms` ,SUM(u.c) `c` $get_timestamp ,`search_engine_id`,url as referer,COUNT(DISTINCT(`search_engine_id`)) `num_engines`
					FROM ($select1 UNION ALL $select2) `u`,`$urls` `u2` 
					WHERE u2.id = u.referer_id AND `search_engine_id` IS NOT NULL AND `search_terms` IS NOT NULL 
					GROUP BY `search_terms` 
					$order_by_str $limit";
		}
		else // second level
		{
			$sql = "SELECT u2.`site_id`,`search_terms` ,SUM(u.c) `c` $get_timestamp ,url as referer,`search_engine_id`
					FROM ($select1 UNION ALL $select2) `u`,`$urls` `u2` 
					WHERE u2.id = u.referer_id AND `search_engine_id` IS NOT NULL AND `search_terms` = '$search_terms' 
					GROUP BY `search_engine_id` 
					$order_by_str $limit";
		}
		return $fsdb->get_results($sql);
	}
	else // mysql < 4.1.14
	{
		$get_timestamp = $order_by == ORDER_BY_RECENT_FIRST ? ",MAX(SUBSTRING(timestamp,1,10)) `ts`" : "";
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
		$limit = $num_limit ? " LIMIT $num_limit" : "";
		$not_excluded = not_excluded($site_id, "h");
		/**
		 * first level
		 */
		if ($search_terms == null) 
		{
			$sql = "SELECT r.`site_id`,`search_terms`, count(`search_terms`) `c`,search_engine_id,url as referer,COUNT(DISTINCT(`search_engine_id`)) `num_engines` $get_timestamp
							FROM `$hits` h,`$ua` ua,`$urls` r 
							WHERE h.referer_id = r.id AND h.useragent_id = ua.id AND `search_engine_id` IS NOT NULL 
 							AND `search_terms` IS NOT NULL
							AND $not_excluded AND url != 'unknown' AND $timestamp_between 
					GROUP BY `search_terms` $order_by_str $limit";
		}
		else // second level
		{
			$sql = "SELECT r.`site_id`,`search_terms`, count(`search_terms`) `c` $get_timestamp ,`url` as referer,`search_engine_id`
							FROM `$hits` h,`$ua` ua,`$urls` r 
							WHERE h.referer_id = r.id AND h.useragent_id = ua.id AND `search_engine_id` IS NOT NULL 
							AND $not_excluded AND url != 'unknown' AND $timestamp_between  AND `search_terms` = '$search_terms'
					GROUP BY `search_engine_id` $order_by_str $limit";
		}
		return $fsdb->get_results($sql);
	}	
}

function fs_get_recent_referers($num_limit, $days_ago = null, $order_by = ORDER_BY_FIRST_SEEN)
{
	return fs_get_recent_referers_range($num_limit, true, fs_days_ago_to_unix_time($days_ago), null, true, $order_by);
}  

function fs_get_recent_referers_range($num_limit, $is_unix_time, $start_time, $end_time, $site_id, $order_by = ORDER_BY_FIRST_SEEN, $exclude_internal = true)
{
	$fsdb = &fs_get_db_conn();
	$num_limit = $fsdb->escape($num_limit);
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$ua = fs_useragents_table();
	$ranges = fs_archive_ranges();
	$archive_referrers = fs_archive_referrers();
	$order_by_str = '';
	switch($order_by)
	{
		case ORDER_BY_HIGH_COUNT_FIRST:
			$order_by_str = "ORDER BY `refcount` DESC";
		break;
		case ORDER_BY_RECENT_FIRST:
			$order_by_str = "ORDER BY `ts` DESC,`referer_id`";
		break;	
		case ORDER_BY_FIRST_SEEN:
			$order_by_str = "ORDER BY `add_time` DESC";
		break;	
	}
	
	$and_exclude_internal = $exclude_internal? "AND u2.site_id IS NULL" : "";
	$not_excluded = not_excluded($site_id, 'h');
	if (fs_mysql_newer_than("4.1.14")) 
	{
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
		$select1 = "SELECT `site_id`,`referer_id`,COUNT(`referer_id`) `c`,MAX(SUBSTRING(timestamp,1,10)) `ts` 
					FROM `$hits` h,`$ua` ua 
					WHERE h.useragent_id = ua.id AND $not_excluded AND $timestamp_between
					GROUP BY `referer_id`";
		
		$range_between = fs_time_range_between($is_unix_time, $start_time, $end_time);
		$from_site = fs_get_site_id_query($site_id);
		$select2 = "SELECT `site_id`,`url_id` AS `referer_id`, SUM(`views`) `c`,SUBSTRING(`range_start`,1,10) AS `ts` 
					FROM `$archive_referrers` `d`,`$ranges` `r` 
					WHERE d.range_id = r.range_id AND $range_between AND $from_site
					GROUP BY `url_id`";

		$limit = $num_limit ? " LIMIT $num_limit" : "";
		
		$sql = "SELECT u2.`add_time`,`url` ,SUM(`c`) `refcount`,`ts`,u2.title
				FROM ($select1 UNION ALL $select2) `u`,`$urls` `u2` 
				WHERE u2.id = u.referer_id AND `url` != '' AND `url` != 'unknown' $and_exclude_internal
				AND u2.search_engine_id IS NULL
				GROUP BY `referer_id` 
				$order_by_str $limit";
		return $fsdb->get_results($sql);
	}
	else
	{
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
		$sql = "SELECT u2.add_time,`url`,count(url) `refcount`,MAX(SUBSTRING(timestamp,1,10)) `ts`,u2.title
						FROM `$hits` h,`$ua` ua,`$urls` u2 
						WHERE h.referer_id = u2.id AND h.useragent_id = ua.id
						AND $not_excluded AND url != 'unknown' AND url != '' 
						AND $timestamp_between $and_exclude_internal
						AND u2.search_engine_id IS NULL";
		$sql .= " GROUP BY url $order_by_str".($num_limit ? " LIMIT $num_limit" : "");
		return $fsdb->get_results($sql);
	}
}

function fs_get_popular_pages($num_limit, $days_ago, $site_id, $type = null)
{
	return fs_get_popular_pages_range($num_limit, true, fs_days_ago_to_unix_time($days_ago), null, $site_id, $type);
}

function fs_get_popular_pages_range($num_limit, $is_unix_time, $start_time, $end_time,$site_id, $type = null)
{
	$fsdb = &fs_get_db_conn();
	$num_limit = $fsdb->escape($num_limit);
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$url_metadata = fs_url_metadata_table();
	$ua = fs_useragents_table();
	$ranges = fs_archive_ranges();
	$archive_pages = fs_archive_pages();
	$not_excluded = not_excluded($site_id, 'h');
	$limit = ($num_limit ? " LIMIT $num_limit" : "");
	
	if (fs_mysql_newer_than("4.1.14"))
	{
	    $select1 = "SELECT `site_id` , `url_id` as uid, count( `url_id` ) `cc` 
	    			FROM `$hits` h, `$ua` ua 
	    			WHERE h.useragent_id = ua.id AND $not_excluded AND ".fs_timestamp_between($is_unix_time, $start_time, $end_time)."
	    			GROUP BY `site_id` , `url_id`";
		
		$select2 = "SELECT `site_id` , `url_id` as uid, `views` AS cc FROM `$archive_pages` d, `$ranges` r 
					WHERE d.range_id = r.range_id AND ".fs_get_site_id_query($site_id)." 
					AND ".fs_time_range_between($is_unix_time, $start_time, $end_time);
		
		$sql = "SELECT uid as url_id, u.site_id, url, sum( cc ) c, title 
				FROM ($select1 UNION ALL $select2) `u2` , `$urls` `u` 
				WHERE u.id = uid ".($type != null ? "AND type = '$type'" : "")."
				GROUP BY `site_id`, `uid`
				ORDER BY c DESC ".$limit;
	}
	else
	{
		$sql = "SELECT h.url_id,u.`site_id`,`url`, COUNT(h.url_id) `c`, title 
    			FROM `$hits` h, `$ua` ua , `$urls` u 
    			WHERE h.url_id = u.id 
				".($type != null ? " AND type = '$type'" : "")."
				AND h.useragent_id = ua.id 
				AND $not_excluded 
				AND ".fs_timestamp_between($is_unix_time, $start_time, $end_time)."
    			GROUP BY `site_id` , h.url_id ORDER BY c DESC ".$limit;
	}
	return $fsdb->get_results($sql);
}

function fs_get_country_codes($days_ago = null)
{
	return fs_get_views_per_country_range(true, true,fs_days_ago_to_unix_time($days_ago), null);
}

/**
 * Returns a table mapping country codes to page views from the country in each row, for the specified time range.
 * range is half inclusive : [). 
 *
 * $site_id site id to work on, or false for all sites, or true for current site in options table. (see not_excluded() doc).
 * $is_unix_time true if the start and end times are unix time, false for mysql datetime
 * $start_time timestamp of start time
 * $end_time timestamp of end time.
 */
function fs_get_views_per_country_range($site_id, $is_unix_time, $start_time, $end_time)
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$ua = fs_useragents_table();
	$archive_countries = fs_archive_countries();
	$ranges = fs_archive_ranges();
	$not_excluded = not_excluded();
	$valid_country_code = "`country_code` IS NOT NULL AND `country_code` != '0'";

	if (fs_mysql_newer_than("4.1.14")) 
	{
		$from_site = fs_get_site_id_query($site_id);
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
	    $select1 = "SELECT `site_id`,`country_code`, count(`country_code`) c
	    			FROM `$hits` h, `$ua` ua 
	    			WHERE h.useragent_id = ua.id AND $not_excluded AND $timestamp_between AND $valid_country_code
	    			GROUP BY `site_id` , `country_code`";
		
	    $timerange_between = fs_time_range_between($is_unix_time, $start_time, $end_time);
		$select2 = "SELECT `site_id`,`country_code` ,`views` AS c FROM `$archive_countries` d, `$ranges` r ".
					"WHERE d.range_id = r.range_id AND $from_site AND $timerange_between  AND $valid_country_code";
		
		$sql = "SELECT `country_code`, sum( u.c ) c ".
				"FROM ($select1 UNION ALL $select2)
				`u` GROUP BY `site_id` , `country_code`
				ORDER BY c DESC";
	}
	else
	{
		$sql = "SELECT `country_code`, count(`country_code`) c
						FROM `$hits` h,`$ua` ua
						WHERE ua.id = h.useragent_id AND 
						$not_excluded AND $valid_country_code";
		$sql .= "AND ".fs_timestamp_between($is_unix_time, $start_time, $end_time);
	
		$sql .= " GROUP BY `country_code` ORDER BY c DESC";
	}
	return $fsdb->get_results($sql);
}


/**
 * store some usage FireStats usage information
 */
function fs_maintain_usage_stats()
{
	if (fs_is_admin())
	{
		$first_run_time = fs_get_system_option('first_run_time');
		if (!$first_run_time)
		{
			fs_update_system_option('first_run_time',time());
		}
		
		$firestats_id = fs_get_system_option('firestats_id');
		if (!$firestats_id)
		{
			fs_update_system_option('firestats_id',mt_rand());
		}
	}

	$first_login = fs_get_option('first_login');
	if (!$first_login)
	{
		fs_update_option('first_login',time());
	}
}


function fs_wp_get_users($user_id = null)
{
	if (!fs_in_wordpress())
	{
		echo "not in wp";
		return array(); // currently users are only suppored when installed under wordpress
	}
	$wpdb =& $GLOBALS['wpdb'];
	$sql = "SELECT ID,display_name FROM $wpdb->users";
	$users = $wpdb->get_results($sql,ARRAY_A);
	if ($users === false) return false;
	foreach($users as $u)
	{
		$res[] = array('id'=>$u['ID'],'name'=>$u['display_name']);
	}
	return $res;
}

function fs_botlist_import_url($url, $remove_existing)
{
	$error = '';
	$data = fs_fetch_http_file($url, $error);
	if (!empty($error)) return $error;
	return fs_botlist_import_array(explode("\n",$data), $remove_existing);

}

function fs_botlist_import($file, $remove_existing)
{
	$lines = @file($file);
	if ($lines === false) return sprintf(fs_r('Error opening file : %s'),"<b>$file</b>");
	return fs_botlist_import_array($lines, $remove_existing);
}

function fs_botlist_import_array($lines, $remove_existing)
{
	if ($remove_existing)
	{
		$res = fs_clear_bots_list();
		if ($res != '')
		{
			return $res;
		}
	}

	foreach($lines as $line)
	{
		$l = trim($line);
		if (strlen($l) > 0 && $l[0] != '#')
		{
			$ok = fs_add_bot($line, false);
			if ($ok != '') return $ok;
		}
	}
	return '';
}


function fs_timestamp_between($is_unix_time, $start_time, $end_time)
{
	$sql = '';
	if ($start_time)
	{
		$ts = "'$start_time'";
		if ($is_unix_time)
		{
			$ts = "FROM_UNIXTIME($start_time)";
		}
		$sql .= "`timestamp` >= $ts";
		
		if ($end_time)
		{
			$ts = "'$end_time'";
			if ($is_unix_time)
			{
				$ts = "FROM_UNIXTIME($end_time)";
			}
			if ($sql != "") $sql .= " AND ";
			$sql .= "`timestamp` < $ts";
		}
	}
	return $sql != "" ? $sql : "1";
}

function fs_time_range_between($is_unix_time, $start_time, $end_time)
{
	$sql = '';
	if ($start_time)
	{
		$ts = "'$start_time'";
		if ($is_unix_time)
		{
			$ts = "SUBSTRING(FROM_UNIXTIME($start_time),1,10)";
		}
		$sql .= "`range_start` >= $ts";
		if ($end_time)
		{
			$ts = "'$end_time'";
			if ($is_unix_time)
			{
				$ts = "SUBSTRING(FROM_UNIXTIME($end_time),1,10)";
			}
			if ($sql != "") $sql .= " AND ";
			$sql .= "`range_end` <= $ts";
		}
	}
	return $sql != "" ? $sql : "1";
}

function fs_days_ago_to_unix_time($days_ago)
{
	if ($days_ago)
	{
		return time() - $days_ago * 24 * 60 * 60;
	}
	else
	{
		return null;
	}
}

function fs_set_url_title($url, $title)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$title = $fsdb->escape($title);
	$url = $fsdb->escape($url);
	$res = $fsdb->query("UPDATE `$urls` SET `title`=$title WHERE `url` = $url");
	if ($res === false)
		return fs_db_error();
	else return true;
}

function fs_set_url_title_by_id($url_id, $title)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$res = $fsdb->query("UPDATE `$urls` SET `title`='$title' WHERE `id` = '$id'");
	if ($res === false)
		return fs_db_error();
	else return true;
}

function fs_set_url_type($url, $type)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$res = $fsdb->query("UPDATE `$urls` SET `type`='$type' WHERE `url` = '$url'");
	if ($res === false)
		return fs_db_error();
	else return true;
}

function fs_set_url_type_by_id($url_id, $type)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$res = $fsdb->query("UPDATE `$urls` SET `type`='$type' WHERE `id` = '$id'");
	if ($res === false)
		return fs_db_error();
	else return true;
}


function fs_insert_url_metadata($url, $type, $value)
{
	$url_id = fs_get_url_id($url);
	if (empty($url_id)) return "URL Not found: <b>$url</b>";
	return fs_insert_url_metadata_by_id($url_id, $type, $value);
}

function fs_delete_url_metadata($url, $type, $value)
{
	$url_id = fs_get_url_id($url);
	if (empty($url_id)) return "URL Not found: <b>$url</b>";
	return fs_delete_url_metadata_by_id($url_id, $type, $value);
}

function fs_replace_url_metadata($url, $type, $value = null)
{
	$url_id = fs_get_url_id($url);
	if (empty($url_id)) return "URL Not found: <b>$url</b>";
	return fs_replace_url_metadata_by_id($url_id, $type, $value);
}

function fs_insert_url_metadata_by_id($url_id, $type, $value = null)
{
	$fsdb = &fs_get_db_conn();
	$url_metadata = fs_url_metadata_table();
	$value = $value != null ? $fsdb->escape($value) : 'NULL';
	$url_id = $fsdb->escape($url_id);
	$type = $fsdb->escape($type);
	$sql = "INSERT INTO `$url_metadata` (`url_id`,`type`,`value`) VALUES ($url_id,$type,$value)";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error();
	return true;
}

function fs_delete_url_metadata_by_id($url_id, $type, $value = null)
{
	$fsdb = &fs_get_db_conn();
	$url_metadata = fs_url_metadata_table();
	$value = $value != null ? $fsdb->escape($value) : null;
	$url_id = $fsdb->escape($url_id);
	$type = $fsdb->escape($type);
	$sql = "DELETE FROM `$url_metadata` WHERE `url_id` = $url_id AND `type` = $type";
	if ($value !== null)
	{
		$sql .= " AND `value` = $value";
	}
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error();
	return true;
}

function fs_replace_url_metadata_by_id($url_id, $type, $value = null)
{
	$fsdb = &fs_get_db_conn();
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
	$r1 = fs_delete_url_metadata_by_id($url_id, $type);
	if ($r1 === true)
	{
		$r2 = fs_insert_url_metadata_by_id($url_id, $type, $value);
		$fsdb->query("COMMIT");
		return $r2;
	}
	else
	{
		$fsdb->query("ROLLBACK");
		return $r1;
	}
}

function fs_insert_url($url, $site_id)
{
	$urls = fs_urls_table();
	$fsdb = &fs_get_db_conn();
	if (!is_numeric($site_id)) return "Invalid site id : $site_id";
	$site_id = $fsdb->escape($site_id);
	$url = $fsdb->escape($url);
	$sql = "REPLACE INTO `$urls` (`url`,`site_id`,`md5`,`host`,`add_time`) 
			VALUES ($url,$site_id,MD5(url),substring_index(substring_index(`url`,'/',3),'/',-1),NOW())";
	if($fsdb->query($sql) === false)
		return fs_db_error();
	return true;
}


function fs_get_url_id($url)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$url = $fsdb->escape($url);
	$sql = "SELECT `id` FROM `$urls` WHERE `md5` = MD5($url)";
	return $fsdb->get_var($sql);
}


?>
