<?php
function check_upload_size( $file ) {
	if( $file['error'] != '0' ) // there's already an error
		return $file;

	$space_allowed = 1048576 * get_space_allowed();
	$space_used = get_dirsize( constant( "ABSPATH" ) . constant( "UPLOADS" ) );
	$space_left = $space_allowed - $space_used;
	$file_size = filesize( $file['tmp_name'] );
	if( $space_left < $file_size )
		$file['error'] = sprintf( __( 'Not enough space to upload. %1$sKb needed.' ), number_format( ($file_size - $space_left) /1024 ) );
	if( $file_size > ( 1024 * get_site_option( 'fileupload_maxk', 1500 ) ) )
		$file['error'] = sprintf(__('This file is too big. Files must be less than %1$s Kb in size.'), get_site_option( 'fileupload_maxk', 1500 ) );
	if( upload_is_user_over_quota( false ) ) {
		$file['error'] = __('You have used your space quota. Please delete files before uploading.');
	}
	if( $file['error'] != '0' )
		wp_die( $file['error'] . ' <a href="javascript:history.go(-1)">' . __( 'Back' ) . '</a>' );

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'check_upload_size' );

function wpmu_delete_blog($blog_id, $drop = false) {
	global $wpdb;

	if ( $blog_id != $wpdb->blogid ) {
		$switch = true;
		switch_to_blog($blog_id);	
	}

	do_action('delete_blog', $blog_id, $drop);

	$users = get_users_of_blog($blog_id);

	// Remove users from this blog.
	if ( !empty($users) ) foreach ($users as $user) {
		remove_user_from_blog($user->user_id, $blog_id);
	}

	update_blog_status( $blog_id, 'deleted', 1 );

	if ( $drop ) {
		$drop_tables = array( $wpdb->base_prefix . $blog_id . "_categories",
		$wpdb->base_prefix . $blog_id . "_comments",
		$wpdb->base_prefix . $blog_id . "_linkcategories",
		$wpdb->base_prefix . $blog_id . "_links",
		$wpdb->base_prefix . $blog_id . "_link2cat",
		$wpdb->base_prefix . $blog_id . "_options",
		$wpdb->base_prefix . $blog_id . "_post2cat",
		$wpdb->base_prefix . $blog_id . "_postmeta",
		$wpdb->base_prefix . $blog_id . "_posts",
		$wpdb->base_prefix . $blog_id . "_terms",
		$wpdb->base_prefix . $blog_id . "_term_taxonomy",
		$wpdb->base_prefix . $blog_id . "_term_relationships" );

		$drop_tables = apply_filters( 'wpmu_drop_tables', $drop_tables ); 
		reset( $drop_tables );

		foreach ( (array) $drop_tables as $drop_table) {
			$wpdb->query( "DROP TABLE IF EXISTS $drop_table" );
		}

		$wpdb->query( "DELETE FROM $wpdb->blogs WHERE blog_id = '$blog_id'" );
		$dir = constant( "ABSPATH" ) . "wp-content/blogs.dir/{$blog_id}/files/";
		$dir = rtrim($dir, DIRECTORY_SEPARATOR);
		$top_dir = $dir;
		$stack = array($dir);
		$index = 0;

		while ($index < count($stack)) {
			# Get indexed directory from stack
			$dir = $stack[$index];

			$dh = @ opendir($dir);
			if ($dh) {
				while (($file = @ readdir($dh)) !== false) {
					if ($file == '.' or $file == '..')
						continue;

					if (@ is_dir($dir . DIRECTORY_SEPARATOR . $file))
						$stack[] = $dir . DIRECTORY_SEPARATOR . $file;
					else if (@ is_file($dir . DIRECTORY_SEPARATOR . $file))
						@ unlink($dir . DIRECTORY_SEPARATOR . $file);
				}
			}
			$index++;
		}

		$stack = array_reverse($stack);  // Last added dirs are deepest
		foreach( (array) $stack as $dir) {
			if ( $dir != $top_dir)
			@rmdir($dir);
		}
	}
	$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key='wp_{$blog_id}_autosave_draft_ids'");

	if ( $switch === true )
		restore_current_blog();
}

function update_blog_public($old_value, $value) {
	global $wpdb;
	do_action('update_blog_public');
	update_blog_status( $wpdb->blogid, 'public', (int) $value );
}
add_action('update_option_blog_public', 'update_blog_public', 10, 2);

function wpmu_delete_user($id) {
	global $wpdb;

	$id = (int) $id;
	$user = get_userdata($id);

	do_action('wpmu_delete_user', $id);

	$blogs = get_blogs_of_user($id);

	if ( ! empty($blogs) )
		foreach ($blogs as $blog) {
			switch_to_blog($blog->userblog_id);
			remove_user_from_blog($id, $blog->userblog_id);

			$post_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_author = $id");
			foreach ( (array) $post_ids as $post_id )
				wp_delete_post($post_id);

			// Clean links
			$wpdb->query("DELETE FROM $wpdb->links WHERE link_owner = $id");

			restore_current_blog();
		}

	$wpdb->query("DELETE FROM $wpdb->users WHERE ID = $id");
	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE user_id = '$id'");

	wp_cache_delete($id, 'users');
	wp_cache_delete($user->user_login, 'userlogins');

	return true;
}

function wpmu_get_blog_allowedthemes( $blog_id = 0 ) {
	$themes = get_themes();
	if( $blog_id == 0 )
		$blog_allowed_themes = get_option( "allowedthemes" );
	else 
		$blog_allowed_themes = get_blog_option( $blog_id, "allowedthemes" );
	if( !is_array( $blog_allowed_themes ) || empty( $blog_allowed_themes ) ) { // convert old allowed_themes to new allowedthemes
		if( $blog_id == 0 )
			$blog_allowed_themes = get_option( "allowed_themes" );
		else 
			$blog_allowed_themes = get_blog_option( $blog_id, "allowed_themes" );
			
		if( is_array( $blog_allowed_themes ) ) {
			foreach( (array) $themes as $key => $theme ) {
				$theme_key = wp_specialchars( $theme['Stylesheet'] );
				if( isset( $blog_allowed_themes[ $key ] ) == true ) {
					$blog_allowedthemes[ $theme_key ] = 1;
				}
			}
			$blog_allowed_themes = $blog_allowedthemes;
			if( $blog_id == 0 ) {
				add_option( "allowedthemes", $blog_allowed_themes );
				delete_option( "allowed_themes" );
			} else {
				add_blog_option( $blog_id, "allowedthemes", $blog_allowed_themes );
				delete_blog_option( $blog_id, "allowed_themes" );
			}
		}
	}
	return $blog_allowed_themes;
}

function update_option_new_admin_email($old_value, $value) {
	if ( $value == get_option( 'admin_email' ) || !is_email( $value ) )
		return;

	$hash = md5( $value. time() .mt_rand() );
	$new_admin_email = array( "hash" => $hash, "newemail" => $value );
	update_option( 'adminhash', $new_admin_email );
	
	$content = __("Dear user,\n\n
You recently requested to have the administration email address on 
your blog changed.\n
If this is correct, please click on the following link to change it:\n
###ADMIN_URL###\n\n
You can safely ignore and delete this email if you do not want to take this action.\n\n
This email has been sent to ###EMAIL###\n\n
Regards,\n
The Webmaster");
	
	$content = str_replace('###ADMIN_URL###', get_option( "siteurl" ).'/wp-admin/options.php?adminhash='.$hash, $content);
	$content = str_replace('###EMAIL###', $value, $content);
	
	wp_mail( $value, sprintf(__('[%s] New Admin Email Address'), get_option('blogname')), $content );
}
add_action('update_option_new_admin_email', 'update_option_new_admin_email', 10, 2);

function get_site_allowed_themes() {
	$themes = get_themes();
	$allowed_themes = get_site_option( 'allowedthemes' );
	if( !is_array( $allowed_themes ) || empty( $allowed_themes ) ) {
		$allowed_themes = get_site_option( "allowed_themes" ); // convert old allowed_themes format
		if( !is_array( $allowed_themes ) ) {
			$allowed_themes = array();
		} else {
			foreach( (array) $themes as $key => $theme ) {
				$theme_key = wp_specialchars( $theme[ 'Stylesheet' ] );
				if( isset( $allowed_themes[ $key ] ) == true ) {
					$allowedthemes[ $theme_key ] = 1;
				}
			}
			$allowed_themes = $allowedthemes;
		}
	}
	return $allowed_themes;
}

function get_space_allowed() {
	$spaceAllowed = get_option("blog_upload_space");
	if( $spaceAllowed == false ) 
		$spaceAllowed = get_site_option("blog_upload_space");
	if( empty($spaceAllowed) || !is_numeric($spaceAllowed) )
		$spaceAllowed = 50;

	return $spaceAllowed;
}

function display_space_usage() {
	$space = get_space_allowed();
	$used = get_dirsize( constant( "ABSPATH" ) . constant( "UPLOADS" ) )/1024/1024;

	if ($used > $space) $percentused = '100';
	else $percentused = ( $used / $space ) * 100;

	if( $space > 1000 ) {
		$space = number_format( $space / 1024 );
		$space .= __('GB');
	} else {
		$space .= __('MB');
	}
	?>
	<strong><?php printf(__('Used: %1s%% of %2s'), number_format($percentused), $space );?></strong> 
	<?php
}

// Display File upload quota on dashboard
function dashboard_quota() {	
	$quota = get_space_allowed();
	$used = get_dirsize( constant( "ABSPATH" ) . constant( "UPLOADS" ) )/1024/1024;

	if ($used > $quota) $percentused = '100';
	else $percentused = ( $used / $quota ) * 100;

	?>
	<div id='spaceused'>
		<h3><?php _e("Storage Space <a href='upload.php' title='Manage Uploads...'>&raquo;</a>"); ?></h3>
		<p><?php _e('Total space available:'); ?> <strong><?php echo $quota . __('MB'); ?></strong></p>	
		<p><?php _e('Upload space used:'); 	
		?>
		<strong><?php printf(__('%1sMB (%2s%%)'), round($used,2), number_format($percentused) ); ?></strong></p>
	</div>
	<?php
}
if( current_user_can('edit_posts') )
	add_action('activity_box_end', 'dashboard_quota');

// Edit blog upload space setting on Edit Blog page
function upload_space_setting( $id ) {
	$quota = get_blog_option($id, "blog_upload_space"); 
	if( !$quota )
		$quota = '';
	
	?>
	<tr>
		<th><?php _e('Blog Upload Space Quota'); ?></th>
		<td><input type="text" size="3" name="option[blog_upload_space]" value="<?php echo $quota; ?>" /><?php _e('MB (Leave blank for site default)'); ?></td>
	</tr>
	<?php
}
add_action('wpmueditblogaction', 'upload_space_setting');

function update_user_status( $id, $pref, $value, $refresh = 1 ) {
	global $wpdb;

	$wpdb->query( "UPDATE {$wpdb->users} SET {$pref} = '{$value}' WHERE ID = '$id'" );

	if( $refresh == 1 )
		refresh_user_details($id);
	
	if( $pref == 'spam' ) {
		if( $value == 1 ) 
			do_action( "make_spam_user", $id );
		else
			do_action( "make_ham_user", $id );
	}

	return $value;
}

function refresh_user_details($id) {
	$id = (int) $id;
	
	if ( !$user = get_userdata( $id ) )
		return false;

	wp_cache_delete($id, 'users');
	wp_cache_delete($user->user_login, 'userlogins');
	return $id;
}

/*
  Determines if the available space defined by the admin has been exceeded by the user
*/
function wpmu_checkAvailableSpace() {
	$spaceAllowed = get_space_allowed(); 

	$dirName = trailingslashit( constant( "ABSPATH" ) . constant( "UPLOADS" ) );
	if (!(is_dir($dirName) && is_readable($dirName))) 
		return; 

  	$dir = dir($dirName);
   	$size = 0;

	while($file = $dir->read()) {
		if ($file != '.' && $file != '..') {
			if (is_dir( $dirName . $file)) {
				$size += get_dirsize($dirName . $file);
			} else {
				$size += filesize($dirName . $file);
			}
		}
	}
	$dir->close();
	$size = $size / 1024 / 1024;

	if( ($spaceAllowed - $size) <= 0 ) {
		define( 'DISABLE_UPLOADS', true );
		define( 'DISABLE_UPLOADS_MESSAGE', __('Sorry, you must delete files before you can upload any more.') );
	}
}
add_action('upload_files_upload','wpmu_checkAvailableSpace');

function format_code_lang( $code = '' ) {
	$code = strtolower(substr($code, 0, 2));
	$lang_codes = array('aa' => 'Afar',  'ab' => 'Abkhazian',  'af' => 'Afrikaans',  'ak' => 'Akan',  'sq' => 'Albanian',  'am' => 'Amharic',  'ar' => 'Arabic',  'an' => 'Aragonese',  'hy' => 'Armenian',  'as' => 'Assamese',  'av' => 'Avaric',  'ae' => 'Avestan',  'ay' => 'Aymara',  'az' => 'Azerbaijani',  'ba' => 'Bashkir',  'bm' => 'Bambara',  'eu' => 'Basque',  'be' => 'Belarusian',  'bn' => 'Bengali',  'bh' => 'Bihari',  'bi' => 'Bislama',  'bs' => 'Bosnian',  'br' => 'Breton',  'bg' => 'Bulgarian',  'my' => 'Burmese',  'ca' => 'Catalan; Valencian',  'ch' => 'Chamorro',  'ce' => 'Chechen',  'zh' => 'Chinese',  'cu' => 'Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic',  'cv' => 'Chuvash',  'kw' => 'Cornish',  'co' => 'Corsican',  'cr' => 'Cree',  'cs' => 'Czech',  'da' => 'Danish',  'dv' => 'Divehi; Dhivehi; Maldivian',  'nl' => 'Dutch; Flemish',  'dz' => 'Dzongkha',  'en' => 'English',  'eo' => 'Esperanto',  'et' => 'Estonian',  'ee' => 'Ewe',  'fo' => 'Faroese',  'fj' => 'Fijian',  'fi' => 'Finnish',  'fr' => 'French',  'fy' => 'Western Frisian',  'ff' => 'Fulah',  'ka' => 'Georgian',  'de' => 'German',  'gd' => 'Gaelic; Scottish Gaelic',  'ga' => 'Irish',  'gl' => 'Galician',  'gv' => 'Manx',  'el' => 'Greek, Modern',  'gn' => 'Guarani',  'gu' => 'Gujarati',  'ht' => 'Haitian; Haitian Creole',  'ha' => 'Hausa',  'he' => 'Hebrew',  'hz' => 'Herero',  'hi' => 'Hindi',  'ho' => 'Hiri Motu',  'hu' => 'Hungarian',  'ig' => 'Igbo',  'is' => 'Icelandic',  'io' => 'Ido',  'ii' => 'Sichuan Yi',  'iu' => 'Inuktitut',  'ie' => 'Interlingue',  'ia' => 'Interlingua (International Auxiliary Language Association)',  'id' => 'Indonesian',  'ik' => 'Inupiaq',  'it' => 'Italian',  'jv' => 'Javanese',  'ja' => 'Japanese',  'kl' => 'Kalaallisut; Greenlandic',  'kn' => 'Kannada',  'ks' => 'Kashmiri',  'kr' => 'Kanuri',  'kk' => 'Kazakh',  'km' => 'Central Khmer',  'ki' => 'Kikuyu; Gikuyu',  'rw' => 'Kinyarwanda',  'ky' => 'Kirghiz; Kyrgyz',  'kv' => 'Komi',  'kg' => 'Kongo',  'ko' => 'Korean',  'kj' => 'Kuanyama; Kwanyama',  'ku' => 'Kurdish',  'lo' => 'Lao',  'la' => 'Latin',  'lv' => 'Latvian',  'li' => 'Limburgan; Limburger; Limburgish',  'ln' => 'Lingala',  'lt' => 'Lithuanian',  'lb' => 'Luxembourgish; Letzeburgesch',  'lu' => 'Luba-Katanga',  'lg' => 'Ganda',  'mk' => 'Macedonian',  'mh' => 'Marshallese',  'ml' => 'Malayalam',  'mi' => 'Maori',  'mr' => 'Marathi',  'ms' => 'Malay',  'mg' => 'Malagasy',  'mt' => 'Maltese',  'mo' => 'Moldavian',  'mn' => 'Mongolian',  'na' => 'Nauru',  'nv' => 'Navajo; Navaho',  'nr' => 'Ndebele, South; South Ndebele',  'nd' => 'Ndebele, North; North Ndebele',  'ng' => 'Ndonga',  'ne' => 'Nepali',  'nn' => 'Norwegian Nynorsk; Nynorsk, Norwegian',  'nb' => 'Bokmål, Norwegian, Norwegian Bokmål',  'no' => 'Norwegian',  'ny' => 'Chichewa; Chewa; Nyanja',  'oc' => 'Occitan, Provençal',  'oj' => 'Ojibwa',  'or' => 'Oriya',  'om' => 'Oromo',  'os' => 'Ossetian; Ossetic',  'pa' => 'Panjabi; Punjabi',  'fa' => 'Persian',  'pi' => 'Pali',  'pl' => 'Polish',  'pt' => 'Portuguese',  'ps' => 'Pushto',  'qu' => 'Quechua',  'rm' => 'Romansh',  'ro' => 'Romanian',  'rn' => 'Rundi',  'ru' => 'Russian',  'sg' => 'Sango',  'sa' => 'Sanskrit',  'sr' => 'Serbian',  'hr' => 'Croatian',  'si' => 'Sinhala; Sinhalese',  'sk' => 'Slovak',  'sl' => 'Slovenian',  'se' => 'Northern Sami',  'sm' => 'Samoan',  'sn' => 'Shona',  'sd' => 'Sindhi',  'so' => 'Somali',  'st' => 'Sotho, Southern',  'es' => 'Spanish; Castilian',  'sc' => 'Sardinian',  'ss' => 'Swati',  'su' => 'Sundanese',  'sw' => 'Swahili',  'sv' => 'Swedish',  'ty' => 'Tahitian',  'ta' => 'Tamil',  'tt' => 'Tatar',  'te' => 'Telugu',  'tg' => 'Tajik',  'tl' => 'Tagalog',  'th' => 'Thai',  'bo' => 'Tibetan',  'ti' => 'Tigrinya',  'to' => 'Tonga (Tonga Islands)',  'tn' => 'Tswana',  'ts' => 'Tsonga',  'tk' => 'Turkmen',  'tr' => 'Turkish',  'tw' => 'Twi',  'ug' => 'Uighur; Uyghur',  'uk' => 'Ukrainian',  'ur' => 'Urdu',  'uz' => 'Uzbek',  've' => 'Venda',  'vi' => 'Vietnamese',  'vo' => 'Volapük',  'cy' => 'Welsh',  'wa' => 'Walloon',  'wo' => 'Wolof',  'xh' => 'Xhosa',  'yi' => 'Yiddish',  'yo' => 'Yoruba',  'za' => 'Zhuang; Chuang',  'zu' => 'Zulu');
	$lang_codes = apply_filters('lang_codes', $lang_codes, $code);
	return strtr( $code, $lang_codes );
}

function sync_slugs( $term, $taxonomy, $args ) {
	$args[ 'slug' ] = sanitize_title( $args[ 'name' ] );
	return $args;
}
add_filter( 'pre_update_term', 'sync_slugs', 10, 3 );

function redirect_user_to_blog() {
	global $wpdb, $current_user, $current_site;
	get_currentuserinfo();
	$primary_blog = (int) get_usermeta( $current_user->ID, 'primary_blog' );
	if( !$primary_blog )
		$primary_blog = 1;

	$newblog = $wpdb->get_row( "SELECT * FROM {$wpdb->blogs} WHERE blog_id = '{$primary_blog}'" );
	if( $newblog != null ) {
		$blogs = get_blogs_of_user( $current_user->ID );
		if ( empty($blogs) || $blogs == false ) { // If user has no blog
			add_user_to_blog('1', $current_user->ID, 'subscriber'); // Add subscriber permission for first blog.
			wp_redirect( 'http://' . $current_site->domain . $current_site->path. 'wp-admin/' );
			exit();
		}

		foreach ( (array) $blogs as $blog ) {
			if ( $blog->userblog_id == $newblog->blog_id ) {
				wp_redirect( 'http://' . $newblog->domain . $newblog->path . 'wp-admin/' );
				exit();
			}
		}

		reset( $blogs );
		$blog = current( $blogs ); // Take the first blog...
		wp_redirect( 'http://' . $blog->domain . $blog->path. 'wp-admin/' );
		exit();
	}
}
add_action( 'admin_page_access_denied', 'redirect_user_to_blog' );

function wpmu_menu() {
	global $menu, $submenu;

	if( is_site_admin() ) {
		$menu[29] = array(__('Site Admin'), '10', 'wpmu-admin.php' );
		$submenu[ 'wpmu-admin.php' ][1] = array( __('Admin'), '10', 'wpmu-admin.php' );
		$submenu[ 'wpmu-admin.php' ][5] = array( __('Blogs'), '10', 'wpmu-blogs.php' );
		$submenu[ 'wpmu-admin.php' ][10] = array( __('Users'), '10', 'wpmu-users.php' );
		$submenu[ 'wpmu-admin.php' ][20] = array( __('Themes'), '10', 'wpmu-themes.php' );
		$submenu[ 'wpmu-admin.php' ][25] = array( __('Options'), '10', 'wpmu-options.php' );
		$submenu[ 'wpmu-admin.php' ][30] = array( __('Upgrade'), '10', 'wpmu-upgrade-site.php' );
	}
	unset( $submenu['themes.php'][10] );
	unset( $submenu['plugins.php'][5] );
	unset( $submenu['plugins.php'][10] );
	unset( $submenu['edit.php'][30] );
	unset( $menu['35'] ); // Plugins

	$menu_perms = get_site_option( "menu_items" );
	if( is_array( $menu_perms ) == false )
		$menu_perms = array();
	if( $menu_perms[ 'plugins' ] == 1 )
		$menu[35] = array(__('Plugins'), 'activate_plugins', 'plugins.php');
}
add_action( '_admin_menu', 'wpmu_menu' );

function mu_options( $options ) {
	$removed = array( 'general' => array( 'siteurl', 'home', 'admin_email', 'default_role' ),
	'reading' => array( 'gzipcompression' ),
	'writing' => array( 'ping_sites', 'mailserver_login', 'mailserver_pass', 'default_email_category', 'mailserver_port', 'mailserver_url' ),
	'misc' => array( 'hack_file', 'use_linksupdate', 'uploads_use_yearmonth_folders', 'upload_path' ) );

	$added = array( 'general' => array( 'new_admin_email', 'WPLANG', 'language' ) );

	$options = remove_option_whitelist( $removed, $options );
	$options = add_option_whitelist( $added, $options );

	return $options;
}
add_filter( 'whitelist_options', 'mu_options' );

function import_no_new_users( $permission ) {
	return false;
}
add_filter( 'import_allow_create_users', 'import_no_new_users' );
// See "import_allow_fetch_attachments" and "import_attachment_size_limit" filters too.

function add_option_update_handler($option_group, $option_name, $sanitize_callback = '') {
	global $new_whitelist_options;
	$new_whitelist_options[ $option_group ][] = $option_name;
	if( $sanitize_callback != '' )
		add_filter( "sanitize_option_{$option_name}", $sanitize_callback );
}

function remove_option_update_handler($option_group, $option_name, $sanitize_callback = '') {
	global $new_whitelist_options;
	$pos = array_search( $option_name, $new_whitelist_options );
	if( $pos !== false )
		unset( $new_whitelist_options[ $option_group ][ $pos ] );
	if( $sanitize_callback != '' )
		remove_filter( "sanitize_option_{$option_name}", $sanitize_callback );
}

function option_update_filter( $options ) {
	global $new_whitelist_options;

	if( is_array( $new_whitelist_options ) )
		$options = add_option_whitelist( $new_whitelist_options, $options );

	return $options;
}
add_filter( 'whitelist_options', 'option_update_filter' );

function add_option_whitelist( $new_options, $options = '' ) {
	if( $options == '' ) {
		global $whitelist_options;
	} else {
		$whitelist_options = $options;
	}
	foreach( $new_options as $page => $keys ) {
		foreach( $keys as $key ) {
			$pos = array_search( $key, $whitelist_options[ $page ] );
			if( $pos === false )
				$whitelist_options[ $page ][] = $key;
		}
	}
	return $whitelist_options;
}

function remove_option_whitelist( $del_options, $options = '' ) {
	if( $options == '' ) {
		global $whitelist_options;
	} else {
		$whitelist_options = $options;
	}
	foreach( $del_options as $page => $keys ) {
		foreach( $keys as $key ) {
			$pos = array_search( $key, $whitelist_options[ $page ] );
			if( $pos !== false )
				unset( $whitelist_options[ $page ][ $pos ] );
		}
	}
	return $whitelist_options;
}

/* Blogswitcher */
function blogswitch_init() {
	global $current_user;
	$blogs = get_blogs_of_user( $current_user->ID );
	if ( !$blogs )
		return;
	add_action( 'admin_menu', 'blogswitch_ob_start' );
	add_action( 'dashmenu', 'blogswitch_markup' );
}


function blogswitch_ob_start() {
	wp_enqueue_script( 'blog-switch', '/wp-admin/js/blog-switch.js', array( 'jquery' ), 2 );
	ob_start( 'blogswitch_ob_content' );
}

function blogswitch_ob_content( $content ) {
	$content = preg_replace( '#<ul id="dashmenu">.*?%%REAL_DASH_MENU%%#s', '<ul id="dashmenu">', $content );
	return str_replace( '%%END_REAL_DASH_MENU%%</ul>', '', $content );
}

function blogswitch_markup() {
	global $current_user, $current_blog;
	$list = array();
	$options = array();

	$primary_blog = get_usermeta( $current_user->ID, 'primary_blog' );
	$blogs = get_blogs_of_user( $current_user->ID );

	foreach ( (array) $blogs as $blog ) {
		if ( !$blog->blogname )
			continue;

		// Use siteurl for this in case of mapping
		$parsed = parse_url( $blog->siteurl );

		if ( $current_blog->blog_id == $blog->userblog_id ) {
			$current  = ' class="current"';
			$selected = ' selected="selected"';
		} else {
			$current  = '';
			$selected = '';
		}
		
		$url = clean_url( $blog->siteurl ) . '/wp-admin/';
		$name = wp_specialchars( strip_tags( $blog->blogname ) );
		$list_item = "<li><a href='$url'$current>$name</a></li>";
		$option_item = "<option value='$url'$selected>$name</option>";

		if ( $current_blog->blog_id == $blog->userblog_id ) {
			$list[-2] = $list_item;
			$options[] = $option_item; // [sic] don't reorder dropdown based on current blog
		} elseif ( $primary_blog == $blog->userblog_id ) {
			$list[-1] = $list_item;
			$options[-1] = $option_item;
		} else {
			$list[] = $list_item;
			$options[] = $option_item;
		}
	}
	ksort($list);
	ksort($options);

	$list = array_slice( $list, 0, 4 ); // First 4

	$select = "\n\t\t<select>\n\t\t\t" . join( "\n\t\t\t", $options ) . "\n\t\t</select>";

	echo "%%REAL_DASH_MENU%%\n\t" . join( "\n\t", $list );

	if ( count($list) < count($options) ) :
?>

	<li id="all-my-blogs-tab" class="wp-no-js-hidden"><a href="#" class="blog-picker-toggle"><?php _e( 'All my blogs' ); ?></a></li>

	</ul>

	<form id="all-my-blogs" action="" method="get" style="display: none">
		<p>
			<?php printf( __( 'Choose a blog: %s' ), $select ); ?>

			<input type="submit" class="button" value="<?php _e( 'Go' ); ?>" />
			<a href="#" class="blog-picker-toggle"><?php _e( 'Cancel' ); ?></a>
		</p>
	</form>

<?php 	else : // counts ?>

	</ul>

<?php
	endif; // counts

	echo '%%END_REAL_DASH_MENU%%';
}

add_action( '_admin_menu', 'blogswitch_init' );

function mu_css() {
	wp_admin_css( 'css/mu' );
}
add_action( 'admin_head', 'mu_css' );

function mu_dropdown_languages( $lang_files = array(), $current = '' ) {
	$flag = false;	
	$output = array();
					
	foreach ( (array) $lang_files as $val ) {
		$code_lang = basename( $val, '.mo' );
		
		if ( $code_lang == 'en_US' ) { // American English
			$flag = true;
			$ae = __('American English');
			$output[$ae] = '<option value="'.$code_lang.'"'.(($current == $code_lang) ? ' selected="selected"' : '').'> '.$ae.'</option>';
		} elseif ( $code_lang == 'en_GB' ) { // British English
			$flag = true;
			$be = __('British English');
			$output[$be] = '<option value="'.$code_lang.'"'.(($current == $code_lang) ? ' selected="selected"' : '').'> '.$be.'</option>';
		} else {
			$translated = format_code_lang($code_lang);
			$output[$translated] =  '<option value="'.$code_lang.'"'.(($current == $code_lang) ? ' selected="selected"' : '').'> '.$translated.'</option>';
		}
		
	}						
	
	if ( $flag === false ) { // WordPress english
		$output[] = '<option value=""'.((empty($current)) ? ' selected="selected"' : '').'>'.__('English')."</option>";
	}
	
	// Order by name
	uksort($output, 'strnatcasecmp');
	
	$output = apply_filters('mu_dropdown_languages', $output, $lang_files, $current);	
	echo implode("\n\t", $output);	
}

// Only show "Media" upload icon
function mu_media_buttons() {
	global $post_ID, $temp_ID;
	$uploading_iframe_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);
	$context = apply_filters('media_buttons_context', __('Add media: %s'));
	$media_upload_iframe_src = "media-upload.php?post_id=$uploading_iframe_ID";
	$media_title = __('Add Media');
	$out = <<<EOF
	<a href="{$media_upload_iframe_src}&amp;TB_iframe=true&amp;height=500&amp;width=640" class="thickbox" title='$media_title'><img src='images/media-button-other.gif' alt='$media_title' /></a>
EOF;
	printf($context, $out);
}
add_action( 'media_buttons', 'mu_media_buttons' );
remove_action( 'media_buttons', 'media_buttons' );

/* Warn the admin if SECRET SALT information is missing from wp-config.php */
function secret_salt_warning() {
	if( !is_site_admin() )
		return;
	if( !defined( 'SECRET_KEY' ) || !defined( 'SECRET_SALT' ) ) {
		$salt1 = wp_generate_password() . wp_generate_password();
		$salt2 = wp_generate_password() . wp_generate_password();
		$msg = sprintf( __( 'Warning! You must define SECRET_KEY and SECRET_SALT in <strong>%swp-config.php</strong><br />Please add the following code before the line, <code>/* That\'s all, stop editing! Happy blogging. */</code>' ), ABSPATH );
		$msg .= "<blockquote>define('SECRET_KEY', '$salt1');<br />define('SECRET_SALT', '$salt2');</blockquote>";

		echo "<div id='update-nag'>$msg</div>";
	}
}
add_action( 'admin_notices', 'secret_salt_warning' );

function mu_dashboard() {
	unregister_sidebar_widget( 'dashboard_plugins' );
}
add_action( 'wp_dashboard_setup', 'mu_dashboard' );

/* Unused update message called from Dashboard */
function update_right_now_message() {
}

function profile_update_primary_blog() {
	global $current_user;

	if ( isset( $_POST['primary_blog'] ) ) {
		update_user_option( $current_user->id, 'primary_blog', (int) $_POST['primary_blog'], true );
	}
}
add_action( 'personal_options_update', 'profile_update_primary_blog' );
?>
