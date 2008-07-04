<?php

define('FS_NO_SESSION', true);
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-common.php');

/**
 * Like add hit, but with a mandatory site ID
 */
function fs_add_site_hit($site_id, $user_id = null, $close_connection = true)
{
	fs_add_hit($user_id,$close_connection,$site_id);
}

function fs_add_hit($user_id = null,$close_connection = true, $site_id = 1)
{
	require_once(dirname(__FILE__).'/init.php');
	$res = fs_add_hit__($user_id, $site_id);
	if ($close_connection)
	{
		$fsdb = &fs_get_db_conn();
		$fsdb->disconnect();
		fs_get_db_conn(false,true);
	}
}

function fs_add_hit__($user_id, $site_id)
{
	if (FS_COMMIT_STRATEGY == FS_COMMIT_IMMEDIATE)
	{
		return fs_add_hit_immediate__($user_id, $site_id);
	}
	else
	if (FS_COMMIT_STRATEGY == FS_COMMIT_MANUAL)
	{
		return fs_add_hit_delayed__($user_id, $site_id);
	}
	else
		return "FireStats: Unknown commit strategy";
}

function fs_get_hit_data($fsdb, $user_id,$site_id)
{
	$d = new stdClass();
	if ($user_id != null) $user_id = $fsdb->escape($user_id);
	$remoteaddr = $useragent = $url = $referer = "'unknown'";
	$site_id = $fsdb->escape($site_id);
	$real_ip = fs_get_ip_address();
	if (isset($_SERVER['REMOTE_ADDR']))
		$remoteaddr = $fsdb->escape(fs_limited_htmlentities($real_ip));
	if (isset($_SERVER['HTTP_USER_AGENT']))
		$useragent 	= $fsdb->escape(fs_limited_htmlentities($_SERVER['HTTP_USER_AGENT']));
	if (isset($_SERVER['REQUEST_URI']))
		$url = $fsdb->escape(fs_limited_htmlentities(fs_get_absolute_url($_SERVER['REQUEST_URI'])));
	if (isset($_SERVER['HTTP_REFERER']))
	{
		// if referrer is relative, convert it to absolute using the requested URI (see RFC 2616 section 14.36)
		$ref = fs_get_absolute_url($_SERVER['HTTP_REFERER'], $_SERVER['REQUEST_URI']);
		$referer = $fsdb->escape(fs_limited_htmlentities($ref));
	}
	$d->ip_address = $real_ip;
	$d->user_id = $user_id;
	$d->site_id = $site_id;
	$d->remoteaddr = $remoteaddr;
	$d->useragent = $useragent;
	$d->url = $url;
	$d->referer= $referer;
	return $d;
}

function fs_add_hit_immediate__($user_id, $site_id, $time = null)
{
	if (!fs_db_valid())
	{
		return fs_get_database_status_message();
	}

	$fsdb = &fs_get_db_conn();
	$d = fs_get_hit_data($fsdb, $user_id, $site_id);
	$user_id = $d->user_id;
	$site_id = $d->site_id;
	$remoteaddr = $d->remoteaddr;
	$useragent = $d->useragent;
	$url = $d->url;
	$referer = $d->referer;
	
	if ($time === null)
	{
		$time = "NOW()";
	}
	else
	{
		$time = $fsdb->escape($time);
	}

	$useragents = fs_useragents_table();
	$urls = fs_urls_table();
	$excluded_ips = fs_excluded_ips_table();

	if($fsdb->query("START TRANSACTION") === false) return fs_debug_rollback();

	// insert to user agent table (no duplicates)
	$ret = $fsdb->query("INSERT IGNORE INTO `$useragents` (`useragent`,`md5`) VALUES ($useragent ,MD5(`useragent`))");
	if($ret === false)  return fs_debug_rollback();

	// if we actually inserted a new useragent, we need to match it against existing filters.
	if ($ret > 0)
	{
		$bots = fs_bots_table();
		$ret = $fsdb->get_row("SELECT ua.id id,count(wildcard) c
				FROM $bots RIGHT JOIN $useragents ua 
				ON useragent REGEXP wildcard 
				WHERE useragent = $useragent
				GROUP BY useragent");
		if ($ret === false)  return fs_debug_rollback();
		$ret = $fsdb->query("UPDATE $useragents SET match_bots='$ret->c' WHERE id='$ret->id'");
		if ($ret === false)  return fs_debug_rollback();
	}

	$save_excluded = fs_get_save_excluded_records() === 'true';

	$c = $fsdb->get_var("SELECT COUNT(ip) FROM `$excluded_ips` WHERE `ip` = ".$remoteaddr);
	if ($c === false)  return fs_debug_rollback();

	$c = (int)$c;
	$excluded_ip = ($c > 0) ? 1 : 0;

	$excluded_users = fs_get_local_option('firestats_excluded_users');
	if ($excluded_users === false)  return fs_debug_rollback();

	$excluded_user = $user_id && $excluded_users && in_array($user_id,explode(",",$excluded_users)) ? 1 : 0;

	// get index of useragent in table, can't use LAST_INSERT_ID() here because of the no-dups policy
	$ua_info = $fsdb->get_row("SELECT id,match_bots from `$useragents` WHERE `useragent` = $useragent");
	$excluded_useragent = $ua_info->match_bots > 0;

	// check if we want to save this
	if (!$save_excluded && ($excluded_useragent || $excluded_user || $excluded_ip))
	{
		return true;
	}

	$useragent_id = $ua_info->id;
	if ($useragent_id === false)  return fs_debug_rollback();

	// insert to urls table (no duplicates)
	$url = ($url ? "$url" : "''");
	if($fsdb->query("INSERT IGNORE INTO `$urls` (`url`,`md5`,`add_time`,`host`) 
					 VALUES ($url,MD5(url),$time,substring_index(substring_index(`url`,'/',3),'/',-1))") === false)
	{
		return fs_debug_rollback();
	}

	// get index of url in table, can't use LAST_INSERT_ID() here because of the no-dups policy
	$url_id = $fsdb->get_var("SELECT id from ".fs_urls_table()." WHERE `url` = $url");
	if ($url_id === false) return fs_debug_rollback();
	if ($url_id == null)
	{
		return fs_debug_rollback();
	}
	
	// update site id of url to current site id.
	// this is only done for the url and not for the referrer:
	// we don't know the site id of the referrer. if it will appear as a url it will be assigned the site_id. 
	if (false === $fsdb->get_var("UPDATE `$urls` SET `site_id` = $site_id WHERE `id` = $url_id"))
	{
		return fs_debug_rollback();
	}
	

	// insert referers into urls table (no duplicates)
	$referer = ($referer ? "$referer" : "''");
	
	require_once(FS_ABS_PATH.'/php/searchengines.php');
	$search_engine_id = "NULL";
	$search_terms = "NULL";
	$referrer_breakdown = null;
	if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']))
	{
		$search_params = fs_get_search_terms_and_engine($_SERVER['HTTP_REFERER'],$referrer_breakdown);
		if ($search_params)
		{
			$id = $search_params->engine_id;
			$terms = $search_params->search_terms;
			if (!empty($id)) $search_engine_id = $fsdb->escape($id);
			if (!empty($terms)) $search_terms = $fsdb->escape($terms);
		}
	}

	$has_scheme = isset($referrer_breakdown['scheme']);
	$optional_host = ($has_scheme ? ",`host`":"");
	$optional_host_query = ($has_scheme ? ",substring_index(substring_index(`url`,'/',3),'/',-1)":"");
	if($fsdb->query("INSERT IGNORE INTO `$urls`(`url`,`md5`,`add_time`,`search_engine_id`,`search_terms` $optional_host) VALUES ($referer,MD5(url),$time,$search_engine_id ,$search_terms $optional_host_query)") === false)
	{
		return fs_debug_rollback();
	}

	// get index of url in table, can't use LAST_INSERT_ID() here because of the no-dups policy
	$referer_id = $fsdb->get_var("SELECT id from $urls WHERE `url` = $referer");
	if ($referer_id === false) return fs_debug_rollback();
	if ($referer_id == null)
	{
		echo "FireStats : Error getting referrer id  ";
		return fs_debug_rollback();
	}

	require_once(dirname(__FILE__).'/ip2country.php');
	$ip2c_res =	fs_ip2c($d->ip_address, true);
	$ccode = ($ip2c_res ? $fsdb->escape($ip2c_res) : "NULL");
	// insert to database.
	$sql = "INSERT IGNORE INTO ".fs_hits_table()."
			(site_id,ip,timestamp,url_id,referer_id,useragent_id,session_id,excluded_ip,excluded_user,user_id,country_code) 
					VALUES ($site_id,
							$remoteaddr,
							$time,
							$url_id,
							$referer_id,
							$useragent_id,
							".(isset($session_id) ? "$session_id" : "NULL").",
							$excluded_ip,
							$excluded_user,
							".($user_id ? "$user_id" : "NULL").",
							$ccode
							)";

	if($fsdb->query($sql) === false) return fs_debug_rollback();

	if($fsdb->query("COMMIT") === false)  return fs_debug_rollback();
	
	return true;
}

function fs_add_hit_delayed__($user_id, $site_id)
{
	$pending = fs_pending_date_table();
	$fsdb = &fs_get_db_conn();
	$d = fs_get_hit_data($fsdb, $user_id, $site_id);
	$user_id = $d->user_id;
	$site_id = $d->site_id;
	$remoteaddr = $d->remoteaddr;
	$useragent = $d->useragent;
	$url = $d->url;
	$referer = $d->referer;
	
			
	$sql = "INSERT DELAYED INTO `$pending` (
				`timestamp`,
				`site_id` ,
				`user_id`,
				`url` ,
				`referrer` ,
				`useragent` ,
				`ip`
			)
			VALUES (
				NOW(), 
				$site_id, 
				".($user_id ? "$user_id" : "NULL")." , 
				$url, 
				$referer, 
				$useragent, 
				$remoteaddr
			)";	
				
	if($fsdb->query($sql) === false) 
	{
		return fs_db_error();
	}
}

/**
 * This function returns the best ip address for the client.
 * the if the client passed through a proxy it tries to detect the correct client ip.
 * if its a private (LAN) address it uses first public IP (usually the proxy itself).
 */
function fs_get_ip_address()
{
	// obtain the X-Forwarded-For value.
	$headers = function_exists('getallheaders') ? getallheaders() : null;
	$xf = isset($headers['X-Forwarded-For']) ? $headers['X-Forwarded-For'] : "";
	if (empty($xf))
	{
		$xf = isset($GLOBALS['FS_X-Forwarded-For']) ? $GLOBALS['FS_X-Forwarded-For'] : "";
	}

	if (empty($xf))
	{
		$xf = $_SERVER['REMOTE_ADDR'];
	}
	else
	{
		$xf = $xf.",".$_SERVER['REMOTE_ADDR'];
	}
	$fwd = explode(",",$xf);
	foreach($fwd as $ip)
	{
		$ip = trim($ip);
		$long = ip2long($ip);
		if ($long != -1 && $long !== false)
		{
			if (fs_is_public_ip($ip)) return $ip;
		}
	}

	// if we got this far and still didn't find a public ip, just use the first ip address in the chain.
	return $fwd[0];
}

function fs_is_public_ip($long)
{

	// 167772160 - 10.0.0.0
	// 184549375 - 10.255.255.255
	//
	// -1408237568 - 172.16.0.0
	// -1407188993 - 172.31.255.255
	//
	// -1062731776 - 192.168.0.0
	// -1062666241 - 192.168.255.255
	//
	// -1442971648 - 169.254.0.0
	// -1442906113 - 169.254.255.255
	//
	// 2130706432 - 127.0.0.0
	// 2147483647 - 127.255.255.255 (32 bit integer limit!!!)
	//
	// -1 is also b0rked	
	if (($long >= 167772160 AND $long <= 184549375) OR
		($long >= -1408237568 AND $long <= -1407188993) OR
		($long >= -1062731776 AND $long <= -1062666241) OR
		($long >= 2130706432 AND $long <= 2147483647) OR $long == -1)
	{
		return false;
	}

	return true;
}

function fs_limited_htmlentities($str)
{
	return str_replace (array ( '<', '>'),
						array ( '&lt;' , '&gt;'),
						$str);	

}

function fs_debug_rollback()
{
	$fsdb = &fs_get_db_conn();
	$msg = sprintf(fs_r('Database error: %s'), $fsdb->last_error).'<br/>'. sprintf('SQL: %s', $fsdb->last_query);
	$fsdb->query("ROLLBACK");
	echo $msg;
	return;
}
?>
