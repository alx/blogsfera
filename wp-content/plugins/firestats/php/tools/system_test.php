<?php
define('FS_NO_SESSION', true);
require_once(dirname(dirname(__FILE__)).'/init.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<title>FireStats test page</title>
<style type="text/css">

	.warning {
		background: #fff287;
		border: 1px solid #c69;
		margin: 1em 5% 10px;
		padding: 0 1em 0 1em;
	}
	
	.fatal {
		background: #ffb9b9;
		border: 1px solid #c69;
		margin: 1em 5% 10px;
		padding: 0 1em 0 1em;
	}
	
	.info {
		background: #c3ffb7;
		border: 1px solid #c69;
		margin: 1em 5% 10px;
		padding: 0 1em 0 1em;
	}
	</style>
</head>
<body>
<div id="firestats"><br />
<h2>FireStats <?php echo FS_VERSION?> system test</h2>
<div class="fs_body width_margin">

<table border='1'>
	<tr style='background:#6bb4ff'>
		<th width="25%">Test name</th>
		<th width="25%">Result</th>
	</tr>
	<?php run_tests()?>
   
	<tr>
		<td>Ajax test</td>
		<td id="js_injection_result">Testing...</td>
	</tr>
	<tr  id="injection_tr" style="display:none">
		<td>
		   	<table border="0">
				<tr id="injection_res">
					<td></td>
				</tr>
		   	</table>
		</td>
	</tr>
</table>

<script type="text/javascript"	src='<?php echo fs_url('js/prototype.js')?>'></script> 
	<script	type="text/javascript">
//<![CDATA[

	function timedOut()
	{
		var result = $('js_injection_result');
		var msg = "Timed out";
		var html = "<td class='fatal'>fatal</td><td>"+msg+"</td>";
		$('injection_res').innerHTML = html;
		
		result.innerHTML = "Failed";
		result.className = 'fatal';
		$('injection_tr').style.display = '';
		var msg = "Timed out";
		var html = "<td class='fatal'>fatal</td>"+
				"<td>"+msg+"</td>";
		$('injection_res').innerHTML = html; 
		 
	}
	messageTimerID = setTimeout("timedOut()", 10000);
	var ajaxUrl = "<?php echo fs_js_url('js/firestats.js.php',"test")?>";
	var myAjax = new Ajax.Request(
	ajaxUrl,
	{
		method: 'get', 
		parameters: "", 
		onComplete: function(response)
		{
			clearTimeout(messageTimerID);
			var result = $('js_injection_result');
			if (response.responseText != "")
			{
				result.innerHTML = "Failed";
				result.className = 'fatal';
				$('injection_tr').style.display = '';
				var msg = "JavaScript injection detected, the following content was injected by an unknown component on your server:<br/><b>" + response.responseText + "</b>";
				
				var html = "<td class='fatal'>fatal</td>"+
							"<td>"+msg+"</td>";
				
				$('injection_res').innerHTML = html; 
			}
			else
			{
				result.innerHTML = "Passed";
				result.className = 'info';
			}
		}	
	});
//]]>
</script></div>
</div>
</body>
</html>
<?php

function addTest(&$tests, $name, $func)
{
	$test = new stdClass();
	$test->name = $name;
	$test->func = $func;
	$tests[] = $test;
}

function run_tests()
{
	$tests = array();
	addTest($tests, "PHP Version", "fs_systest_php_version");
	addTest($tests, "Files integrity test", "fs_systest_files_integrity");
	addTest($tests, "Database status", "fs_systest_database_test");
	addTest($tests, "Session", "fs_systest_session");

	foreach($tests as $test)
	{
		$f = $test->func;
		fs_systest_done($test->name, $f());
	}
}

function fs_systest_session()
{
	require_once(FS_ABS_PATH.'/php/session.php');
	global $FS_SESSION;
	unset($FS_SESSION);
	$errors = array();
	$res = fs_initialize_session_dir(true);
	if ($res !== true)
	{
		$errors[] = fs_systest_error("fatal",sprintf("Error initializing session directory: %s",$res));
	}
	else
	{
		$ok = fs_session_start(null, true);
		if ($ok !== true)
		{
			$errors[] = fs_systest_error("fatal","Error creating test session");
		}
		else
		{
			global $FS_SESSION;
			$sid = $FS_SESSION['sid'];
			unset($GLOBALS['FS_SESSION']);
			$ok = fs_session_start($sid,true);
			if ($ok !== true)
				$errors[] = fs_systest_error("fatal","Error restoring session : $ok");
		}
	}
	return $errors;
}

function fs_systest_php_version()
{
	$errors = array();
	$version = phpversion();
	$ver = explode( '.', PHP_VERSION );
	$ver_num = $ver[0] . $ver[1] . $ver[2];
	if ($ver_num < 442)
	{
		$errors[] = fs_systest_error("fatal",sprintf("Your PHP Version (%s) is older than 4.4.2",$version));
	}
	else
	if ($ver_num == 521)
	{
		$errors[] = fs_systest_error("warning","PHP Version is 5.2.1, if your PHP is compilied with 64bit, it has a bug that prevents IP to country from functioning correctly");
	}

	return $errors;
}

function fs_systest_files_integrity()
{
	$errors = array();
	$md5_list = FS_ABS_PATH."/md5.lst";
	if (file_exists($md5_list))
	{
		$files = file($md5_list);
		foreach($files as $file)
		{
			$f = explode(" ", $file);
			$expected_md5 = $f[0];
			$name = trim($f[1]);
			$fname = FS_ABS_PATH."/$name";
			if (@file_exists($fname))
			{
				if (!is_readable($fname))
				{
					$errors[] = fs_systest_error("fatal",$name, "Read access denied");
				}
				else
				{
					$actual_md5 = md5_file($fname);
	
					if ($actual_md5 != $expected_md5)
					{
						$errors[] = fs_systest_error("warning",$name, "altered");
					}
				}
			}
			else
			{
				$errors[] = fs_systest_error("fatal",$name,"not found");
			}
		}
	}
	else
	{
		$errors[] = fs_systest_error("fatal",$md5_list,"not found");
	}

	return $errors;
}


function fs_systest_database_test()
{
	require_once(FS_ABS_PATH.'/php/db-common.php');
	$db = fs_get_db_status();
	$errors = array();
	if ($db['status'] == FS_DB_NOT_CONFIGURED)
	{
		$errors[] = fs_systest_error("fatal","Database is not configured");
	}
	else
	{
		// database is configured, we can do some serious tests.
		$mysql_version = fs_mysql_version();
		if (!fs_mysql_newer_than("4.0.17"))
		{
			$errors[] = fs_systest_error("fatal","Your MySQL database version is <b>$mysql_version</b>, FireStats requires <b>4.0.17</b> or newer");
		}
		else
		if (!fs_mysql_newer_than("4.1.14"))
		{
			$errors[] = fs_systest_error("warning","Your MySQL database version is <b>$mysql_version</b>, Some features of FireStats reqruires <b>4.1.14</b> or newer");
		}

		if ($db['status'] != FS_DB_VALID && $db['status'] != FS_DB_NOT_CONFIGURED)
		{
			$errors[] = fs_systest_error("fatal",fs_get_database_status_message($db));
		}
		else
		{
			$fsdb = &fs_get_db_conn();
			$tables = fs_get_tables_list();
			$except = array(fs_pending_date_table()); // don't check this one for InnoDB.
			$res = $fsdb->get_results("SHOW TABLE STATUS");
			if ($res === false)
			{
				$errors[] = fs_systest_error("fatal","Error querying database");
			}
			else
			{
				$bad_tables = "";
				$found = array();
				foreach($res as $t)
				{
					if (in_array($t->Name, $tables) === true)
					{
						$found[$t->Name] = true;
						if (in_array($t->Name, $except) === false)
						{
							if ((isset($t->Engine) && $t->Engine != "InnoDB") || (isset($t->Type) && $t->Type != "InnoDB"))
							{
								if ($bad_tables == "")
									$bad_tables .= $t->Name;
								else
									$bad_tables .= ", ".$t->Name;
							}
						}
					}
				}
				
				foreach ($tables as $t)
				{
					if (!(isset($found[$t]) && $found[$t]))
					{
						$errors[] = fs_systest_error("fatal","missing table <b>$t</b>");
					}
				}

				if ($bad_tables != "")
				{
					$errors[] = fs_systest_error("fatal","Some of your tables are not using the InnoDB engine, which is required by FireStats. wierd things may happen <b>($bad_tables)</b>");
				}
			}
		}
	}
	return $errors;
}

function fs_systest_done($testName, $errors)
{
	if (count($errors) == 0)
	{
		echo "<tr>
		<td>$testName</td>
	 	<td class='info'>Passed</td>
	 </tr>";		
	}
	else
	{
		echo "<tr>
		<td>$testName</td>
	 	<td class='fatal'>Failed</td>
	 </tr>
	<tr><td colspan='2'>
	<table border='1' style ='width:100%'>";
		foreach($errors as $err)
		{
			?>
<tr>
	<td class="<?php echo $err->severity?>"><?php echo $err->severity?></td>
<td><?php echo $err->col1?></td>
<?php if (!empty($err->col2)) {?>
<td><?php echo $err->col2?></td>
<?php }?>
</tr>
	<?php
}
echo "</table></td></tr>";
}
}


function fs_systest_error($severity,$col1, $col2 = "")
{
	$e = new stdClass();
	$e->severity = $severity;
	$e->col1 = $col1;
	$e->col2 = $col2;
	return $e;
}
?>
