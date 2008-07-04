<?php

if (!isset($GLOBALS['fs_initialized']))
{
	require_once(dirname(__FILE__).'/constants.php');
	$fs_conf = dirname(dirname(__FILE__)).'/conf.php';
	if (file_exists($fs_conf)) require_once($fs_conf);
	
	require_once(dirname(__FILE__).'/session.php');
	if (!defined('FS_NO_SESSION'))
	{
		$res = fs_resume_existing_session();
		if ($res !== true)
		{
			global $FS_SESSION_ERROR;
			$FS_SESSION_ERROR = $res;
		}
	}

	if (file_exists(dirname(__FILE__).'/../demo'))
	{
	    define('DEMO',true);
	}
	
	if (!defined('FS_COMMIT_STRATEGY'))
	{
		define('FS_COMMIT_STRATEGY', FS_COMMIT_IMMEDIATE);
	}
	
	global $FS_CONTEXT;
	if (!isset($FS_CONTEXT))
	{
		detect_context();
	}
	
	// if we run in wordpress, load its config to gain access to the api and configuration
	if (fs_in_wordpress())
	{
		global $FS_CONTEXT;
		$config_path = $FS_CONTEXT['WP_PATH'];
		require_once($config_path);
	}
	
	if (!defined('FS_DEFAULT_LANG'))
	{
		define('FS_DEFAULT_LANG','en_US');
	}	
	
	require_once(dirname(__FILE__).'/auth.php');
	require_once(dirname(__FILE__).'/db-common.php');
	require_once(dirname(__FILE__).'/fs-gettext.php');
	require_once(dirname(__FILE__).'/db-config-utils.php');
	require_once(dirname(__FILE__).'/plugins.php');
	
	
	// will trigger the initialization of the database connection
	$fsdb = &fs_get_db_conn();
	require_once(dirname(__FILE__).'/utils.php');
	
	fs_init_language();
	fs_add_action("authenticated", "fs_init_language");
	
	fs_register_incremental_process('rebuild_cache', 'fs_rebuild_cache_calc_max', 'fs_rebuild_cache', 'fs_rebuild_cache_desc',array(FS_ABS_PATH.'/php/rebuild-db.php'));
	fs_register_incremental_process('rebuild_countries', 'fs_rebuild_countries_calc_max', 'fs_rebuild_country_codes', null,array(FS_ABS_PATH.'/php/ip2country.php'));
	fs_register_incremental_process('recalculate_search_engine_terms', 'fs_recalculate_search_engine_terms_calc_max', 'fs_recalculate_search_engine_terms', null,array(FS_ABS_PATH.'/php/searchengines.php'));

	$GLOBALS['fs_initialized'] = true;
}


function fs_init_language()
{
	global $FS_LANG;
	global $fs_gettext;
	
	$fsdb = &fs_get_db_conn();
	$current_lang = null;
	if ($fsdb->is_connected()) 
	{
		$current_lang = fs_get_option('current_language');
	}

	if (empty($current_lang)) $current_lang = FS_DEFAULT_LANG;
	if ($FS_LANG == $current_lang)	return;

	$transfile = FS_ABS_PATH.'/i18n/firestats-'.$current_lang.'.po';
	if (file_exists($transfile))
	{
		$fs_gettext = new fs_gettext($transfile);
	}
	else
	{
		$fs_gettext = new fs_gettext();
	}
	$FS_LANG = $current_lang;
}

function fs_lang_dir()
{
	global $FS_LANG_DIR;
	if (!isset($FS_LANG_DIR))
	{
		global $FS_LANG;
		if ($FS_LANG == 'he_IL' || $FS_LANG == 'ar_AR')
		{
			$FS_LANG_DIR = "rtl";
		}
		else
		{
			$FS_LANG_DIR = "ltr";
		}
	}
	return $FS_LANG_DIR;
}

function fs_is_demo()
{
	return defined('DEMO') && DEMO;
}

/**
 * There are two methods to install FireStats:
 * 1. Standalone: where its installed somewhere on the server (independent) and  serves
 *    a few systems on the same machine.
 *    For example: it can serve several blogs and a trac site.
 * 2. Hosted: as a subsystem of another system, like Wordpress.
 *	  In this mode, FireStats is actually installed inside the hosting system, and 
 *    its also uses the host database and database configuration.
 */
function fs_is_hosted()
{
	if (function_exists('fs_full_installation'))
	{
		return fs_full_installation();
	}
	else
	{
		return false; // default to standalone
	}
}

function fs_is_standalone()
{
	return !fs_is_hosted();
}

function fs_in_wordpress()
{
	global $FS_CONTEXT;
	return $FS_CONTEXT['TYPE'] == 'WORDPRESS';
}

// this is a pretty ugly function that tries to autoamtically detect and set the context
// this is only need to be called in special circumstences (like system test)
function detect_context()
{
	global $FS_CONTEXT;
	$FS_CONTEXT = array();
	$wpc = fs_priv_get_wp_config_path();
	if ($wpc != false)
	{
		$FS_CONTEXT['TYPE'] = 'WORDPRESS';
		$FS_CONTEXT['WP_PATH'] = $wpc;
	}
	else
	{
		$FS_CONTEXT['TYPE'] = 'GENERIC';
	}
}

function fs_priv_get_wp_config_path()
{
    $base = dirname(__FILE__);
    $path = false;

    if (@file_exists(dirname(dirname(dirname(dirname($base))))."/wp-config.php"))
		$path = dirname(dirname(dirname(dirname($base))))."/wp-config.php";
    else
    if (@file_exists(dirname(dirname(dirname($base)))."/wp-config.php"))
		$path = dirname(dirname(dirname($base)))."/wp-config.php";
    else
        $path = false;

    if ($path != false)
    {
        $path = str_replace("\\", "/", $path);
    }
    return $path;
}

?>
