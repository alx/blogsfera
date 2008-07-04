<?php
/*
 * This is a sample configuration file.
 * If you wish to change any of the options here, first copy this file to conf.php (in the same directory).
 * This action is needed to avoid overwriting your configuration file when you upgrade.
 * 
 * 
 * NOTE: This file does NOT contain database configuration. 
 * (db configuration is exists in php/fs-config.php which is created by FireStats autoamtically)
 */

/**
 * Default language for FireStats.
 * This is the language of the login screen and of FireStats for users that did not choose a new language from the settings tab.
 */
 
#define('FS_DEFAULT_LANG','en_US');


/**
 * Commit strategy
 * FireStats support the following data commit strategies.
 * 1. Immediate (default): 
 * 	All hits are commited immediatelly, this provides the best user experience, but is also the heaviest load on the server.
 * 	
 * 2. Manual:
 * In the this mode, new hits are stored on a in the pending-hits table. this operation is very fast (a single insert), but the hits need futher 
 * processing before it's data becomes available to users.
 * To initiate a commit, you need to execute the file php/commit.php.
 * this is usually done using a cron job that executre "php -f /www/firestats/php/commit-pending.php" periodically.
 * 
 * Valid values are FS_COMMIT_IMMEDIATE and FS_COMMIT_MANUAL.
 */
 
#define('FS_COMMIT_STRATEGY',FS_COMMIT_MANUAL);

/**
 * THe maximum number of hits to process in a single iteration when commiting pending hits.
 * the larger this number, the better the performance - but also the higher the memory usage of the commit.
 */
#define('FS_COMMIT_MAX_CHUNK_SIZE',1000);


/**
 * It is possible to override all the options in php/session.php from here.
 * to do this, simply define them here.
 */

?>