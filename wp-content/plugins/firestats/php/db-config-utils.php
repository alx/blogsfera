<?php

require_once(dirname(__FILE__).'/utils.php');

define('FS_DB_CONFIG_UNAVAILABLE'	, 0);
define('FS_DB_CONFIG_FILE'			, 1);
define('FS_DB_CONFIG_WORDPRESS'		, 2);

function fs_get_db_response($error_code, $msg = null)
{
	$res['status'] = 'error';
	$res['color'] = 'red';

	switch($error_code)
	{
		case 'access_denied':
			$res['message'] = $msg;
		break;
		case 'db_access_error':
			$res['message'] = fs_r('Error accessing database')." : ".$msg;
		break;
		case 'db_connect_error':
			$res['message'] = fs_r('Error connecting to database')." : ".$msg;
		break;
		case 'read_only_config':
			$res['message']= fs_r('Cannot write configuration file (read only file or directory)');
		break;
		case 'db_create_error':
			$res['message']= fs_r('Error creating database').":".$msg;
		break;
		case 'user_create_error':
			$res['message']= fs_r('Error creating user').":".$msg;
		break;
		case 'tables_create_error':
			$res['message']= fs_r('Error creating tables').":".$msg;
		break;
		case 'config_save_error':
			$res['message']= fs_r('Error saving configuration').":".$msg;
		break;
		case 'password_not_specified':
			$res['message']= fs_r('Password not specified');
		break;
		case 'wrong_password':
			$res['message']= fs_r('Wrong password');
		break;
		case 'db_missing':
			$res['status']='database_missing';
			$res['color'] = 'orange';
			$res['message'] = sprintf("Database named %s does not exist",'<b>'.$msg.'</b>');
		break;
		case 'tables_missing':
			$res['status']='tables_missing';
			$res['color'] = 'orange';
			$res['message']= fs_r('FireStats tables are not installed');
		break;
		case 'db_installed':
			$res['status']='ok';
			$res['color'] = 'blue';
			$res['message']= fs_r('FireStats is installed');
		break;
		case 'other_db_detected':
			$res['status']='other_db_detected';
			$res['color'] = 'orange';
			$res['message']= fs_r('Another FireStats database detected');
		break;
		case 'db_created':
			$res['status'] = 'ok';
			$res['color'] = 'blue';
			$res['message'] = fs_r('New database created successfuly');
		break;
		default:
			$res['message'] = sprintf(fs_r('Unexpected error code %s'),$error_code);
	}

	return $res;
}

function fs_test_db_connection($host, $username, $password, $db_name, $table_prefix)
{
	if (!fs_is_admin()) return fs_get_db_response("access_denied","Access denied : fs_test_db_connection");
	
	require_once(FS_ABS_PATH."/lib/ezsql/shared/ez_sql_core.php");
	require_once(FS_ABS_PATH."/lib/ezsql/mysql/ez_sql_mysql.php");

	$conn = new fs_ezSQL_mysql($username,$password, $db_name, $host);
	$conn->hide_errors();

	if (!$conn->connect($username,$password,$host, false))
	{
		return fs_get_db_response('db_connect_error', $conn->last_error); 
	}

	if (($mysql_version = $conn->get_var("select version()")) === false)
	{
		return fs_get_db_response('db_access_error', $conn->last_error); 
	}


	if ($conn->query("use `$db_name`") === false)
	{
		return fs_get_db_response('db_missing', $db_name);
	}

	if ($conn->query("show tables") === false)
	{
		return fs_get_db_response('db_access_error', $conn->last_error); 
	}

    $version_table = $table_prefix.'firestats_version';
		
	$sql = "SHOW TABLES LIKE '$version_table'";
    $results = $conn->query($sql);
    if ($results === FALSE)
    {
		return fs_get_db_response('db_access_error', $conn->last_error); 
    }

    if ($results == 0)
	{
		return fs_get_db_response('tables_missing'); 
	}
	
	fs_load_config();
	if(!fs_same_config($host, $username, $password, $db_name, $table_prefix))
	{	
		return fs_get_db_response('other_db_detected'); 
	}
	
	return fs_get_db_response('db_installed'); 
}

function fs_same_config($host, $user, $pass, $dbname, $table_prefix)
{
	global $fs_config;

	return	$fs_config['DB_NAME'] == $dbname &&
			$fs_config['DB_PREFIX'] == $table_prefix &&
			$fs_config['DB_PASS'] == $pass &&
			$fs_config['DB_HOST'] == $host &&
			$fs_config['DB_USER'] == $user;
}


function fs_save_config_file($host, $user, $pass, $dbname, $table_prefix)
{
	if (!fs_is_admin()) return "Access denied : fs_save_config_file";

    ob_start();
    $file = fopen(dirname(__FILE__)."/fs-config.php", "w");
    $output = ob_get_clean();
    if ($file === false)
    {
		return $output;
    }
    else
    {
		ob_start();
        $res = fwrite($file, fs_get_config($host, $user, $pass, $dbname, $table_prefix));
		$output = ob_get_clean();
        fclose($file);
		if ($res === false)
		{
			return $output;
		}
    }
}

function fs_get_config($host, $user, $pass, $dbname, $table_prefix)
{
	if ($host == '') $host = 'localhost';
	return 
"<?php
// Auto generated file, edit at your own risk.
// To change settings, use FireStats database tab
\$fs_config['DB_NAME']='$dbname';
\$fs_config['DB_PREFIX']='$table_prefix';
\$fs_config['DB_USER']='$user';
\$fs_config['DB_PASS']='$pass';
\$fs_config['DB_HOST']='$host';
\$GLOBALS['fs_config'] = \$fs_config;
?>
";
}

function fs_create_new_database($host, $admin_user, $admin_pass, $user, $pass, $dbname, $table_prefix)
{
	if (!fs_is_admin()) return fs_get_db_response("access_denied","Access denied : fs_create_new_database");

 	require_once dirname(__FILE__)."/ezsql/mysql/ez_sql_mysql.php";
	require_once dirname(__FILE__)."/ezsql/shared/ez_sql_core.php";
	require_once dirname(__FILE__)."/db-setup.php";

	if ($user == '' || $pass == '')
	{
		$user = $admin_user;
		$pass = $admin_pass;
	}

	$conn = new fs_ezSQL_mysql($admin_user,$admin_pass, $dbname, $host);
	$conn->hide_errors();
		
	if (!fs_config_writeable())
	{
		return fs_get_db_response('read_only_config');
	}

	if (!$conn->connect($admin_user,$admin_pass,$host, false))
	{
		return fs_get_db_response('db_connect_error', $conn->last_error);
	}

	$db = $conn->query("SHOW DATABASES LIKE '$dbname'");
	if ($db=== false)
	{
		return fs_get_db_response('db_access_error', $conn->last_error);
	}
	$existed = $db == 1;

	if ($conn->query("CREATE DATABASE IF NOT EXISTS `$dbname`") === false)
	{
		return fs_get_db_response('db_create_error', $conn->last_error);
	}
	
	if ($conn->query("GRANT SELECT, INSERT, UPDATE, 
							DELETE, CREATE, ALTER, INDEX, 
							DROP, CREATE TEMPORARY TABLES 
							ON `$dbname`.*
							TO `$user` IDENTIFIED BY '$pass';") === false)
	{
		$last_error = $conn->last_error;
		if (!$existed)
		{
			$conn->query("DROP DATABASE `$dbname`");
		}

		return fs_get_db_response('user_create_error', $last_error);
	}

    ob_start();
    $install_res = fs_install_into($user,$pass,$dbname,$host);
    $output = ob_get_clean();
	
    if (!$install_res)
    {
		$last_error = $conn->last_error;
		if (!$existed)
		{
			$conn->query("DROP DATABASE `$dbname`");
		}

		return fs_get_db_response('tables_create_error', $last_error.($output ? ', Output: '.$output : ''));
    }

	$r = fs_save_config_file($host,$user,$pass,$dbname,$table_prefix);
	if ($r != '')
	{
		if (!$existed)
		{
			$conn->query("DROP DATABASE `$dbname`");
		}
		return fs_get_db_response('config_save_error', $conn->last_error);
	}

	$conn->disconnect();

	return fs_get_db_response('db_created', $conn->last_error);
}

function fs_config_writeable()
{
	return 	is_writeable(dirname(__FILE__)) || 
			is_writeable(dirname(__FILE__).'/fs-config.php');
}

function fs_get_config_source_desc()
{
   	$db_config_type = fs_get_db_config_type();
	switch ($db_config_type)
	{
		case FS_DB_CONFIG_UNAVAILABLE:
		$cfg_source = fs_r('Not configured');
		break;
		case FS_DB_CONFIG_FILE:
		$cfg_source = fs_r('FireStats configuration file');
		break;
		case FS_DB_CONFIG_WORDPRESS:
		$cfg_source = fs_r('Wordpress configuration file');
		break;
	}
    return $cfg_source;
}


function fs_get_db_config_type()
{
	if (file_exists(dirname(__file__)."/fs-config.php")) return FS_DB_CONFIG_FILE;
	if (fs_in_wordpress() && fs_full_installation()) return FS_DB_CONFIG_WORDPRESS;
	return FS_DB_CONFIG_UNAVAILABLE;
}


function fs_should_show_use_wp_button()
{
    $in_wordpress = fs_in_wordpress() && fs_full_installation();
	$db_config_type = fs_get_db_config_type();
    return $in_wordpress && $db_config_type != FS_DB_CONFIG_WORDPRESS;
}



function fs_load_config()
{
	$db_config_type = fs_get_db_config_type();
	if ($db_config_type == FS_DB_CONFIG_FILE)
	{
		// note:
		// this is not require_once on purpose!
		// we need this to be included even if it was included before.
		require(dirname(__FILE__).'/fs-config.php');
	}
	else
	if ($db_config_type == FS_DB_CONFIG_WORDPRESS)
	{
		require_once(fs_get_wp_config_path());
		$fs_config['DB_NAME']=DB_NAME;

		if (!fs_is_wpmu())
		{
			global $table_prefix;
			$db_prefix = isset($table_prefix) ? $table_prefix : '';
		}
		else
		{
			global $wpmuBaseTablePrefix;	
			$db_prefix = isset($wpmuBaseTablePrefix) ? $wpmuBaseTablePrefix : '';
		}

		$fs_config['DB_PREFIX'] = $db_prefix;
		$fs_config['DB_USER'] =DB_USER;
		$fs_config['DB_PASS'] = DB_PASSWORD;
		$fs_config['DB_HOST'] = DB_HOST;
		$GLOBALS['fs_config'] = $fs_config;
	}
	else
	{
		// load default values
		$fs_config['DB_NAME'] = 'firestats';
		$fs_config['DB_PREFIX'] = '';
		$fs_config['DB_USER'] = '';
		$fs_config['DB_PASS'] = '';
		$fs_config['DB_HOST'] = 'localhost';
		$GLOBALS['fs_config'] = $fs_config;
	}
}
?>
