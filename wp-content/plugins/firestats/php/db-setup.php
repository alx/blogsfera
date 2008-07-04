<?php
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-common.php');

# Used for first-time initialization - create table if not present...
function fs_install_into($user, $pass, $dbname, $dbhost)
{
	return fs_install_impl(fs_create_db_conn($user,$pass,$dbname, $dbhost));
}

function fs_install($upgrade_if_needed = false)
{
	$fp = fopen(__FILE__, "r");
	if (!$fp) return "Error opening lock file";
	if (flock($fp, LOCK_EX + LOCK_NB) == FALSE)
	{
		fs_e("Operation already in progress");
		return false;
	}
	$fsdb = &fs_get_db_conn();
	if (!isset($fsdb)) die('db object not initialized');
	$res = fs_install_impl($fsdb,$upgrade_if_needed);

	flock($fp, LOCK_UN);
	return $res;
}


function fs_install_impl(&$fsdb, $upgrade_if_needed = false)
{
	$db_status_arr = fs_get_db_status($fsdb);
	$db_status = $db_status_arr['status'];
	$db_version = $db_status_arr['ver'];
	$msg = fs_get_database_status_message($db_status_arr);
	
	if ($db_status == FS_DB_VALID)
	{
		return true;
	}
	else
	if ($db_status == FS_DB_NOT_CONFIGURED || $db_status == FS_DB_IS_NEWER_THAN_CODE)
	{
		echo $msg;
		return false;
	}
	else
	if ($db_status == FS_DB_GENERAL_ERROR || $db_status == FS_DB_CONNECTION_ERROR)
	{
		echo $msg ." : " .$fsdb->debug();
		return false;
	}
	else
	if ($db_status == FS_DB_NOT_INSTALLED)
	{
		if (!fs_db_install($fsdb)) return false;
	}
	else
	if ($db_status == FS_DB_NEED_UPGRADE && $upgrade_if_needed)
	{
		if (!fs_db_upgrade($fsdb,$db_version)) return false;
		fs_do_action("db_upgraded");
		
		// after a db upgrade (major releases only) reset donation status for users that didn't donate
		$donation =  fs_get_option('donation_status');
		if ($donation != 'donated')
		{
			fs_update_option('donation_status','');
			fs_update_option('last_nag_time',time());
		}
	}
	return true;
}

function fs_db_install(&$fsdb)
{
	$fsdb->hide_errors();
	$version_table = fs_version_table();


	$hits_table = fs_hits_table();
	$useragents_table = fs_useragents_table();
	$sql = "
		CREATE TABLE IF NOT EXISTS $hits_table
		(
			`id` INTEGER PRIMARY KEY AUTO_INCREMENT ".fs_comment('Primary key').",
			`site_id` INTEGER default 1 ".fs_comment('Source site ID, defaults to site 0').",
			`ip` VARCHAR(40) NOT NULL DEFAULT 'unknown' ".fs_comment('IP Address of hit source').",
			`timestamp` DATETIME NOT NULL ".fs_comment('Hit timestamp').",
			`url_id` INTEGER ".fs_comment('Hit URL ID').",
			`referer_id` INTEGER ".fs_comment('Referer URL id').",
			`useragent_id` INTEGER ".fs_comment('UserAgent ID').",
			`session_id` VARCHAR(30) ".fs_comment('Client session ID').",
			`user_id` INTEGER default NULL ".fs_comment('User ID in the enclosing system, NULL for unknown user').",
			`country_code` INT(4) default NULL ".fs_comment('Country code of IP address or NULL if unknown').",
			`excluded_by_user` TINYINT(1) DEFAULT 0 ".fs_comment('1 if user explicitly excluded record, 0 otherwise').",
			`excluded_ip` TINYINT(1) DEFAULT 0 ".fs_comment('1 if the ip is in the excluded ips table, 0 otherwise').",
			`excluded_user` TINYINT(1) DEFAULT 0 ".fs_comment('1 if the user id is in the excluded users table, 0 otherwise').",
			INDEX (`site_id`)
		)
		".fs_comment('Hits table').fs_engine("InnoDB");
	if ($fsdb->query($sql) === FALSE)
	{
		$fsdb->debug();
		return false;
	}

	$r = $fsdb->query(
	"CREATE TABLE IF NOT EXISTS `$useragents_table`
		(
			id INTEGER PRIMARY KEY AUTO_INCREMENT ".fs_comment('Primary key').", 
			useragent TEXT ".fs_comment('Useragent string').",
			md5 CHAR(32) NOT NULL,
			match_bots INTEGER DEFAULT 0 ".fs_comment('Number of matching bots (useragent wildcards), if 0 the useragent is not exluded').",
			UNIQUE(`md5`)
		) ".fs_comment('User-Agents table').fs_engine("InnoDB"));

	if ($r === FALSE)
	{
		$fsdb->debug();
		return false;
	}

	$urls_table = fs_urls_table();
	$r = $fsdb->query(
	"CREATE TABLE IF NOT EXISTS $urls_table
		(
			`id` INTEGER PRIMARY KEY AUTO_INCREMENT ".fs_comment('Primary key').",
			`url` TEXT,
			`site_id` INT NULL,
			`md5` CHAR(32) NOT NULL,
			`host` VARCHAR( 40 ) NULL DEFAULT NULL, 
			`search_engine_id` SMALLINT NULL DEFAULT NULL ".fs_comment('Search engine ID').",
			`search_terms` VARCHAR( 255 ) NULL DEFAULT NULL ".fs_comment('Search terms').",
			`title` VARCHAR( 255 ) NULL DEFAULT NULL ".fs_comment('Optional title, or NULL for unkown').",
			`type` INT NULL DEFAULT NULL ".fs_comment('Optional type, or NULL for unkown').",
			`add_time` DATETIME NOT NULL ".fs_comment('Time this url was added').",
			UNIQUE(`md5`),
			INDEX (`search_engine_id`),
			INDEX (`host`),
			INDEX (`type`)
		) ".fs_comment('Referers table').fs_engine("InnoDB"));

	if ($r === FALSE)
	{
		$fsdb->debug();
		return false;
	}

	$excluded_ip_table = fs_excluded_ips_table();
	$r = $fsdb->query(
	"CREATE TABLE IF NOT EXISTS $excluded_ip_table
		(
			id INTEGER PRIMARY KEY AUTO_INCREMENT ".fs_comment('Primary key').",
			ip VARCHAR(16) NOT NULL
		) ".fs_comment('List of excluded ips').fs_engine("InnoDB"));
	if ($r === FALSE)
	{
		$fsdb->debug();
		return false;
	}

	$bots_table = fs_bots_table();
	$r = $fsdb->query(
	"CREATE TABLE IF NOT EXISTS $bots_table
		(
			id INTEGER PRIMARY KEY AUTO_INCREMENT ".fs_comment('Primary key').",
			wildcard VARCHAR(100) NOT NULL ".fs_comment('Bots wildcard')."
		) ".fs_comment('Bots table').fs_engine("InnoDB"));
	if ($r === FALSE)
	{
		$fsdb->debug();
		return false;
	}

	if (!fs_create_options_table($fsdb)) return false;
	if (!fs_create_sites_table($fsdb)) return false;
	if (!fs_create_archive_tables($fsdb)) return false;
	if (!fs_create_users_table($fsdb)) return false;
	if (!fs_create_pending_data_table($fsdb)) return false;
	if (!fs_create_url_metadata($fsdb)) return false;
	
	$r = $fsdb->query("CREATE TABLE IF NOT EXISTS `$version_table`
	(
			version INTEGER NOT NULL PRIMARY KEY
	)".fs_comment('FireStats datbase schema version').fs_engine("InnoDB"));
	if ($r === FALSE)
	{
		$fsdb->debug();
		return false;
	}

	$r = $fsdb->query("INSERT INTO `$version_table` (`version`) VALUES('".FS_REQUIRED_DB_VERSION."')");
	if ($r === FALSE)
	{
		$fsdb->debug();
		return false;
	}

	return true;
}

function fs_create_options_table(&$fsdb)
{
	$options_table = fs_options_table();
	$r = $fsdb->query("CREATE TABLE IF NOT EXISTS `$options_table` (
		`user_id` INT NOT NULL ".fs_comment('User ID or -1 for System option').",
		`option_key` VARCHAR(100) NOT NULL,
		`option_value` TEXT NOT NULL,	
		UNIQUE `user_id_option_key_unique` ( `user_id`,`option_key`)
	) ".fs_comment('FireStats options table').fs_engine("InnoDB"));
	if ($r === FALSE)
	{
		$fsdb->debug();
		return false;
	}

	return true;
}

function fs_db_upgrade(&$fsdb, $db_version)
{
	$fsdb->hide_errors();
	// upgrade to version 10
	if ($db_version < 10)
	{
		require_once(FS_ABS_PATH.'/php/upgrade/upgrade_10.php');
		$res = fs_db_upgrade_10($fsdb, $db_version);
		if ($res === false) return false;	
	}
	
	// upgrade to version 11
	if ($db_version < 11)
	{
		require_once(FS_ABS_PATH.'/php/upgrade/upgrade_11.php');
		$res = fs_db_upgrade_11($fsdb, $db_version);
		if ($res === false) return false;	
	}
	return true;
}

function fs_apply_db_upgrade(&$fsdb,$sqls)
{
	for ($i = 0; $i < count($sqls)/2;$i++)
	{
		$j = $i * 2;
		$condition = $sqls[$j];
		$sql = $sqls[$j+1];
		if ($condition === true)
		{
			if ($fsdb->query($sql) === false)
			{
				$fsdb->debug();
				return false;
			}
		}
		else
		if (is_string($condition))
		{
			// error
			echo "$condition";
			return false;
		}
	}
	return true;
}

function fs_index_not_exists(&$fsdb,$table_name, $index_name)
{
	$r = fs_index_exists($fsdb,$table_name, $index_name);
	return is_bool($r) ? !$r : $r;
}

function fs_index_exists(&$fsdb,$table_name, $index_name)
{
	$sql = "SHOW INDEX FROM `$table_name`";
	$res = $fsdb->get_results($sql);
	if ($res === FALSE)
	{
		return $fsdb->debug(false);
	}
	else
	{
		if ($res !== null && count($res) > 0)
		{
			foreach($res as $r)
			{
				if (isset($r->Key_name) && strtolower($r->Key_name) === strtolower($index_name)) 
				{
					return true;
				}
			}
		}
		return false;
	}
}

function fs_column_type_is_not(&$fsdb,$table_name, $column_name, $column_type)
{
	$r = fs_column_type_is($fsdb,$table_name, $column_name, $column_type);
	return is_bool($r) ? !$r : $r;
}

function fs_column_type_is(&$fsdb,$table_name, $column_name, $column_type)
{
	//$res = $fsdb->get_var("SHOW COLUMNS FROM `$table_name` WHERE `Field` = '$column_name' AND `Type` = '$column_type'");
	$sql = "SHOW COLUMNS FROM `$table_name`";
	$res = $fsdb->get_results($sql);
	if ($res === FALSE)
	{
		return $fsdb->debug(false);
	}
	else
	{
		foreach($res as $r)
		{
			if (isset($r->Field) && isset($r->Type))
			{
				if (strtolower($r->Field) === strtolower($column_name) && strtolower($r->Type) === strtolower($column_type))
				{
					return true;
				}
			}
		}
		return false;
	}
}


function fs_column_not_exists(&$fsdb,$table_name, $column_name)
{
	$r = fs_column_exists($fsdb,$table_name, $column_name);
	return is_bool($r) ? !$r : $r;
	
}

function fs_column_exists(&$fsdb,$table_name, $column_name)
{
	//	$res = $fsdb->get_var("SHOW COLUMNS FROM `$table_name` WHERE `Field` = '$column_name'");
	$sql = "SHOW COLUMNS FROM `$table_name`";
	$res = $fsdb->get_results($sql);
	if ($res === FALSE)
	{
		return $fsdb->debug(false);
	}
	else
	{
		foreach($res as $r)
		{
			if (isset($r->Field))
			{
				if (strtolower($r->Field) === strtolower($column_name))
				{
					return true;
				}
			}
		}
		return false;
	}
}

function fs_table_exists(&$fsdb, $table_name)
{
  	$sql = "SHOW TABLES LIKE '$table_name'";
	$res = $fsdb->get_results($sql);
	if ($res === FALSE)
	{
		return $fsdb->debug(false);
	}
	else
	{
		return !empty($res);
	}
}

function fs_update_db_version(&$fsdb, $new_version)
{
	$version_table = fs_version_table();

	$c = $fsdb->get_var("SELECT count(*) FROM `$version_table`");
	if ($c === false)
	{
		$fsdb->debug();
		return false;
	}

	// in some wierd cases, there is more than one version record in the versions table
	// in those cases, we delete them first.
	if ((int)$c > 1)
	{
		if ($fsdb->query("DELETE FROM `$version_table`") === false)
		{
			$fsdb->debug();
			return false;
		}
		if ($fsdb->query("REPLACE INTO `$version_table` ( `version` )
						  VALUES ('$new_version')") === false)
		{
			$fsdb->debug();
			return false;
		}
	}
	else
	{
		if ($fsdb->query("UPDATE `$version_table` SET `version`='$new_version' WHERE 1") === FALSE)
		{
			$fsdb->debug();
			return false;
		}
	}

	return true;
}
function fs_create_sites_table(&$fsdb)
{
	$sites = fs_sites_table();
	$sql = "CREATE TABLE IF NOT EXISTS `$sites` (
		`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ".fs_comment('Site ID').",
		`type` INT NOT NULL DEFAULT '0' ".fs_comment('Site type').",
		`name` VARCHAR( 100 ) NOT NULL ".fs_comment('Site name')."
		)".fs_comment('FireStats options table').fs_engine("InnoDB");
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}
	return true;
}

function fs_create_archive_tables(&$fsdb)
{
	$ranges = fs_archive_ranges();
	$sql = "CREATE TABLE IF NOT EXISTS `$ranges` (
		`range_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ".fs_comment('Range ID').",
		`range_start` DATETIME NOT NULL ".fs_comment('Range start time').",
		`range_end` DATETIME NOT NULL ".fs_comment('Range end time').",
		UNIQUE `ranges index` (`range_start`,`range_end`)
		)".fs_comment('Archive ranges table').fs_engine("InnoDB");
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	// create baseline range
	$r = $fsdb->query("INSERT IGNORE INTO `$ranges` (`range_id`,`range_start`,`range_end`) VALUES ('1' , '1000-01-01 00:00:00', '1000-01-01 00:00:00')");
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	$r = $fsdb->query(fs_get_create_site_archive());
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	$r = $fsdb->query(fs_get_create_archive_with_id(fs_archive_pages(), 'url_id','Archive for pages'));
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	$r = $fsdb->query(fs_get_create_archive_with_id(fs_archive_referrers(), 'url_id', 'Archive for referrers'));
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	$r = $fsdb->query(fs_get_create_archive_with_id(fs_archive_useragents(), 'useragent_id', 'Archive for useragents'));
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	$countries_archive = fs_archive_countries();
	$sql = "CREATE TABLE IF NOT EXISTS `$countries_archive` (
		`range_id` INT NOT NULL ".fs_comment('Range ID').",
		`site_id` INTEGER NOT NULL ".fs_comment('Site ID of this data').",
		`country_code` INTEGER NOT NULL ".fs_comment('Country code for this data').",
		`views`  INTEGER NOT NULL ".fs_comment('Number of views from country in time range').",
		`visits` INTEGER NOT NULL ".fs_comment('Number of visits from country in time range').",
		UNIQUE `index` (`range_id`,`site_id`,`country_code`)
		) ".fs_comment("Countries archive table").fs_engine("InnoDB");
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	return true;
}

function fs_get_create_site_archive()
{
	$archive_sites = fs_archive_sites();
	$sql = "CREATE TABLE IF NOT EXISTS `$archive_sites` (
		`range_id` INT NOT NULL ".fs_comment('Range ID').",
		`site_id` INTEGER NOT NULL ".fs_comment('Site ID of this data').",
		`views`  INTEGER NOT NULL ".fs_comment('Number of views in time range').",
		`visits` INTEGER NOT NULL ".fs_comment('Number of visits in time range').",
		UNIQUE `index` (`range_id`,`site_id`)
		) ".fs_comment("Archive for sites (page views and visitors per site)").fs_engine("InnoDB");
	return $sql;
}

function fs_get_create_archive_with_id($table_name, $id_name, $comment)
{
	$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
		`range_id` INT NOT NULL ".fs_comment('Range ID').",
		`site_id` INTEGER NOT NULL ".fs_comment('Site ID of this data').",
		`$id_name` INTEGER NOT NULL ".fs_comment('Url ID for this data').",
		`views`  INTEGER NOT NULL ".fs_comment('Number of views in time range').",
		`visits` INTEGER NOT NULL ".fs_comment('Number of visits in time range').",
		UNIQUE `index` (`range_id`,`site_id`,`$id_name`)
		) ".fs_comment("$comment").fs_engine("InnoDB");
	return $sql;
}

function fs_create_users_table(&$fsdb)
{
	$users = fs_users_table();
	$sql= "CREATE TABLE IF NOT EXISTS `$users` (
			`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`username` VARCHAR( 32 ) NOT NULL ,
			`password` VARCHAR( 32 ) NOT NULL ,
			`email` CHAR( 32 ) NOT NULL ,
			`security_level` SMALLINT NOT NULL ,
				UNIQUE (
					`username` ,
					`email`
				)
			) ".fs_comment("Users table").fs_engine("InnoDB");
	$r = $fsdb->query($sql);
	if ($r === FALSE)
	{
		return false;
	}
	
	return true;
}

function fs_create_pending_data_table(&$fsdb)
{
	$pending = fs_pending_date_table();
	$sql = "CREATE TABLE IF NOT EXISTS `$pending` (
				`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
				`timestamp` DATETIME NOT NULL ,
				`site_id` INT NOT NULL ,
				`user_id` INT NULL ,
				`url` TEXT NOT NULL ,
				`referrer` TEXT NOT NULL ,
				`useragent` TEXT NOT NULL ,
				`ip` VARCHAR( 40 ) NOT NULL
				) ".fs_comment("Pending data table").fs_engine("MYISAM");
	$r = $fsdb->query($sql);
	if ($r === FALSE)
	{
		return false;
	}
	
	return true;			
}

function fs_create_url_metadata(&$fsdb)
{
	$urlmeta = fs_url_metadata_table();
	$sql = "CREATE TABLE IF NOT EXISTS `$urlmeta` 
		(
			`url_id` INT NOT NULL ,
			`type` INT NOT NULL ,
			`value` TEXT NULL ,
			INDEX ( `url_id` , `type` )
		) ".fs_engine("InnoDB");
			
	$r = $fsdb->query($sql);
	if ($r === FALSE)
	{
		return false;
	}
	
	return true;				
}

function fs_comment($str)
{
	$fs_mysql_version = fs_mysql_version();
	if (ver_comp("4.1.0",$fs_mysql_version) > 0)
	{
		return ""; // no comment.
	}
	else
	{
		return "COMMENT '".$str."'";
	}
}

function fs_engine($engine)
{
	$fs_mysql_version = fs_mysql_version();
	if (ver_comp("4.0.18",$fs_mysql_version) > 0 || ver_comp("4.1.2",$fs_mysql_version) > 0)
	{
		return " TYPE=$engine";
	}
	else
	{
		return " ENGINE=$engine";
	}
}
?>
