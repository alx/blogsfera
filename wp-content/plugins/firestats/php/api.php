<?php
/*
	This file contains FireStats external APIs.
	if no api function is called, the footprint of the api is minimal - 
	as the real code is only included when an API function is called.
*/
define ('FS_API','enabled');


// prevent FireStats from allocating a session if the API file is included directly
define ('FS_NO_SESSION','');
require_once(dirname(__FILE__)."/init.php");

/**
 * Site ID:
 * Some functions accepts a $site_id parameters which defined as follows:
 * - true  : to exclude all sites but the one in the sites_filter option.
 * - false : to include all sites.
 * - integer : site id to include (exclude all other sites).
 * 
 * URL:
 * Some function accepts an optional $url to get the hits for.
 * The URL should be exactly as recorded by FireStats.
 * note trailing slash and leading www. 
 * example: 'http://mysite.com/?p=123')
 * 
 * Unix time : Returns the current time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).
 * see http://php.net/time
 */

/*
	Returns the number of pages displayed in the specified time period.
	days_ago is an optional parameter which specifies how many days ago to start counting.
	if days_ago is not specified, the count will begin when you installed FireStats.
	site_id : see comment at the start of the file
	url: see comment at the start of the file
*/
function fs_api_get_page_views($days_ago = NULL, $site_id = true, $url = null)
{
	require_once(dirname(__FILE__).'/db-sql.php');
	$url_id = null;
	if ($url)
	{
		$url_id = fs_get_url_id($url);
		if ($url_id === null) return 0;
	}
	return fs_get_hit_count($days_ago,$site_id,$url_id);
}

/**
 * deprecated. use fs_api_get_page_views
 */
function fs_api_get_hit_count($days_ago = NULL, $site_id = true, $url = null)
{
	return fs_api_get_page_views($days_ago, $site_id, $url);
}

/**
	Returns the number of pages displayed in the specified time range
	$range_start : start of range in unix time. see comment at the start of the file  
	$range_end : end of range in unix time. see comment at the start of the file
	site_id : see comment at the start of the file
	url: see comment at the start of the file
 */
function fs_api_get_page_views_range($range_start, $range_end, $site_id = true, $url = null)
{
	require_once(dirname(__FILE__).'/db-sql.php');
	$url_id = null;
	if ($url)
	{
		$url_id = fs_get_url_id($url);
		if ($url_id === null) return 0;
	}
	return fs_get_page_views_range($site_id, true, $range_start, $range_end, $url_id);
}

/*
	Returns the number of unique hits in the specified time period.
	days_ago is an optional parameter which specifies how many days ago to start counting.
	if days_ago is not specified, the count will begin when you installed FireStats.
	url: see comment at the start of the file
*/
function fs_api_get_visits($days_ago = NULL, $site_id = true, $url = null)
{
	require_once(dirname(__FILE__).'/db-sql.php');
	$url_id = null;
	if ($url)
	{
		$url_id = fs_get_url_id($url);
		if ($url_id === null) return 0;
	}	
	return fs_get_unique_hit_count($days_ago, $site_id, $url_id);
}

/**
 * deprecated. use fs_api_get_visits
 */
function fs_api_get_unique_hit_count($days_ago = NULL, $site_id = true, $url = null)
{
	return fs_api_get_unique_hit_count($days_ago, $site_id, $url);
}


/**
	Returns the number of visits in the specified time range
	$range_start : start of range in unix time. see comment at the start of the file  
	$range_end : end of range in unix time. see comment at the start of the file
	site_id : see comment at the start of the file
	url: see comment at the start of the file
 */
function fs_api_get_visits_range($range_start, $range_end, $site_id = true, $url = null)
{
	require_once(dirname(__FILE__).'/db-sql.php');
	$url_id = null;
	if ($url)
	{
		$url_id = fs_get_url_id($url);
		if ($url_id === null) return 0;
	}
	return fs_get_unique_hit_count_range($site_id, true, $range_start, $range_end, $url_id);
}

/*
	Returns image tags of images representing the useragent
	3 Icons may be returned:
	* OS Icon
	* Browser Icon
	* PDA Icon (if the useagent is of a phone)

	To access the user agent of the current user in PHP use $_SERVER['HTTP_USER_AGENT']
*/ 
function fs_api_get_browser_and_os_images($useragent)
{
	require_once(dirname(__FILE__).'/browsniff.php');
	return fs_pri_browser_images($useragent);
}

/*
	Returns an image tag with the flag of the country this ip_address blonged to.
	if unknown, an empty string is returned.
*/
function fs_api_get_country_flag_image($ip_address)
{
	require_once(dirname(__FILE__).'/ip2country.php');
	$code = fs_ip2c($ip_address);
	if ($code != false) return fs_get_country_flag_url($code);
	else return '';
}

/*
	Returns a two characters country code of the country this ip address is belonged to.
	if unknown, false is returned.
*/
function fs_api_get_country_code($ip_address)
{
	require_once(dirname(__FILE__).'/ip2country.php');
	return fs_ip2c($ip_address);
}

/*
	Returns and array of popular pages
	days_ago: is an optional parameter which specifies how many days ago to start counting. if days_ago is not specified, the count will begin when you installed FireStats.
	num_limit : maximum number of items in the result. optional, defaults to 10.
	site_id : see comment at the start of the file
	type: type of urls to return. if not specified all are returned. see FS_URL_TYPE_* in php/constants.php

	returns: an array of containing object with the fields: 
			url : Item URL
			title: the URL title (only if exists)
			c : number of times this item was viewed
*/
function fs_api_get_popular_pages($days_ago = NULL, $num_limit = 10, $site_id = true, $type = null)
{
	require_once(dirname(__FILE__).'/db-sql.php');
	return fs_get_popular_pages($num_limit, $days_ago, $type);
}
?>
