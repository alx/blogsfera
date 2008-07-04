<?php
/**
 * This file contains API that allow usage of sessions independent of the HTTP header state.
 * in other words, you are not required to initialize the session only before any data was 
 * was sent in the http body.
 * however, you are responsible to transmit the session id to the client, and
 * to restore the session with the session id recieved from the client using the function
 * fs_session_start($sid).
 */

/**
 * you may need to force FireStats to use a specific directory for the session files:
 * for example, SourceForge hosting only allow write access to /tmp/persistent/.
 * 
 * to enable this, uncomment the following define line (remove the #) and change it to your desired temp directory. 
 */
#define('FS_TEMP_DIR','/tmp/persistent');
 
/**
 * In the rare configurations where FireStats is shared by 
 * multiple operating system users (actual users on the OS, not users in some subsystem), set this to true.
 * this will create a directory under the chosen temp dir for each user, thuse preventing permission conflicts.
 */
define('CREATE_USER_DIR',false);

/**
 * minimum time between garbage collection, normally you don't need to change this.
 */
define('GC_INTERVAL_SECONDS',60*30);

/**
 * number of seconds before timing out a session, normally you don't need to change this.
 */
define('SESSION_TIMEOUT',60*60);

/**
 * sessions dir, this is only appended to the temp directory when FS_TEMP_DIR is not defined
 * normally you don't need to change this.
 */ 
define('SESSIONS_DIR','fs_sessions');

/**
 * THis turns on debug information for the session management code.
 * only turn this to true if you are tying to debug a sessions problem.
 */
define('FS_SESSION_DEBUG',false);

/**
 * Regisrer fs_store_session as a shutdown function, to ensure the session is saved on shutdown.
 */
register_shutdown_function('fs_store_session');

/**
 * ensure sys_get_temp_dir is available even on php4
 */
fs_ensure_sys_get_temp_dir_available();

/**
 * Get the session ID of the current session.
 */
function fs_get_session_id()
{
	global $FS_SESSION;
	if (isset($FS_SESSION))
	{
		return $FS_SESSION['sid'];
	}
	return false;
}

/**
 * initializes the sesssion.
 * if $sid is not supplied to the function (or if its null), the function will create a fresh session.
 * if $sid is supplied, the function will attempt to load the session from the storage.
 * returns : true if the session was initialized, false in case of an error.
 */
function fs_session_start($sid = null, $silent_test = false)
{
	$session_initialized = fs_initialize_session_dir($silent_test);
	if ($session_initialized !== true) return $session_initialized;
	
	global $FS_SESSION;
	if (isset($FS_SESSION['sid']))
	{
		return true;
	}

	$dir = $GLOBALS['FS_TEMP_DIR'];
	if (empty($sid))
	{
		$sid = '';
		$tries = 10;
		do
		{
			$tries--;
			$rand = mt_rand();
			$now = microtime();
			$sid = md5($rand."_".$now);
			$fname = $dir."session_$sid";
			if (file_exists($fname)) continue;
			$handle = @fopen($fname, "w+");
			if ($handle === false )
			{
				return fs_session_die($silent_test, "Failed to open file $fname for writing");
			}
			else
			{
				break;
			}
		}
		while ($tries > 0);
		if ($handle) fclose($handle);

		if ($tries == 0)
		{
			// failed to start session.
			if (FS_SESSION_DEBUG) echo "Failed to start session in <b>$dir</b><br/>";
			return "Failed to start session in <b>$dir</b><br/>";
		}

		$session = array();
		$session['sid'] = $sid;
		$session['accessed'] = time();
		global $FS_CONTEXT;
		$session['context'] = $FS_CONTEXT;
		
		$GLOBALS['FS_SESSION'] = $session;
		// store the session now,
		// to make sure its already available to sub-scripts that attempt to
		// access the session information before this script has terminated.
		return fs_store_session();
	}
	else // need to load existing session.
	{
		// garbage collect first.
		$gc = fs_session_gc();
		if (is_string($gc)) return $gc;
		$file = "$dir/session_$sid";
		if (file_exists($file))
		{
			$handle = @fopen($file,"r");
			if ($handle != false)
			{
				$fresh = false;		
				$str = @fgets($handle);
				fclose($handle);
				if ($str != false)
				{
					$session = unserialize($str);
					$accessed = isset($session['accessed'])? (int)$session['accessed'] : 0;
					$fresh = time() - $accessed < SESSION_TIMEOUT;
					if ($fresh)
					{
						$GLOBALS['FS_SESSION'] = $session;
					}
				}
				return $fresh;
			}
			else
			{
				if (FS_SESSION_DEBUG) echo "Error opening session file $file<br/>";
				return "Error opening session file $file";
			}
		}
		else
		{
			if (FS_SESSION_DEBUG) echo "Session file not found : $file<br/>";
			return false;
		}
	}
}

/**
 * Stores a session object, called automatically when the script processing is complete.
 */
function fs_store_session()
{
	if (isset($GLOBALS['FS_SESSION']))
	{
		$session_initialized = fs_initialize_session_dir(true);
		if ($session_initialized !== true) return $session_initialized;
		global $FS_CONTEXT;
		$session = $GLOBALS['FS_SESSION'];
		$session['context'] = $FS_CONTEXT; 

		$session['accessed'] = time();
		$sid = $session['sid'];
		$dir = $GLOBALS['FS_TEMP_DIR'];
		$handle = @fopen("$dir/session_$sid","w+");
		fputs($handle,serialize($session));
		fclose($handle);
	}
	return true;
}

/**
 * Garbage collect stale session.
 * this is called automatically and perform its operation at most every GC_INTERVAL_SECONDS.
 */
function fs_session_gc()
{
	$session_initialized = fs_initialize_session_dir(true);
	if ($session_initialized !== true) return $session_initialized;
		
	$last_gc = 0;
	$dir = $GLOBALS['FS_TEMP_DIR'];
	$fname = "$dir/last_gc";
	if (file_exists($fname))
	{
		$f = file($fname);
		if (isset($f[0]) && is_numeric($f[0])) $last_gc = (int)$f[0];
	}

	if (time() - $last_gc > GC_INTERVAL_SECONDS)
	{
		//echo "running gc</br>";
		$dh  = opendir($dir);
		while (false !== ($filename = readdir($dh)))
		{
			if ($filename != 'last_gc' && $filename != '.' && $filename != '..')
			{
				$rotten = false;
				
				$fn = "$dir/$filename";
				$fd = @fopen($fn, "r");
				if ($fd == false) 
				{
					if (FS_SESSION_DEBUG) echo "Error opening $fn<br/>";
					// error opening the file, assume invalid and (try to) gc it.
					$rotten = true;
				}
				else
				{
					$str = fgets($fd);
					fclose($fd);
					if ($str == false) 	
					{
						// error reading the  file, assume invalid and gc it.
						$rotten = true;	
					}
					else
					{
						$session = @unserialize($str);
						
						if ($session != false)
						{
							$accessed = (int)$session['accessed'];
							//echo "elapsed " . (time() - $accessed) . "<br/>";
							$rotten = time() - $accessed >= SESSION_TIMEOUT;
						}
						else
						{
							if (FS_SESSION_DEBUG) echo "Error unserialzing $fn<br/>";
							// bad file.
							$rotten = true;
						}
					}
				}

				
				if ($rotten)
				{
					if (FS_SESSION_DEBUG) echo "Session GC: unlinking $filename</br>";
					if(!@unlink($fn))
					{
						if (FS_SESSION_DEBUG) echo "Error unlinking $fn<br/>";
					}
				}
			}
		}

		if (fs_is_writable_and_readable($fname))
		{
			$fd = fopen($fname,"w+");
			fputs($fd,time());
			fclose($fd);
		}
		else
		{
			return "$fname is not writeable by PHP user";
		}
	}
}

/**
 * WARNING: This is one tricky function from hell.
 * DO NOT TOUCH if you are not 100% sure you know what you are doing, many people have died to get this function to where it is today.
 */
function fs_initialize_session_dir($silent_test = false)
{
	// only detect the temp dir once.
	if (isset($GLOBALS['FS_TEMP_DIR'])) return true;
	
	$help_url = "http://firestats.cc/wiki/ErrorInitializingSessionsDir";
	$text = sprintf("<h3>Error initializing sessions directory, read <a href='$help_url'>this</a> for help</h3><br/><span style='color:red'>%%s<span>");

	// user may override the detection of temp dir with FS_TEMP_DIR
	if (defined("FS_TEMP_DIR"))
	{
		$temp_dir = FS_TEMP_DIR;
	}
	else
	{
		$temp_dir = sys_get_temp_dir();
	}
	
	$home_temp_dir = dirname(dirname(__FILE__));
	$home_temp_dir .= "/".SESSIONS_DIR;

	// if FS_TEMP_DIR is NOT defined
	// AND one the following coditions is true, then try to use sessions directory under firestats directory:
	// 1. home_temp exists 
	// 2. can't detect temp directory 
	// 3. php is running in safe mode 
	// 4. temp is not writable.
	if(!defined("FS_TEMP_DIR") && 
		(is_dir($home_temp_dir) || 
		ini_get('safe_mode') == 1 ||
		$temp_dir === false || 
		!fs_is_dir_writable_and_readable($temp_dir)))
	{
		// sessions dir not found?
		// we require that the user create the directory because
		// if we create it the user will not be able to delete it.
		if (!is_dir($home_temp_dir)) 
		{
			return fs_session_die($silent_test, sprintf($text,"Directory ,'<b>$home_temp_dir</b>' does not exist, please create it"));
		}
		
		$temp_dir = $home_temp_dir;
	}
	else // temp directory exists, normal flow.
	{
		// make sure the dir ends with /
		fs_fix_dir1($temp_dir);
		
		// if FS_TEMP_DIR is not defined, append fs_sessions to the temp dir. 
		if (!defined("FS_TEMP_DIR"))
		{
			$temp_dir .= SESSIONS_DIR;	
			if (!is_dir($temp_dir)) // sessions dir not found inside temp dir, attempt to create it.
			{
				if (!mkdir($temp_dir, 0700))
				{
					return fs_session_die($silent_test, sprintf($text,"Failed to create '<b>$temp_dir</b>'"));
				}
			}
		}
		else 
		{
			// just make sure the directory the user defined exists.
			if (!is_dir($temp_dir)) 
			{
				return fs_session_die($silent_test, sprintf($text,"Directory ,'<b>$temp_dir</b>' does not exist"));
			}
		}
		
	}

	// make sure the dir ends with /
	fs_fix_dir1($temp_dir);

	if (!fs_is_dir_writable_and_readable($temp_dir))
	{
		return fs_session_die($silent_test, sprintf($text,"Directory ,'<b>$temp_dir</b>' is not writable or readable by the PHP user"));
	}
	
	// unfortunatelly, not all systems has posix_getuid, also - this cause problems when safe_mode is turned on.
	// if we run on a system that does not have it, and also happens to
	// have multiple (system) users accessing firestats, we are screwed. 
	if (CREATE_USER_DIR == true && function_exists("posix_getuid") && ini_get('safe_mode') != 1)
	{
		$temp_dir .= posix_getuid();
		// make sure the dir ends with /
		fs_fix_dir1($temp_dir);
	}
	
	if (!@file_exists($temp_dir) && !mkdir($temp_dir, 0700))
	{
		return fs_session_die($silent_test, sprintf($text,"Failed to create '<b>$temp_dir</b>'"));
	}
		
	$GLOBALS['FS_TEMP_DIR'] = $temp_dir;
	
	// create an index.html inside the sessions dir to prevent directory listing
	// if this failes it's not important because it means session files creation will also fail, so there is nothing to hide.
	if (!@file_exists($temp_dir."index.html"))
	{
		$handle = @fopen($temp_dir."index.html","w+");
		if ($handle !== false)
		{
			@fputs($handle,"Move along, nothing to see here.");
			@fclose($handle);
		}
	}
	
	return true;
}

function fs_ensure_sys_get_temp_dir_available()
{
	if ( !function_exists('sys_get_temp_dir') )
	{
		// Based on http://www.phpit.net/
		// article/creating-zip-tar-archives-dynamically-php/2/
		function sys_get_temp_dir()
		{
			// Try to get from environment variable
			if ( !empty($_ENV['TMP']) )
			{
				return realpath( $_ENV['TMP'] );
			}
			else if ( !empty($_ENV['TMPDIR']) )
			{
				return realpath( $_ENV['TMPDIR'] );
			}
			else if ( !empty($_ENV['TEMP']) )
			{
				return realpath( $_ENV['TEMP'] );
			}
			// Detect by creating a temporary file
			else
			{
				// Try to use system's temporary directory
				// as random name shouldn't exist
				$temp_file = @tempnam( md5(uniqid(rand(), TRUE)), '' );
				if ( $temp_file )
				{
					$temp_dir = realpath( dirname($temp_file) );
					if (!@unlink( $temp_file )) return FALSE;
					return $temp_dir;
				}
				else
				{
					return FALSE;
				}
			}
		}
	}
}

function fs_is_dir_writable_and_readable($dir)
{
	fs_fix_dir1($dir);
	return fs_is_writable_and_readable($dir); 
}
function fs_is_writable_and_readable($path) 
{
	//will work in despite of Windows ACLs bug
	//NOTE: use a trailing slash for folders!!!
	//see http://bugs.php.net/bug.php?id=27609
	//see http://bugs.php.net/bug.php?id=30931

	if ($path{strlen($path)-1}=='/') // recursively return a temporary file path
		return fs_is_writable_and_readable($path.uniqid(mt_rand()).'.tmp');
	else if (is_dir($path))
		return fs_is_writable_and_readable($path.'/'.uniqid(mt_rand()).'.tmp');
	// check tmp file for read/write capabilities
	$f = @fopen($path, 'w+');
	if ($f === false)
		return false;

	$res = fputs($f,'test');
	if ($res === false)
	{
		fclose($f);
		return false;
	}

	$data = file_get_contents($path);
	if ($data === false)
	{
		fclose($f);
		return false;
	}

	if ('test' !== $data)
	{
		fclose($f);
		return false;
	}

	fclose($f);
	@unlink($path);
		
	return true;
}


function fs_resume_existing_session()
{
	$sid = null;
	if (isset($_REQUEST['sid']) && !empty($_REQUEST['sid'])) 
	{
		$sid = $_REQUEST['sid'];
	}
	else
	if (isset($_COOKIE['FS_SESSION_ID']))
	{
		$sid = $_COOKIE['FS_SESSION_ID'];
	}
	else
		return "sid not specified";
	$res = fs_session_start($sid);
	global $FS_SESSION;
	if (is_bool($res) && $res)
	{
		global $FS_CONTEXT;
		$FS_CONTEXT = $FS_SESSION['context'];
		return true;
	}
	else
	{
		if (is_string($res))
		{
			return $res;
		}
		else
		{
			return "Session expired";
		}
	}
}

function fs_session_die($silent_test, $msg)
{
	if ($silent_test) 
		return $msg;
	else
		die($msg);
}

function fs_fix_dir1(&$dir)
{
	// make sure the dir ends with /
	$last = substr($dir, strlen($dir) - 1 );
	if ($last != "/" && $last != '\\') $dir .= "/";
	return $dir;
}
?>
