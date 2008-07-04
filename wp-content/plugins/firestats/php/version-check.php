<?php

require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/utils.php');


function fs_get_latest_version_info($url, &$error)
{
	$data = fs_fetch_http_file($url, $error);
	if (!empty($error)) return false;
	$arr = explode("\n",$data);
	return fs_readINIArray($arr,'#');
}

function fs_lazy_get_latest_version_info($url, $type, $default_check_interval, &$error, $force_check = false)
{
	$fsdb = fs_get_db_conn();
	if (!$fsdb->is_connected()) return null;
	$version_check_enabled = fs_get_system_option($type."_version_check_enabled",'true');
	if ($version_check_enabled != 'false' || $force_check)
	{
		$last_version_check_time = (int)fs_get_system_option($type.'_last_version_check_time');
		$version_check_interval = (int)fs_get_system_option($type.'_version_check_interval',$default_check_interval);

		$now = time();

		$d = ($last_version_check_time + $version_check_interval);
		if ($now > $d || $force_check)
		{
			// in case of an a freeze due to server networking problem, we don't want subsequent calls to check again.
			// for this reason, I deactivate the version check for the specifed type before testing, and reactivate it after
			// if the script hangs, the user will probably reload and this time it will work because the check
			// will never be re-activated.
			fs_update_system_option($type."_version_check_enabled",'false');

			// time to check
			$err = '';
			$info = fs_get_latest_version_info($url, $err);
			if ($err != '')
			{
				$error = sprintf(fs_r('Error getting version information from %s : %s'),$url, $err);
				return null;
			}

			fs_update_system_option($type.'_last_version_check_time',$now);

			// the fetch was successful, we can re-activate the version check
			fs_update_system_option($type."_version_check_enabled",'true');


			// if data is null we have a networking problem. just pretend everything is fine
			// so the server will not continue trying till enough time passed
			if ($info != null)
			{
				fs_update_system_option($type.'_last_version_info_on_server',serialize($info));
			}
			return $info;
		}
		else
		{
			// use cached result from last check
			$info = fs_get_system_option($type.'_last_version_info_on_server');
			if ($info)
			{
				return unserialize($info);
			}
			else
			{
				// if for some reason we have n data in the cache, try again, this time with force_check = true.
				return fs_lazy_get_latest_version_info($url, $type, $error, $default_check_interval, true);
			}
		}
	}
	else
	{
		return null; // user does not want version check. assume we are good.
	}
}

function fs_is_ip2country_db_need_update(&$download_url, &$file_type, &$version, &$info, &$error)
{
	$current_version = fs_get_current_ip2c_db_version();
	$url = FS_IP2COUNTRY_DB_VER_CHECK_URL;
	$type = 'ip-to-country-db';
	$info = fs_lazy_get_latest_version_info($url, $type, 60*60*24*14, $error, true); // check every two weeks, force check
	if(!empty($info[$type]['version']))
	{
		if (function_exists('gzinflate'))
		{
			$file_type = 'zip';
			$download_url = $info[$type]['zip_url'];
		}
		else
		{
			$file_type = 'bin';
			$download_url = $info[$type]['bin_url'];
		}
		$version = $info[$type]['version'];
		return (ver_comp($current_version, $version) < 0);
	}
	else
	{
		return false;
	}
}

function fs_get_latest_ip2c_db_version_message()
{
	$current_version = fs_get_current_ip2c_db_version();
	$url = sprintf(FS_IP2COUNTRY_DB_VER_CHECK_URL, $current_version);
	$type = 'ip-to-country-db';
	$error = '';
	$info = fs_lazy_get_latest_version_info($url, $type,60*24*14, $error);
	if (!empty($error))
	{
		return $error;
	}
	$ver = isset($info[$type]) ? $info[$type]['version'] : "";
	if ($info != null && ver_comp($current_version,$ver) < 0)
	{
		return
		'<br/>'.
		fs_r('A new version of the IP-to-country database is available! you can update it from the settings tab');
	}
	else
	{
		return "";
	}
}

function fs_get_latest_firestats_version_message()
{
	$url = FS_FIRESTATS_VER_CHECK_URL;
	$error = '';
	$res = fs_get_latest_version_message($url, 'firestats',FS_VERSION, $error, 60*60*24);
	if (empty($error)) return $res;
	else return $error;
}

function fs_get_latest_version_message($url, $type, $current_version, &$error, $default_check_interval)
{
	$info = fs_lazy_get_latest_version_info($url, $type, $error, $default_check_interval);
	$ver = isset($info[$type]) ? $info[$type]['version'] : "";
	if ($info != null && ver_comp($current_version,$ver) < 0)
	{
		return '<br/>'.sprintf(fs_r('Version %s is available! click %s for more info'),
		$info[$type]['version'],
		'<a target="_blank" href="'.FS_HOMEPAGE."?upgrade_from=".FS_VERSION.'">'.fs_r('here').'</a>');
	}
	else
	{
		return "";
	}
}

function fs_get_current_ip2c_db_version()
{
	$name = FS_ABS_PATH."/lib/ip2c/db.version";
	if (!file_exists($name)) return '';
	$f = @fopen($name,"r");
	if (!$f) return 0;
	$ver = fgets($f);
	fclose($f);
	return $ver;
}

function fs_set_current_ip2c_db_version($version)
{
	$name = FS_ABS_PATH."/lib/ip2c/db.version";
	$f = @fopen($name,"w");
	if (!$f) return sprintf(fs_r('Error opening %s'),$name);
	fputs($f,$version);
	return '';
}

function fs_get_botlist_version_hash()
{
	return fs_get_system_option('botlist_version_hash','');
}

function fs_is_botlist_updated(&$url, &$md5, &$error,$force_check = false)
{
	$check_url = FS_FIRESTATS_VER_CHECK_URL;
	$info = fs_lazy_get_latest_version_info($check_url, 'botlist',60*60*24*14, $error, $force_check);
	if (!empty($error))
	{
		return false;
	}
	
	$url = '';
	if (isset($info['botlist']['md5']))
	{
		$md5 = $info['botlist']['md5'];;
		$url = $info['botlist']['url'];
	}
	$cur = fs_get_botlist_version_hash();
	return $cur != '' && $cur == $md5;
}

?>
