<?php
require_once(FS_ABS_PATH.'/lib/ip2c/ip2c.php');

if (!isset($GLOBALS['fs_ip2c']))
{
	// during update, the file may not be there temporarily
	$__fs_ip2c_file = FS_ABS_PATH.'/lib/ip2c/ip-to-country.bin';
	if (file_exists($__fs_ip2c_file)) 
	{
		$GLOBALS['fs_ip2c'] = new fs_ip2country($__fs_ip2c_file);
		$GLOBALS['fs_ip2c_country_cache'] = array();
	}
}

/**
 * $ip - the IP address.
 * $as_int - false to return a 2 chars code (il, us etc). true to return as integer (can be used as database key)
 */
function fs_ip2c($ip, $as_int = false)
{
	if (isset($GLOBALS['fs_ip2c']))
	{
		$ip2c = $GLOBALS['fs_ip2c'];
		$ip2c_res = $ip2c->get_country($ip);
		if ($ip2c_res != false)
		{
			$ccode = $ip2c_res['id2'];
			if ($ccode == null) return null;
			if ($as_int)
			{
				$c1 = ord($ccode[0]);
				$c2 = ord($ccode[1]);
				$intcode = ($c1 << 8) | $c2;
				return $intcode;
			}
			else
			{
				return $ccode;
			}
		}
	}

	return false;
}

function fs_get_country_name($country_code, $is_int = false)
{
	if (isset($GLOBALS['fs_ip2c_country_cache']))
	{
		$cache = $GLOBALS['fs_ip2c_country_cache'];
		if ($is_int)
		{
			$c = chr(($country_code >> 8) & 0xFF).chr(($country_code) & 0xFF);
			$country_code = $c;
		}
		
		$res = isset($cache[$country_code]) ? $cache[$country_code] : false;
		if (!$res)
		{
			$ip2c = $GLOBALS['fs_ip2c'];
			$res = $ip2c->find_country($country_code);
			$cache[$country_code] = $res;
		}
		return $res['name'];
	}
	else
	{
		return '';
	}
}


function fs_get_country_flag_url($country_code, $is_int = false)
{
	if ($is_int && $country_code != NULL)
	{
		$c = chr(($country_code >> 8) & 0xFF).chr(($country_code) & 0xFF);
		$country_code = $c;
	}
    if (!$country_code) return "";
    $code = strtolower($country_code);
    $flag_url = fs_url("img/flags/$code.png");
    $name = fs_get_country_name($code);
    
	return fs_get_flag_img_tag($name, $flag_url);
}

function fs_get_flag_img_tag($name, $img_url)
{
    return "<img src='$img_url' alt='$name' title ='$name' width='16' height='11' class='fs_flagicon'/>";
}

function fs_echo_country_flag_url($country_code)
{
    echo fs_get_country_flag_url($country_code);
}



/**
 * Downloads a zip file containing the ip2country database and import it.
 */
function fs_update_ip2country_db($url, $file_type, $new_version)
{
	$cant_write = fs_ip2c_database_writeable();
	if ($cant_write != '')
	{
		return array('status'=>'error','message'=>$cant_write);
	}

	require_once(FS_ABS_PATH.'/php/utils.php');

	$dir = $GLOBALS['FS_TEMP_DIR'];
	$tempName = @tempnam($dir,"fs_ip2c_");
	if (!$tempName)
		return array('status'=>'error','message'=>fs_r('Error creating temporary file'));

	$temp = @fopen($tempName,"w");
	if (!$tempName)
		return array('status'=>'error','message'=>fs_r('Error creating temporary file'));

	$res = fs_create_http_conn($url);
	$http = $res['http'];
	$args = $res['args'];
	$error=$http->Open($args);
	if (!empty($error))
	{
		return array('status'=>'error','message'=>sprintf(fs_r('Error opening connection to %s'),$url));
	}
	$error = $http->SendRequest($args);
	if (!empty($error))
	{
		return array('status'=>'error','message'=>sprintf(fs_r('Error sending request to %s'),$url));
	}
	
	$http->ReadReplyHeadersResponse($headers);
	if ($http->response_status != '200')
	{
		return array('status'=>'error','message'=>sprintf(fs_r("Server returned error %s for %s"),"<b>$http->response_status</b>", "<b>$url</b>"));
	}	

	$output = ob_get_clean();
	// this is a little hack to keep outputing stuff while we download, it should help 
	// with the server killing the script due to inactivity.
	echo "/* Downloading IP-to-country database. if you see this, your server didn't give this script enough time to complete.<br/>";

	$content = '';
	for(;;)
	{
		$body = "";
		$error=$http->ReadReplyBody($body,10000);
		if($error != "") 
			return array('status'=>'error','message'=>sprintf(fs_r('Error reading data from %s : %s'),$url, $error));

		echo "*";

		if ($body == '') break;

		fwrite($temp, $body);
	}
	echo "*/";
	ob_start();
	echo $output;

	if ($file_type == 'bin')
	{
		return fs_install_bin_ip2c_database($new_version,$temp);
	}
	else
	if ($file_type == 'zip')
	{
		$bin_file = '';
		$res = fs_extract_zip_ip2c_database($tempName,$bin_file);
		if ($res == '')
		{
			$ok = fs_install_bin_ip2c_database($new_version, $bin_file);
			fs_clean_ip2c_temp();
			return $ok;
		}
		else
		{
			return array('status'=>'error','message'=>sprintf(fs_r("Error extracting IP-to-country database: %s"),$res));
		}
	}
	else
	{
		return array('status'=>'error','message'=>sprintf(fs_r('Unsupported file type : %s'), $file_type));
	}

}

function fs_clean_ip2c_temp()
{
	$dir = $GLOBALS['FS_TEMP_DIR'];
	fs_unlink($dir, "fs_ip2c_*");
	unlink("$dir/db.version");
	unlink("$dir/ip-to-country.bin");
}

function fs_ip2c_database_writeable()
{
	$ip2c_dir = FS_ABS_PATH.'/lib/ip2c/';
	$bin_file = FS_ABS_PATH.'/lib/ip2c/ip-to-country.bin';
	$ver_file = FS_ABS_PATH.'/lib/ip2c/db.version';

	if (!is_writable($ip2c_dir))
		return sprintf(fs_r("can't update ip-to-country database, read-only directory : %s"),$ip2c_dir);
	if (file_exists($bin_file) && !is_writable($bin_file))
		return sprintf(fs_r("can't update ip-to-country database, read-only file : %s"),$bin_file);
	if (file_exists($ver_file) && !is_writable($ver_file))
		return sprintf(fs_r("can't update ip-to-country database, read-only file : %s"),$ver_file);
	return '';
}

function fs_install_bin_ip2c_database($version, $new_bin_file)
{
	$ip2c_dir = FS_ABS_PATH.'/lib/ip2c/';
	$bin_file = FS_ABS_PATH.'/lib/ip2c/ip-to-country.bin';
	$ver_file = FS_ABS_PATH.'/lib/ip2c/db.version';
	
	if (file_exists($bin_file))	unlink($bin_file);
	if (file_exists($ver_file))	unlink($ver_file);
 
	if (copy($new_bin_file, $bin_file) === false)
	{
		return array('status'=>'error','message'=>fs_r("Error installing new IP-to-country database"));
	}
	
	$res = fs_set_current_ip2c_db_version($version);
	if ($res == '')
	{
		return array('status'=>'ok','message'=>fs_r("Successfuly updated IP-to-country database"));
	}
	else
	{
		return array('status'=>'error','message'=>sprintf(fs_r("Error updating version file : %s"),$res));
	}
}

function fs_extract_zip_ip2c_database($zipFile,&$bin_file)
{
	require_once(FS_ABS_PATH."/lib/unzip/dUnzip2.inc.php");
	ob_start();
	$zip = new dUnzip2($zipFile);
	$dir = $GLOBALS['FS_TEMP_DIR'];
	$zip->unzipAll($dir);

	$tempName2 = tempnam($dir,"fs_ip2c_");
	if (!$tempName2)
		return fs_r('Error creating temporary file');
	
	$zip->unzip("ip-to-country.bin", $tempName2);
	$output = ob_get_clean();
	if ($output != '')
	{
		return $output;
	}

	$bin_file = $tempName2;
	return '';
}

function fs_rebuild_country_codes($value)
{
	$reset = $value == 0;
	$num_to_build = 1000;
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	if ($reset)
	{
		if (false === $fsdb->get_results("UPDATE `$hits` SET `country_code` = '0'"))
		{
			return fs_db_error();
		}
	}
	
	$res = $fsdb->get_results("SELECT DISTINCT(IP) FROM `$hits` WHERE `country_code` = '0' LIMIT $num_to_build");
	if ($res === false)
	{
		return fs_db_error();
	}
	else
	{
		$chunk_size = 200;
		$c = count($res);
		$index = 0;
		if ($c > 0)
		{
			while($index < $c)
			{
				$ii = 0;
				$sql = "UPDATE `$hits` SET `country_code` = CASE ";
				$ips = '';
				while ($ii < $chunk_size && $index < $c)
				{
					$record = $res[$index++];
					$ii++;
					
					$ip = $record->IP;
					if ($ips == '')
					{
						$ips .= "'$ip'";
					}
					else 
						$ips .= ",'$ip'";
					
					$intcode = fs_ip2c($ip, true);
					
					if ($intcode != 0)
					{
						$sql .= "WHEN IP='$ip' THEN '$intcode' ";
					}
					else
					{
						$sql .= "WHEN IP='$ip' THEN NULL ";
					}
				}
				$sql .= " ELSE `IP` END WHERE IP IN ($ips)";
				$r2 = $fsdb->query($sql);
				if ($r2 === false)
				{
					return fs_db_error();
				}
			}
		}
		return $index;
	}
}

function fs_rebuild_countries_calc_max()
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$count = $fsdb->get_var("SELECT COUNT(DISTINCT(IP)) c FROM `$hits`");
	if ($count === null)
	{
		return fs_db_error();
	}
	else
	{
		return $count;
	}
}


?>
