<?php
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/db-common.php');

/**
 * Checks if the there is a session cookie, and if so resume the session 
 */
function fs_resume_user_session()
{
	require_once(FS_ABS_PATH.'/php/session.php');
	$res = fs_resume_existing_session();
	if ($res !== true) 
	{
		return $res;
	}
	
	$authenticated = fs_current_user_id() !== false;
	if ($authenticated)
	{
		// raise authenticated event.
		// some initialization code may only happen after the user is authenticated.
		fs_do_action("authenticated");
		
	}
	return $authenticated;
}


function fs_current_user_id()
{
	$user = fs_get_current_user();
	if ($user === null || (isset($user->dummy) && $user->dummy)) return false;
	return $user->id;
}
/**
 * Checks if there is an authenticated user
 */
function fs_authenticated()
{
	return fs_get_current_user() !== null ;
}

function fs_get_current_user()
{
	global $FS_SESSION;
	if(empty($FS_SESSION)) return null;
	if(!empty($FS_SESSION['user'])) return $FS_SESSION['user'];
	return null;
}

function fs_is_user()
{
	return fs_check_sec_level(SEC_USER);
}

function fs_is_admin()
{
	return fs_check_sec_level(SEC_ADMIN);
}

function fs_can_use()
{
	return fs_is_user() || fs_is_admin();
}

function fs_check_sec_level($sec_level)
{
	global $FS_SESSION;
	if(empty($FS_SESSION)) return false;
	if(empty($FS_SESSION['user'])) return false;
	$user = $FS_SESSION['user'];
	return $user->security_level == $sec_level;
}

/**
 * Attempts to login the user
 * on success, creates a new session for the user and returns true.
 * on failure, return false. 
 */
function fs_login($username, $password, $pass_is_md5 = false)
{
	$fsdb = &fs_get_db_conn();
	if (!$fsdb->is_connected())
	{
		global $fs_config;
		return sprintf(fs_r("Error connecting to database (%s)"), $fs_config['DB_HOST']);
	}
	
	$username = $fsdb->escape($username);
	$password = $fsdb->escape($password);
	$users = fs_users_table();
	if ($pass_is_md5)
	{
		$pass = "$password";	
	}
	else
	{
		$pass = "MD5($password)";
	}
	
	$user = $fsdb->get_row("SELECT `id`,`username`,`email`,`security_level`  FROM `$users` WHERE `username` = $username AND `password` = $pass");
	if ($user === false)
	{
		return fs_db_error();
	}
	else
	if ($user !== null)
	{
		// this is used to indicate the user logged in (and was not invented by some plugin). only logged in users can logout.
		$user->logged_in = true;
		$res = fs_start_user_session($user);
		if ($res === false) return false;
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * Authenticate the current user as an admin.
 * this should only be used if there is currenty no admin in the database.
 */
function fs_dummy_auth()
{
	if (!fs_no_admin()) 
	{
		echo "Admin is already defined in the database";
		return;
	}
	
	$user = new stdClass();
	$user->dummy = true;
	$user->name = "Dummy admin";
	$user->security_level = SEC_ADMIN;
	$res = fs_start_user_session($user);
	if ($res) fs_store_session();
	if ($res === false) return false;
}

function fs_start_user_session($user)
{
	require_once(FS_ABS_PATH.'/php/session.php');
	$ok = fs_session_start();
	if ($ok !== true)
	{
		$msg = "Error starting session";
		if (is_string($ok)) $msg .= " :$ok";
		$msg .= "<br/>"; 
		echo $msg;
		return false;
	}
	
	global $FS_SESSION;
	$FS_SESSION['user'] = $user;
	fs_store_session();

	// user is null for dummy sessions (may be needed before login)
	if ($user != null)
	{
		// raise authenticated event.
		// some initialization code may only happen after the user is authenticated.
		fs_do_action("authenticated");
	}
	return true;
}

/**
 * returns true if there are no admin users in the users table.
 * this is an indication that an admin user need to be created.
 */
function fs_no_admin()
{
	$fsdb = &fs_get_db_conn();
	if (!$fsdb->is_connected()) 
	{
		// if database is not connected, we have no admin, right?
		return true;
	}
	$users = fs_users_table();
	$c = $fsdb->get_var("SELECT COUNT(`id`) FROM `$users` WHERE `security_level` = '1'");
	return (int)$c === 0;
}

function fs_create_user($username, $email, $password, $security_level)
{
	if (!fs_is_admin()) return "Access denied : fs_create_user";
	if (!fs_validate_email_address($email)) return fs_r("Invalid email address");
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$username = $fsdb->escape($username);
	$email = $fsdb->escape($email);
	$password = $fsdb->escape($password);
	$security_level = $fsdb->escape($security_level);
	
	$r = $fsdb->get_var("SELECT COUNT(*) FROM `$users` WHERE `username` = $username");
	if ($r === false) return fs_db_error();
	if ((int)$r > 0) return fs_r("A user with this name already exists");
	
	$r = $fsdb->get_var("SELECT COUNT(*) FROM `$users` WHERE `email` = $email");
	if ($r === false) return fs_db_error();
	if ((int)$r > 0) return fs_r("A user with this email address already exists");

	$sql = "INSERT INTO `$users` (`id` ,`username` ,`password` ,`email` ,`security_level`)VALUES (NULL , $username, MD5($password), $email, $security_level)";
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		return fs_db_error();
	}

	return true;
}

function fs_update_user($id,$username, $email, $password, $security_level)
{
	if (!fs_is_admin()) return "Access denied : fs_update_user";
	if (!fs_validate_email_address($email)) return fs_r("Invalid email address");
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$id = $fsdb->escape($id);
	$username = $fsdb->escape($username);
	$email = $fsdb->escape($email);
	$security_level = $fsdb->escape($security_level);
	
	$r = $fsdb->get_var("SELECT COUNT(*) FROM `$users` WHERE `username` = $username AND `id` != $id");
	if ($r === false) return fs_db_error();
	if ((int)$r > 0) return fs_r("A user with this name already exists");

	$r = $fsdb->get_var("SELECT COUNT(*) FROM `$users` WHERE `email` = $email AND `id` != $id");
	if ($r === false) return fs_db_error();
	if ((int)$r > 0) return fs_r("A user with this email address already exists");
	
	if (empty($password))
	{
		$sql = "UPDATE `$users` set `username`=$username,`email`=$email ,`security_level`=$security_level WHERE `id` = $id";
	}
	else
	{
		$password = $fsdb->escape($password);
		$sql = "UPDATE `$users` set `username`=$username,`password`=MD5($password),`email`=$email ,`security_level`=$security_level WHERE `id` = $id";
	}
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		return fs_db_error();
	}

	return true;
}

function fs_change_password($id, $username, $password)
{
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$id = $fsdb->escape($id);
	$username = $fsdb->escape($username);
	$password = $fsdb->escape($password);
	$user = $fsdb->get_row("SELECT `id`,`username`,`email`,`security_level`  FROM `$users` WHERE `username` = $username AND `id` = $id");
	if ($user === false)
	{
		return fs_db_error();
	}
	else
	if ($user === null)
	{
		return "fs_change_password: Unknown user"; // not translated
	}
	else
	{
		$allowed = fs_is_admin() || $user->id == fs_current_user_id();
		if (!$allowed)
		{
			return "Access denied: fs_change_password"; // not translated
		}
		else
		{
			$sql = "UPDATE `$users` set `password`=MD5($password) WHERE `username` = $username AND `id` = $id";
			$r = $fsdb->query($sql);
			if ($r === false)
			{
				return fs_db_error();
			}
			return true;
		}
	}
	
}

function fs_get_user_by_username_and_email($username, $email)
{
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$email = $fsdb->escape($email);
	$username = $fsdb->escape($username);
	$sql = "SELECT `id`,`username`,`email`,`security_level` FROM `$users` WHERE `username` = $username AND `email` = $email";
	$u = $fsdb->get_row($sql);
	if ($u === false) return fs_db_error();
	return $u;
}

function fs_delete_user($id)
{
	if (!fs_is_admin()) return "Access denied : fs_delete_user";
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$id = $fsdb->escape($id);
	$sql = "DELETE FROM `$users` WHERE `id`=$id";
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		return fs_db_error();
	}

	return true;
}
?>
