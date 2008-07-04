<?php
function fs_db_upgrade_11(&$fsdb, $db_version)
{
	$r = fs_create_users_table($fsdb);
	if ($r === FALSE)
	{
		echo fs_db_error();
		return false;
	}
	
	$options = fs_options_table();
	$referers = fs_table_prefix().'firestats_referers'; // deprecated table, function no longer exists.
	$urls = fs_urls_table();
	$hits = fs_hits_table();
	$archive_pages = fs_archive_pages();
	$archive_ranges = fs_archive_ranges();
	
	$user_id_missing = fs_column_not_exists($fsdb,$options,'user_id');
	$sqls = array
	(
		fs_index_exists($fsdb, $options, 'option_key')		,"ALTER TABLE `$options` DROP INDEX `option_key`",
		fs_column_exists($fsdb,$options,'id')				,"ALTER TABLE `$options` DROP `id`",
		$user_id_missing		,"ALTER TABLE `$options` ADD `user_id` INT NOT NULL FIRST",
		fs_index_not_exists($fsdb, $options, 'user_id_option_key_unique'),"ALTER TABLE `$options` ADD UNIQUE `user_id_option_key_unique` ( `user_id`,`option_key`)",
		fs_column_exists($fsdb,$referers,'referer'), "ALTER TABLE `$referers` CHANGE `referer` `url` TEXT NULL DEFAULT NULL",
		fs_column_not_exists($fsdb,$referers,'site_id'), "ALTER TABLE `$referers` ADD `site_id` INT NULL AFTER `url`",
		fs_column_not_exists($fsdb,$urls,'site_id'), "ALTER TABLE `$urls` ADD `site_id` INT NULL AFTER `url`",
		fs_column_not_exists($fsdb,$urls,'new_url_id'), "ALTER TABLE `$urls` ADD `new_url_id` INT NULL",
		fs_column_not_exists($fsdb,$referers,'title'), "ALTER TABLE `$referers` ADD `title` VARCHAR( 255 ) NULL DEFAULT NULL",
		fs_column_not_exists($fsdb,$referers,'type'), "ALTER TABLE `$referers` ADD `type` INT NULL DEFAULT NULL",
		fs_index_not_exists($fsdb, $referers, 'type'),"ALTER TABLE `$referers` ADD INDEX `type` (`type`)",
		fs_column_not_exists($fsdb,$referers,'add_time'), "ALTER TABLE `$referers` ADD `add_time` DATETIME NOT NULL",
	);
	
	if (!fs_apply_db_upgrade($fsdb,$sqls)) return false;
	
	// if created user id, change the following options to system options.
	if ($user_id_missing)
	{
		$system_options = array
		(
			'firestats_id',
			'first_run_time','archive_method',
			'botlist_last_version_check_time',
			'botlist_last_version_info_on_server',
			'botlist_version_check_enabled',
			'botlist_version_hash',
			'firestats_last_version_check_time',
			'firestats_last_version_info_on_server',
			'firestats_version_check_enabled',
			'ip-to-country-db_last_version_check_time',
			'ip-to-country-db_last_version_info_on_server',
			'ip-to-country-db_version_check_enabled',
			'archive_method',
			'archive_older_than',
			'auto_bots_list_update',
			'last_sent_sysinfo',
			'user_agreed_to_send_system_information',
			'last_version_check_time'
		);
		
		foreach($system_options as $opt)
		{
			$sql = "UPDATE `$options` SET `user_id` = '-1' WHERE `option_key`= '$opt'";
			if (false === $fsdb->query($sql))
			{
				echo fs_db_error();
				return false;			
			}
		}
	}
	
	if (!fs_create_pending_data_table($fsdb)) return false;
	
	if (!fs_create_url_metadata($fsdb)) return false;
	
	$fsdb->query("START TRANSACTION");
	
	if (fs_mysql_newer_than("4.1.0"))
	{
		// pupulate current urls table with site ids based on urls in the hits table.
		$sql = "UPDATE `$urls` u,
				   (SELECT DISTINCT(u.id) url_id,h.site_id FROM `$urls` u,`$hits` h WHERE u.id = h.url_id ORDER BY `timestamp` DESC) k 
				SET u.site_id = k.site_id 
				WHERE u.id = k.url_id";
		$r = $fsdb->query($sql);
		if ($r === FALSE)
		{
			echo fs_db_error(true);
			return false;
		}
		
		// pupulate current urls table with site ids based on urls in the archive pages table.
		// this step is not needed for mysql < 4.1.0 because we only support archving for mysql > 4.1.14
		$sql = "UPDATE `$urls` u,(SELECT site_id, url_id, max(range_start) from `$archive_pages` p, `$archive_ranges` r WHERE p.range_id = r.range_id GROUP BY url_id) k 
				SET u.site_id = k.site_id WHERE u.id = k.url_id";
		$r = $fsdb->query($sql);
		if ($r === FALSE)
		{
			echo fs_db_error(true);
			return false;
		}
	}
	else
	{
		// mysql 4.0 does not support nested update-selects. need to update them one by one.
		 
		// get site ids of urls 
		$sql = "SELECT DISTINCT(u.id) url_id,h.site_id FROM `$urls` u,`$hits` h WHERE u.id = h.url_id ORDER BY `timestamp` DESC";
		$res = $fsdb->get_results($sql);
		if ($res === FALSE)
		{
			echo fs_db_error(true);
			return false;
		}
		
		// pupulate current urls table with site ids.
		foreach($res as $u)
		{
			$sql = "UPDATE `$urls` u SET u.site_id = '$u->site_id' WHERE u.id = $u->url_id";
			$r = $fsdb->query($sql);
			if ($r === FALSE)
			{
				echo fs_db_error(true);
				return false;
			}
		}
	}
	
		
	// insert all urls in urls table into referrers table, along with their corrosponding site_id
	$sql = "INSERT IGNORE INTO `$referers` (`url`,`md5`) SELECT url,MD5(url) FROM `$urls`";
			
	$r = $fsdb->query($sql);
	if ($r === FALSE)
	{
		echo fs_db_error(true);
		return false;
	}
	
	// set the site id in the referrers table for urls that were in the urls table.
	$sql = "UPDATE `$referers` r, `$urls` u SET r.site_id = u.site_id WHERE u.md5 = r.md5";
	$r = $fsdb->query($sql);
	if ($r === FALSE)
	{
		echo fs_db_error(true);
		return false;
	}
	
	// update host column of referrers table (lines inserted from urls table does not contain them).
	$sql = "UPDATE `$referers` SET `host`=substring_index(substring_index(`url`,'/',3),'/',-1) WHERE `url` REGEXP 'http://.*'";
	$r = $fsdb->query($sql);
	if ($r === FALSE)
	{
		echo fs_db_error(true);
		return false;
	}
	
	// populate new_url_id row in urls table based on the url id in the referrers table
	$sql = "UPDATE `$urls` u,`$referers` r SET `new_url_id`= r.id WHERE MD5(u.url) = r.md5";
	$r = $fsdb->query($sql);
	if ($r === FALSE)
	{
		echo fs_db_error(true);
		return false;
	}
	
	// update add_time for existing urls.
	if (fs_mysql_newer_than("4.1.0"))
	{
		// set add_time to urls in the urls table
		$select = "SELECT id,MIN(`timestamp`) `timestamp` FROM (SELECT url_id AS id, MIN(`timestamp`) `timestamp` FROM `$hits` GROUP BY `url_id` UNION SELECT `referer_id` AS `id`, MIN(`timestamp`) `timestamp` FROM `$hits` GROUP BY `referer_id`) `u`  GROUP BY id";
		$sql = "UPDATE `$referers`,($select) k SET `add_time` = k.`timestamp` WHERE $referers.id = k.id";
		$r = $fsdb->query($sql);
		if ($r === FALSE)
		{
			echo fs_db_error(true);
			return false;
		}
		
		$select = "SELECT id,MIN(`timestamp`) `timestamp` FROM (SELECT url_id AS id, MIN(`timestamp`) `timestamp` FROM `$hits` GROUP BY `url_id` UNION SELECT `referer_id` AS `id`, MIN(`timestamp`) `timestamp` FROM `$hits` GROUP BY `referer_id`) `u`  GROUP BY id";
		$sql = "UPDATE `$referers`,($select) k SET `add_time` = k.`timestamp` WHERE $referers.id = k.id";
		$r = $fsdb->query($sql);
		if ($r === FALSE)
		{
			echo fs_db_error(true);
			return false;
		}
		
	}
	else
	{
		$sql = "SELECT referer_id id, MIN(timestamp) `timestamp` FROM `$hits` GROUP BY referer_id LIMIT 10";
		$res = $fsdb->get_results($sql);
		if ($res === FALSE)
		{
			echo fs_db_error(true);
			return false;
		}
		foreach($res as $ref)
		{
			$r = $fsdb->query("UPDATE $referers r set r.add_time = '$ref->timestamp' WHERE r.id = '$ref->id'");
			if ($r === FALSE)
			{
				echo fs_db_error(true);
				return false;
			}
			
		}
	}
	
	// if unique index 'ip' exists in hits table, drop it
	if (fs_index_exists($fsdb, $hits, 'ip'))
	{
		// drop unique index.
		// in fact, some tests shows that we don't really need it from the performance pov, and I really don't understand why we need 
		// it from the uniqueness pov.
		$r = $fsdb->query("ALTER TABLE `$hits` DROP INDEX `ip`");
		if ($r === FALSE)
		{
			echo fs_db_error(true);
			return false;
		}			
	}
	
	// update hits table with new url ids.
	$sql = "UPDATE `$hits` h,`$urls` u SET h.url_id = u.new_url_id WHERE h.url_id = u.id";
	$r = $fsdb->query($sql);
	if ($r === FALSE)
	{
		echo fs_db_error(true);
		return false;
	}
	
	
	// if unique index exists in archive pages table, drop it
	if (fs_index_exists($fsdb, $archive_pages, 'index'))
	{
		// drop unique index for the duration of the update.
		$r = $fsdb->query("ALTER TABLE `$archive_pages` DROP INDEX `index`");
		if ($r === FALSE)
		{
			echo fs_db_error(true);
			return false;
		}			
	}
		
	// update pages archive table table with new url ids.
	$sql = "UPDATE `$archive_pages` h,`$urls` u SET h.url_id = u.new_url_id WHERE h.url_id = u.id";
	$r = $fsdb->query($sql);
	if ($r === FALSE)
	{
		echo fs_db_error(true);
		return false;
	}
	
	// re-establish unique index
	$r = $fsdb->query("ALTER TABLE `$archive_pages` ADD UNIQUE `index` ( `range_id` , `site_id` , `url_id` )");
	if ($r === FALSE)
	{
		echo fs_db_error(true);
		return false;
	}
	
	$fsdb->query("COMMIT");
	
	if (fs_table_exists($fsdb, $referers))
	{
		$r = $fsdb->query("DROP TABLE `$urls");
		if ($r === FALSE)
		{
			echo fs_db_error(true);
			return false;
		}
		
		$r = $fsdb->query("RENAME TABLE `$referers` TO `$urls`");
		if ($r === FALSE)
		{
			echo fs_db_error(true);
			return false;
		}
	}
	
	fs_add_pending_maintanence_job('recalculate_search_engine_terms');

 	if (!fs_update_db_version($fsdb, 11)) return false;
	
	return true;
}
?>
