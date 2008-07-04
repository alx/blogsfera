<?php
function fs_db_upgrade_10(&$fsdb, $db_version)
{
	$version_table = fs_version_table();
	// a nice little convert loop.
	$useragents = fs_useragents_table();
	$hits = fs_hits_table();

	// upgrade to version 2
	if ($db_version < 2)
	{
		if (!fs_create_options_table($fsdb)) return false;
		if (!fs_update_db_version($fsdb, 2)) return false;
	}


	// convert charsets, this is instead of collate which does not work on mysql 4.0
	if ($db_version < 3)
	{
		if (ver_comp("4.1.0",fs_mysql_version()) < 0)
		{
			$sqls = array("ALTER TABLE `$useragents` DROP INDEX `unique`",
			"ALTER TABLE `$useragents` ADD `md5` CHAR( 32 ) NOT NULL AFTER `useragent`",
			"UPDATE `$useragents` SET `md5` = MD5( `useragent` )",
			"ALTER TABLE `$useragents` ADD UNIQUE (`md5`)",
			"ALTER TABLE `$hits` CHANGE `timestamp` `timestamp` DATETIME NOT NULL");
			foreach ($sqls as $sql)
			{
				if ($fsdb->query($sql) === false)
				{
					$fsdb->debug();
					return false;
				}
			}

			// deprecated table, function no longer exists.
			$referers = fs_table_prefix().'firestats_referers';
			
			// convert tables charset to utf-8
			$tables = array(fs_excluded_ips_table(),fs_hits_table(),
			fs_bots_table(),fs_options_table(),
			$referers,fs_urls_table(),
			fs_version_table(), fs_useragents_table());

			foreach ($tables as $table)
			{
				$sql = "ALTER TABLE `$table` CONVERT TO CHARSET utf8";
				if ($fsdb->query($sql) === false)
				{
					$fsdb->debug();
					return false;
				}
			}
		}
		if (!fs_update_db_version($fsdb, 3)) return false;
	}

	if ($db_version < 4)
	{
		/*no longer recalculates bots count*/
		if (!fs_update_db_version($fsdb, 4)) return false;
	}

	if ($db_version < 5)
	{
			
		if ($fsdb->query("ALTER TABLE `$hits` ADD `country_code` BLOB NULL DEFAULT NULL AFTER `user_id`") === false)
		{
			$fsdb->debug();
			return false;
		}

		if (!fs_update_db_version($fsdb, 5)) return false;
	}

	if ($db_version < 6)
	{
		require_once(FS_ABS_PATH.'/php/rebuild-db.php');
		require_once(dirname(__FILE__).'/db-sql.php');
		$res = fs_botlist_import(dirname(__FILE__).'/botlist.txt',true);
		if ($res != '')
		{
			echo $res;
			return;
		}
		// bots are now matched using regular expressions. need to recalculate.
		fs_recalculate_match_bots();

		if (!fs_update_db_version($fsdb, 6)) return false;
	}

	if ($db_version < 7)
	{
		if (fs_column_not_exists($fsdb,$hits,'site_id'))
		{
			if ($fsdb->query("ALTER TABLE `$hits` ADD `site_id` INT NOT NULL DEFAULT 1 AFTER `id`") === false)
			{
				$fsdb->debug();
				return false;
			}
		}

		if (fs_index_not_exists($fsdb, $hits, 'site_id'))
		{
			if ($fsdb->query("ALTER TABLE `$hits` ADD INDEX (`site_id`)") === false)
			{
				$fsdb->debug();
				return false;
			}
		}
		if (!fs_update_db_version($fsdb, 7)) return false;
	}

	if ($db_version < 8)
	{
		if (!fs_create_sites_table($fsdb)) return false;
		if (!fs_update_db_version($fsdb, 8)) return false;
	}

	if ($db_version < 9)
	{
		
		if (!fs_create_archive_tables($fsdb)) return false;
		$urls = fs_urls_table();
		$refs = fs_table_prefix().'firestats_referers'; // deprecated table, function no longer exists.

		$sqls = array
		(
		//Change urls table so that can hold text of any length.
		fs_index_exists($fsdb, $urls, 'url')				,"ALTER TABLE `$urls` DROP INDEX `url`",
		fs_column_type_is_not($fsdb, $urls, 'url', 'Text')	,"ALTER TABLE `$urls` CHANGE `url` `url` TEXT NULL DEFAULT NULL",
		fs_column_not_exists($fsdb,$urls,'md5')				,"ALTER TABLE `$urls` ADD `md5` CHAR( 32 ) NOT NULL AFTER `url`",
		true												,"UPDATE `$urls` SET `md5` = MD5( `url` )",
		fs_index_not_exists($fsdb,$urls,'md5')				,"ALTER TABLE `$urls` ADD UNIQUE (`md5`)",
			
		//Change referrers table so that can hold text of any length.
		fs_index_exists($fsdb, $refs, 'referer')			,"ALTER TABLE `$refs` DROP INDEX `referer`",
		fs_column_type_is_not($fsdb, $refs,'referer','Text'),"ALTER TABLE `$refs` CHANGE `referer` `referer` TEXT NULL DEFAULT NULL",
		fs_column_not_exists($fsdb,$refs,'md5')	,"ALTER TABLE `$refs` ADD `md5` CHAR( 32 ) NOT NULL AFTER `referer`",
		true												,"UPDATE `$refs` SET `md5` = MD5( `referer`)",
		fs_index_not_exists($fsdb,$refs,'md5')				,"ALTER TABLE `$refs` ADD UNIQUE (`md5`)",
			
			
		// add search engines id and search terms
		fs_column_type_is_not($fsdb, $refs, 'search_engine_id', 'SMALLINT(6)')	,"ALTER TABLE `$refs` ADD `search_engine_id` SMALLINT(6) NULL DEFAULT NULL ".fs_comment('Search engine ID'),
		fs_column_type_is_not($fsdb, $refs, 'search_terms', 'VARCHAR(255)'),"ALTER TABLE `$refs` ADD `search_terms` VARCHAR(255) NULL DEFAULT NULL ".fs_comment('Search terms'),
		fs_index_not_exists($fsdb,$refs,'search_engine_id'),"ALTER TABLE `$refs` ADD INDEX ( `search_engine_id` )",

		// Add host row
		fs_column_type_is_not($fsdb, $refs, 'host', 'VARCHAR(40)'),"ALTER TABLE `$refs` ADD `host` VARCHAR(40) NULL DEFAULT NULL AFTER `md5`",
		// add index for hosts row
		fs_index_not_exists($fsdb,$refs,'host'),"ALTER TABLE `$refs` ADD INDEX (`host`)",
		// populate hosts row
		true,"UPDATE `$refs` SET `host`=substring_index(substring_index(`referer`,'/',3),'/',-1) WHERE `referer` REGEXP 'http://.*'",

		// drop useragent count row.
		fs_column_exists($fsdb,$useragents,'count'),"ALTER TABLE `$useragents` DROP `count`",
		);

		if (!fs_apply_db_upgrade($fsdb,$sqls)) return false;

		if (!fs_update_db_version($fsdb, 9)) return false;
	}

	if ($db_version < 10)
	{
		// This is a special case.
		// Version 9 was a short lived version that already includes this change.
		// I moved it to version 10 to eliminate the problem of users not completing the upgrade and
		// getting stuck with version 9.5 (This operation is the longest in 8->9 upgrade and is the most likely cause for things like that).
		//Converts country code from blob to int.
		$sqls = array
		(
			fs_column_type_is_not($fsdb, $hits, 'country_code', 'INT(4)'),"ALTER TABLE `$hits` CHANGE `country_code` `country_code` INT(4) NULL DEFAULT NULL"
		);
		if (!fs_apply_db_upgrade($fsdb,$sqls)) return false;
		if (!fs_update_db_version($fsdb, 10)) return false;
	}
	return true;	
}
?>
