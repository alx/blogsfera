<?php

require_once(dirname(__FILE__).'/constants.php');
require_once(dirname(__FILE__).'/errors.php');

function fs_ends_with( $str, $sub )
{
	return ( substr( $str, strlen( $str ) - strlen( $sub ) ) === $sub );
}

// echo translated text
function fs_e($txt)
{
	global $fs_gettext;
	if (isset($fs_gettext))
	echo $fs_gettext->get($txt);
	else echo $txt;
}

// return translated text
function fs_r($txt)
{
	global $fs_gettext;
	if (isset($fs_gettext)) return $fs_gettext->get($txt);
	else return $txt;
}

function fs_url($file)
{
	global $fs_base_url;
	
	if (!isset($fs_base_url))
	{
		if (function_exists('fs_override_base_url'))
		{
			$fs_base_url = fs_override_base_url();
		}
		else
		{
			$fs_base_url = "";
		}
	}
	
	return $fs_base_url.$file;
}

/**
 * returns a URL which javascript can connect to to accesss FireStats resources.
 * browser security prevents JavaScript from accessing hosts other than the one 
 * it was downloaded from.
 */
function fs_js_url($file, $suffix = "")
{
	// This is a work around browsers restricting javascript from accessing different hosts.
	// in wordpress, the Ajax url may be on a different host than the url of the blog.
	// so the browsers prevent javascript from accessing the ajax handler.
	// what happens here is that we redirect the ajax call through the origin page
	global $FS_CONTEXT;
	if (isset($FS_CONTEXT['JAVASCRIPT_URL']))
	{
		return $FS_CONTEXT['JAVASCRIPT_URL'].$file."&".fs_get_request_suffix($suffix,false);
	}
	else
	{
		return fs_url($file).fs_get_request_suffix($suffix);
	}
}

function fs_get_request_suffix($append = "", $prepand_with_qm = true)
{
	require_once(dirname(__FILE__).'/session.php');
	$t = $prepand_with_qm ? '?' : '';
	$t .= 'sid='.fs_get_session_id();
	if ($append)
	{
		$t .= "&$append";
	}
	return $t;
}

function fs_get_whois_providers()
{
	static $whois_providers;
	if (!isset($whois_providers))
	{
		$providers = file(FS_ABS_PATH.'/php/whois.txt');
		foreach($providers as $line)
		{
			$r = sscanf($line,"%s %s");
			$whois_providers[$r[0]] = $r[1];
		}
	}
	return $whois_providers;
}


/*
 Function to replace PHP's parse_ini_file() with much fewer restritions, and
 a matching function to write to a .INI file, both of which are binary safe.

 Version 1.0

 Copyright (C) 2005 Justin Frim <phpcoder@cyberpimp.pimpdomain.com>

 Sections can use any character excluding ASCII control characters and ASCII
 DEL.  (You may even use [ and ] characters as literals!)

 Keys can use any character excluding ASCII control characters, ASCII DEL,
 ASCII equals sign (=), and not start with the user-defined comment
 character.

 Values are binary safe (encoded with C-style backslash escape codes) and may
 be enclosed by double-quotes (to retain leading & trailing spaces).

 User-defined comment character can be any non-white-space ASCII character
 excluding ASCII opening bracket ([).

 readINIfile() is case-insensitive when reading sections and keys, returning
 an array with lower-case keys.
 writeINIfile() writes sections and keys with first character capitalization.
 Invalid characters are converted to ASCII dash / hyphen (-).  Values are
 always enclosed by double-quotes.

 writeINIfile() also provides a method to automatically prepend a comment
 header from ASCII text with line breaks, regardless of whether CRLF, LFCR,
 CR, or just LF line break sequences are used!  (All line breaks are
 translated to CRLF)
 */

function fs_readINIfile ($filename, $commentchar)
{
	return fs_readInitArray(file($filename),$commentchar);
}

function fs_readINIArray ($array1, $commentchar = '#')
{
	$section = '';
	foreach ($array1 as $filedata)
	{
		$dataline = trim($filedata);
		$firstchar = substr($dataline, 0, 1);
		if ($firstchar!=$commentchar && $dataline!='')
		{
			//It's an entry (not a comment and not a blank line)
			if ($firstchar == '[' && substr($dataline, -1, 1) == ']')
			{
				//It's a section
				$section = strtolower(substr($dataline, 1, -1));
			}
			else
			{
				//It's a key...
				$delimiter = strpos($dataline, '=');
				if ($delimiter > 0)
				{
					//...with a value
					$key = strtolower(trim(substr($dataline, 0, $delimiter)));
					$value = trim(substr($dataline, $delimiter + 1));
					if (substr($value, 1, 1) == '"' && substr($value, -1, 1) == '"')
					{
						$value = substr($value, 1, -1);
					}
					$array2[$section][$key] = stripcslashes($value);
				}
				else
				{
					//...without a value
					$array2[$section][strtolower(trim($dataline))]='';
				}
			}
		}else
		{
			//It's a comment or blank line.  Ignore.
		}
	}
	return $array2;
}

function fs_writeINIfile ($filename, $array1, $commentchar, $commenttext) {
	$handle = fopen($filename, 'wb');
	if ($commenttext!='') {
		$comtext = $commentchar.
		str_replace($commentchar, "\r\n".$commentchar,
		str_replace ("\r", $commentchar,
		str_replace("\n", $commentchar,
		str_replace("\n\r", $commentchar,
		str_replace("\r\n", $commentchar, $commenttext)
		)
		)
		)
		)
		;
		if (substr($comtext, -1, 1)==$commentchar && substr($comtext, -1, 1)!=$commentchar) {
			$comtext = substr($comtext, 0, -1);
		}
		fwrite ($handle, $comtext."\r\n");
	}
	foreach ($array1 as $sections => $items) {
		//Write the section
		if (isset($section)) { fwrite ($handle, "\r\n"); }
		//$section = ucfirst(preg_replace('/[\0-\37]|[\177-\377]/', "-", $sections));
		$section = ucfirst(preg_replace('/[\0-\37]|\177/', "-", $sections));
		fwrite ($handle, "[".$section."]\r\n");
		foreach ($items as $keys => $values) {
			//Write the key/value pairs
			//$key = ucfirst(preg_replace('/[\0-\37]|=|[\177-\377]/', "-", $keys));
			$key = ucfirst(preg_replace('/[\0-\37]|=|\177/', "-", $keys));
			if (substr($key, 0, 1)==$commentchar) { $key = '-'.substr($key, 1); }
			$value = ucfirst(addcslashes($values,''));
			fwrite ($handle, '    '.$key.' = "'.$value."\"\r\n");
		}
	}
	fclose($handle);
}

/**
 Compare versions like 0.1.2[-beta]
 where -beta is optional.

 return 0 if ver1 = ver2
 -1 if ver1 < ver2
 1 if ver1 > ver2
 */
function ver_comp($ver1, $ver2, $ignore_suffix = false)
{
	$r1 = sscanf($ver1,"%d.%d.%d-%s");
	$r2 = sscanf($ver2,"%d.%d.%d-%s");
	if ($r1[0] == $r2[0])
	{
		if ($r1[1] == $r2[1])
		{
			if ($r1[2] == $r2[2])
			{
				if ($ignore_suffix) return 0;
				if ($r1[3] == $r2[3]) return 0;
				if ($r1[3] == null) return 1;
				if ($r2[3] == null) return -1;
				return strcmp($r1[3],$r2[3]);
			}
			else
			{
				return $r1[2] - $r2[2] < 0 ? -1 : 1;
			}
		}
		else
		{
			return $r1[1] - $r2[1] < 0 ? -1 : 1;
		}
	}
	else
	{
		return $r1[0] - $r2[0] < 0 ? -1 : 1;
	}
}

function ver_suffix($version)
{
	$r = sscanf($version,"%d.%d.%d-%s");
	return count($r) == 4 ? $r[3] : false;
}

function fs_create_http_conn($url)
{
	require_once(FS_ABS_PATH.'/lib/http/http.php');
	@set_time_limit(0);
	$http=new fs_http_class;
	$http->timeout=10;
	$http->data_timeout=15;
	$http->user_agent= 'FireStats/'.FS_VERSION.' ('.FS_HOMEPAGE.')';
	$http->follow_redirect=1;
	$http->redirection_limit=5;
	$arguments = "";
	$error = $http->GetRequestArguments($url,$arguments);
	return array('status'=>(empty($error)?"ok" : $error ),"http"=>$http, "args"=>$arguments);
}

function fs_fetch_http_file($url, &$error)
{
	$res = fs_create_http_conn($url);
	if ($res['status'] != 'ok')
	{
		$error = $res['status'];
		return null;
	}
	else
	{

		$http = $res['http'];
		$args = $res['args'];
		$error=$http->Open($args);
		if (!empty($error))
		{
			return false;
		}

		$error = $http->SendRequest($args);
		if (!empty($error))
		{
			return false;
		}
		
		$http->ReadReplyHeadersResponse($headers);
		if ($http->response_status != '200')
		{
			$error = sprintf(fs_r("Server returned error %s for %s"),"<b>$http->response_status</b>", "<b>$url</b>");
			return false;
		}

		$content = '';
		for(;;)
		{
			$body = "";
			$error=$http->ReadReplyBody($body,1000);
			if($error!="" || strlen($body)==0)
			break;
			$content .= $body;
		}
		return $content;
	}
}

function fs_time_to_nag()
{
	/**
	 * if donation status is not no or donated
	 * if last nag time > now - 32 days
	 * nag
 	 */

	$status = fs_get_option('donation_status');
	$last_nag_time = fs_get_option('last_nag_time');
	if (!$last_nag_time)
	{
		$last_nag_time = fs_get_option('first_login');
	}
	if ($status != 'no' && $status != 'donated')
	{
		return time() - $last_nag_time > 60*60*24*32;

	}

	return false;
}

function fs_authenticate()
{
	global $FS_SESSION;
	return (isset($FS_SESSION['authenticated']) && $FS_SESSION['authenticated']);
}

function fs_get_relative_url($url)
{
	$text = $url;
	if ($text == "") return $text;
    $p = @parse_url($url);
    if ($p != false)
    {
        if (isset($p['scheme'])) // absolute
        {
            if ($p['host'] == $_SERVER['SERVER_NAME'])
            {
                if (isset($p['path']))		$text = $p['path'];
                if (isset($p['query'])) 	$text .= "?".$p['query'];
                if (isset($p['fragment'])) 	$text .= "#".$p['fragment'];
            }
        }
    }
    return $text;
}

function fs_get_absolute_url($path, $base = null)
{
	$result = $path;
	if ($result == "") return $result;
	$p = @parse_url($path);
	if ($p === false) return $path;

	if (!isset($p['scheme'])) // relative
	{
		// make sure path starts with /
		if (strlen($path) == 0 ||  substr($path, 0, 1) != "/") $path = "/".$path;
		
		if ($base)
		{
			$b = @parse_url($base);
			if (isset($b['scheme']))
			{
				$scheme = $b['scheme'];
				$host   = $b['host'];
				$port   = isset($b['port']) ? $b['port'] : '80';
			}
		}
		
		if (!isset($scheme)) // base is not defined or relative, use SERVER as base.
		{
			$host = $_SERVER['HTTP_HOST'];
			$port = $_SERVER['SERVER_PORT'];
			if ( !isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on' )
			{
				$scheme = "http";
			}
			else
			{
				$scheme = "https";
			}
		}
		
		$portstr = $port == "80" ? "" : ":".$port;
		$result = $scheme."://".$host.$portstr.$path;
	}
	return $result;
}

function fs_mkPair($d,$msg)
{
	$s = new stdClass();
	$s->d = $d;
	$s->msg = $msg;
	return $s;
}

/**
 * System information includes:
 * Unique firestats id
 * FireStats version
 * Installation time
 * PHP version
 * MySQL version
 * Server software (apache? IIS? which version?)
 * Memory limit
 * Number of sites monitored
 * Number of sites monitored from each type (how many wordpress blogs, how many drupals etc).
 */
function fs_get_sysinfo()
{
	require_once(dirname(__FILE__).'/db-common.php');
	$s = array();
	$s["FIRESTATS_ID"] = fs_get_system_option('firestats_id');
	$s["FIRESTATS_VERSION"] = FS_VERSION;
	$s["INSTALLATION_TIME"] = fs_get_system_option('first_run_time');
	
	$s["PHP_VERSION"] = phpversion();
	$s["MYSQL_VERSION"] = fs_mysql_version();
	$s["SERVER_SOFTWARE"] = $_SERVER["SERVER_SOFTWARE"];
	$s["MEMOEY_LIMIT"] = ini_get('memory_limit');
	
	$sites_table = fs_sites_table();
	$sql = "SELECT type,COUNT(type) c from $sites_table GROUP BY type";
	$fsdb = &fs_get_db_conn();
	$res = $fsdb->get_results($sql);
	if ($res === false) return $s;
	$total = 0;
	if (count($res) > 0)
	{
		foreach($res as $r)
		{
			$s["NUM_SITES_$r->type"] = $r->c;
			$total += $r->c;
		}
	}
	$s["NUM_SITES"] = $total;
	
	return $s;
}

function fs_last_sent_info_outdated()
{
	$last_sysinfo_ser = fs_get_option('last_sent_sysinfo');
	if ($last_sysinfo_ser)
	{
		$current_sysinfo = fs_get_sysinfo();
		$last_sysinfo = unserialize($last_sysinfo_ser);
		foreach ($last_sysinfo as $k => $v)
		{
			if (isset($current_sysinfo[$k]) && $current_sysinfo[$k] != $last_sysinfo[$k])
			{
				return true;
			}
		}
		return false;
	}
	return true;
}

function fs_unlink($path,$match,$recursive = false)
{
	$dirs = glob($path."*");
	$files=glob($path.$match);
	foreach($files as $file)
	{
		if(is_file($file))
		{
			unlink($file);
		}
	}
	
	foreach($dirs as $dir)
	{
		if($recursive && is_dir($dir))
		{
			$dir=basename($dir)."/";
			fs_unlink($path.$dir,$match);
		}
	}
}

function fs_validate_email_address($mail) 
{
	$user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
	$domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
	$ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
	$ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';
	return preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $mail);
}

function fs_microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

function fs_array_remove(&$a_Input, $m_SearchValue)
{
	$a_Keys = array_keys($a_Input, $m_SearchValue);
	foreach($a_Keys as $s_Key) 
	{
		unset($a_Input[$s_Key]);
	
	}
	return $a_Input;
}

function &fs_get_incremental_processes()
{
	static $ips;
	if (!isset($ips))
	{
		$ips = array();	
	}
	return $ips;
}

/**
 * Registers an incremental process to be executed as a sequence of steps.
 * We register two functions that will help us execute the process:
 * 1. max_calc()
 * max_calc calculates the number of steps for the whole process, and accept no arguments.
 * on error it returns a string with the error message, on success it returns an int with the number of steps.
 * 
 * 2. step_exec($value)
 * step_exec executes a step in the process.
 * returns the number of actual steps executes, or error message string on error.
 * 
 * 3. step_desc($value)
 * returns the desciption test for the specifeid step.
 *
 * @param $id process ID (unique name for the process)
 * @param $max_calc a function that calculates the number of steps in the process
 * @param $step_exec a function that exectures a step in the process
 * @param $step_desc a function that returns the description of a step
 * @param unknown_type $includes an array containing a list of files to include before calling max_calc of step_exec.
 */
function fs_register_incremental_process($id, $max_calc, $step_exec, $step_desc, $includes = array())
{
	$ips = &fs_get_incremental_processes();
	$ip = new stdClass();
	$ip->max_calc = $max_calc;
	$ip->step_exec = $step_exec;
	$ip->step_desc = $step_desc;
	$ip->includes = $includes;
	$ips[$id] = $ip;
}

function fs_calculate_process_max($id)
{
	$ips = &fs_get_incremental_processes();
	if (!isset($ips[$id])) return "Unknown process id : $id (calc max)";
	$ip = $ips[$id];
	$includes = $ip->includes;
	foreach($includes as $include)
	{
		require_once($include);
	}
	$max_calc = $ip->max_calc;
	if (!function_exists($max_calc)) return "function does not exist : $max_calc";
	return $max_calc();
}


function fs_execute_process_step($id, $value)
{
	$ips = fs_get_incremental_processes();
	if (!isset($ips[$id])) return "Unknown process id : $id (exec step)";
	$ip = $ips[$id];
	$includes = $ip->includes;
	foreach($includes as $include)
	{
		require_once($include);
	}
	$step_exec = $ip->step_exec;
	if (!function_exists($step_exec)) return "function does not exist : $step_exec";
	return $step_exec($value);
}


function fs_get_step_description($id, $value)
{
	$ips = &fs_get_incremental_processes();
	if (!isset($ips[$id])) return "Unknown process id : $id (step desc)";
	$ip = $ips[$id];
	$includes = $ip->includes;
	foreach($includes as $include)
	{
		require_once($include);
	}
	$step_desc = $ip->step_desc;
	if ($step_desc != null)
	{
		if (!function_exists($step_desc)) return "function does not exist : $step_desc";
		return $step_desc($value);
	}
	return null;
}

function fs_add_pending_maintanence_job($id)
{
	$jobs = fs_get_system_option('pending_maintanence', '');
	if ($jobs == '') $jobs = $id;
	else $jobs .= ",$id";
	// remove duplicate jobs
	$jobs = implode(',',array_unique(explode(',',$jobs)));
	fs_update_system_option('pending_maintanence',$jobs);
}
?>
