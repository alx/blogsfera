<?php
// general
define('FS_VERSION','1.4.4-stable');
define('FS_HOMEPAGE','http://firestats.cc');
define('FS_FIRESTATS_VER_CHECK_URL','http://files.firestats.cc/firestats.latest?version='.FS_VERSION);
define('FS_IP2COUNTRY_DB_VER_CHECK_URL','http://files.firestats.cc/ip2c/ip-to-country.latest');
define('FS_HOMEPAGE_TRANSLATE','http://firestats.cc/wiki/TranslateFireStats');
define('FS_WIKI','http://firestats.cc/wiki/');
define('FS_SYSINFO_URL','http://misc.firestats.cc/sysinfo.php');

// database related constants
define('FS_DB_VALID', 0);
define('FS_DB_NOT_INSTALLED', -1);
define('FS_DB_NEED_UPGRADE', -2);
define('FS_DB_IS_NEWER_THAN_CODE', -3);
define('FS_DB_GENERAL_ERROR', -4);
define('FS_DB_NOT_CONFIGURED', -5);
define('FS_DB_CONNECTION_ERROR', -6);

// the database schema version this code works with
define('FS_REQUIRED_DB_VERSION',11);

// site type constants
define('FS_SITE_TYPE_GENERIC'	,0);
define('FS_SITE_TYPE_WORDPRESS'	,1);
define('FS_SITE_TYPE_DJANGO'	,2);
define('FS_SITE_TYPE_DRUPAL'	,3);
define('FS_SITE_TYPE_GREGARIUS'	,4);
define('FS_SITE_TYPE_JOOMLA'	,5);
define('FS_SITE_TYPE_MEDIAWIKI'	,6);
define('FS_SITE_TYPE_TRAC'		,7);
define('FS_SITE_TYPE_GALLERY2'	,8);

// security constants
define("SEC_ADMIN", 1);
define("SEC_USER", 2);
define("SEC_NONE", 3);


define('ORDER_BY_RECENT_FIRST'		,1);
define('ORDER_BY_HIGH_COUNT_FIRST'	,2);
define('ORDER_BY_FIRST_SEEN'		,3);

define('FS_COMMIT_IMMEDIATE'	,1);
define('FS_COMMIT_MANUAL'		,2);


define('FS_ABS_PATH',dirname(dirname(__FILE__)));

/**
 * possible values for metadata type representing url type (no value needed)
 */
define('FS_URL_TYPE_POST',1); // URL is a post

?>
