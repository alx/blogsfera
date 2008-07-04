<?php
/* Don't try to create this file by hand. Read the README.txt and run the installer. */
// ** MySQL settings ** //
define('DB_NAME', 'wordpress');    // The name of the database
define('DB_USER', 'username');     // Your MySQL username
define('DB_PASSWORD', 'password'); // ...and password
define('DB_HOST', 'localhost');    // 99% chance you won't need to change this value
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
define('VHOST', 'VHOSTSETTING'); 
$base = 'BASE';

// Change SECRET_KEY to a unique phrase.  You won't have to remember it later,
// so make it long and complicated.  You can visit http://api.wordpress.org/secret-key/1.0/
// to get a secret key generated for you, or just make something up.
define('SECRET_KEY', 'put your unique phrase here'); // Change this to a unique phrase.
define('SECRET_SALT', 'put your unique phrase here'); // Change this to a unique phrase.

// double check $base
if( $base == 'BASE' )
	die( 'Problem in wp-config.php - $base is set to BASE when it should be the path like "/" or "/blogs/"! Please fix it!' );
// You can have multiple installations in one database if you give each a unique prefix
$table_prefix  = 'wp_';   // Only numbers, letters, and underscores please!

// Change this to localize WordPress.  A corresponding MO file for the
// chosen language must be installed to wp-content/languages.
// For example, install de.mo to wp-content/languages and set WPLANG to 'de'
// to enable German language support.
define ('WPLANG', '');

// uncomment this to enable wp-content/sunrise.php support
//define( 'SUNRISE', 'on' );

// Uncomment and set this to a URL to redirect if a blog does not exist. (Useful if signup is disabled)
// Browser will redirect to constant( 'NOBLOGREDICT' ) . "?new=blogname" where blogname is the unknown blog
// define( 'NOBLOGREDIRECT', '' );

define( "WP_USE_MULTIPLE_DB", false );

/* That's all, stop editing! Happy blogging. */

define('ABSPATH', dirname(__FILE__).'/');
require_once(ABSPATH.'wp-settings.php');
?>
