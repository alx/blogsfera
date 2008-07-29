<?php
/*
	Helper functions for WPMU
*/
function load_muplugin_textdomain($domain, $path = false) {
	$locale = get_locale();
	if ( empty($locale) )
		$locale = 'en_US';

	if ( false === $path )
		$path = MUPLUGINDIR;

	$mofile = ABSPATH . "$path/$domain-$locale.mo";
	load_textdomain($domain, $mofile);
}

function wpmu_update_blogs_date() {
	global $wpdb;

	$wpdb->query( "UPDATE {$wpdb->blogs} SET last_updated = NOW() WHERE  blog_id = '{$wpdb->blogid}'" );
	refresh_blog_details( $wpdb->blogid );

	do_action( 'wpmu_blog_updated', $wpdb->blogid );
}

add_action('delete_post', 'wpmu_update_blogs_date');
add_action('private_to_published', 'wpmu_update_blogs_date');
add_action('publish_phone', 'wpmu_update_blogs_date');
add_action('publish_post', 'wpmu_update_blogs_date');

function get_blogaddress_by_id( $blog_id ) {
	$bloginfo = get_blog_details( (int) $blog_id, false ); // only get bare details!
	return clean_url("http://" . $bloginfo->domain . $bloginfo->path);
}

function get_blogaddress_by_name( $blogname ) {
	global $hostname, $domain, $base;

	if( defined( "VHOST" ) && constant( "VHOST" ) == 'yes' ) {
		if( $blogname == 'main' )
			$blogname = 'www';
		return clean_url("http://".$blogname.".".$domain.$base);
	} else {
		return clean_url("http://".$hostname.$base.$blogname);
	}
}

function get_blogaddress_by_domain( $domain, $path ){
	if( defined( "VHOST" ) && constant( "VHOST" ) == 'yes' ) {
		$url = "http://".$domain.$path;
	} else {
		if( $domain != $_SERVER['HTTP_HOST'] ) {
			$blogname = substr( $domain, 0, strpos( $domain, '.' ) );
			if( $blogname != 'www.' ) {
				$url = 'http://' . substr( $domain, strpos( $domain, '.' ) + 1 ) . $path . $blogname . '/';
			} else { // we're installing the main blog
				$url = 'http://' . substr( $domain, strpos( $domain, '.' ) + 1 ) . $path;
			}
		} else { // main blog
			$url = 'http://' . $domain . $path;
		}
	}
	return clean_url($url);
}

function get_sitestats() {
	global $wpdb;

	$stats['blogs'] = get_blog_count();

	$count_ts = get_site_option( "get_user_count_ts" );
	if( time() - $count_ts > 3600 ) {
		$count = $wpdb->get_var( "SELECT count(*) as c FROM {$wpdb->users}" );
		update_site_option( "user_count", $count );
		update_site_option( "user_count_ts", time() );
	} else {
		$count = get_site_option( "user_count" );
	}
	$stats['users'] = $count;
	return $stats;
}

function get_admin_users_for_domain( $sitedomain = '', $path = '' ) {
	global $wpdb;
	if( $sitedomain == '' ) {
		$site_id = $wpdb->siteid;
	} else {
		$site_id = $wpdb->get_var( "SELECT id FROM ".$wpdb->site." WHERE domain = '".$sitedomain."' AND path = '".$path."'" );
	}
	if( $site_id != false ) {
		$details = $wpdb->get_results( "SELECT ID, user_login, user_pass FROM ".$wpdb->users.", ".$wpdb->sitemeta." WHERE meta_key = 'admin_user_id' AND ".$wpdb->users.".ID = ".$wpdb->sitemeta.".meta_value AND ".$wpdb->sitemeta.".site_id = '".$site_id."'", ARRAY_A );
	} else {
		$details = false;
	}
	return $details;
}

function get_user_details( $username ) {
	global $wpdb;
	return $wpdb->get_row( "SELECT * FROM $wpdb->users WHERE user_login = '$username'" );
}

function is_main_blog() {
	global $current_blog, $current_site;
	if( $current_blog->domain == $current_site->domain && $current_blog->path == $current_site->path )
		return true;
	return false;
}

function get_id_from_blogname( $name ) {
	global $wpdb, $current_site;
	if( constant( 'VHOST' ) == 'yes' ) {
		$domain = $name . '.' . $current_site->domain;
		$path = $current_site->path;
	} else {
		$domain = $current_site->domain;
		$path = $current_site->path . $name;
	}
	return $wpdb->get_var( "SELECT blog_id FROM {$wpdb->blogs} WHERE domain = '$domain' AND path = '$path'" );
}

function get_blog_details( $id, $getall = true ) {
	global $wpdb;

	if( !is_numeric( $id ) ) {
		$id = get_id_from_blogname( $id );
	}
	$all = $getall == true ? '' : 'short';
	$details = wp_cache_get( $id . $all, 'blog-details' );

	if ( $details ) {
		if ( $details == -1 )
			return false;
		elseif ( !is_object($details) ) // Clear old pre-serialized objects. Cache clients do better with that.
			wp_cache_delete( $id . $all, 'blog-details' );
		else
			return $details;
	}

	$details = $wpdb->get_row( "SELECT * FROM $wpdb->blogs WHERE blog_id = '$id' /* get_blog_details */" );

	if ( !$details ) {
		wp_cache_set( $id . $all, -1, 'blog-details' );
		return false;
	}

	if ( !$getall ) {
		wp_cache_add( $id . $all, $details, 'blog-details' );
		return $details;
	}

	$wpdb->suppress_errors();
	$details->blogname   = get_blog_option($id, 'blogname');
	$details->siteurl    = get_blog_option($id, 'siteurl');
	$details->post_count = get_blog_option($id, 'post_count');
	$wpdb->suppress_errors( false );

	$details = apply_filters('blog_details', $details);

	wp_cache_set( $id . $all, $details, 'blog-details' );

	$key = md5( $details->domain . $details->path );
	wp_cache_set( $key, $details, 'blog-lookup' );

	return $details;
}

function refresh_blog_details( $id ) {
	$id = (int) $id;
	$details = get_blog_details( $id, false );
	
	wp_cache_delete( $id , 'blog-details' );
	wp_cache_delete( md5( $details->domain . $details->path )  , 'blog-lookup' );
}

function get_current_user_id() {
	global $current_user;
	return $current_user->ID;
}

function is_site_admin( $user_login = false ) {
	global $current_user;

	if ( !$current_user && !$user_login )
		return false;

	if ( $user_login )
		$user_login = sanitize_user( $user_login );
	else
		$user_login = $current_user->user_login;

	$site_admins = get_site_option( 'site_admins', array('admin') );
	if( is_array( $site_admins ) && in_array( $user_login, $site_admins ) )
		return true;

	return false;
}

// expects key not to be SQL escaped
function get_site_option( $key, $default = false, $use_cache = true ) {
	global $wpdb;

	// Allow plugins to short-circuit site options. 
 	$pre = apply_filters( 'pre_site_option_' . $key, false ); 
 	if ( false !== $pre ) 
 		return $pre; 

	$safe_key = $wpdb->escape( $key );

	if( $use_cache == true ) {
		$value = wp_cache_get($wpdb->siteid . $key, 'site-options');
	} else {
		$value = false;
	}

	if ( false === $value ) {
		$value = $wpdb->get_var("SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = '$safe_key' AND site_id = '{$wpdb->siteid}'");
		if ( ! is_null($value) ) {
			wp_cache_add($wpdb->siteid . $key, $value, 'site-options');
		} elseif ( $default ) {
			wp_cache_add($wpdb->siteid . $key, addslashes( $default ), 'site-options');
			return $default;
		} else {
			wp_cache_add($wpdb->siteid . $key, false, 'site-options');
			return false;
		}
	}

	if (! unserialize($value) ) 
		$value = stripslashes( $value ); 

	return apply_filters( 'site_option_' . $key, maybe_unserialize( $value ) );
}

// expects $key, $value not to be SQL escaped
function add_site_option( $key, $value ) {
	global $wpdb;

	$safe_key = $wpdb->escape( $key );

	$exists = $wpdb->get_row("SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = '$safe_key' AND site_id = '{$wpdb->siteid}'");
	if ( is_object( $exists ) ) {// If we already have it
		update_site_option( $key, $value );
		return false;
	}

	if ( is_array($value) || is_object($value) )
		$value = serialize($value);
	wp_cache_delete($wpdb->siteid . $key, 'site-options');
	$wpdb->query( "INSERT INTO $wpdb->sitemeta ( site_id , meta_key , meta_value ) VALUES ( '{$wpdb->siteid}', '$safe_key', '" . $wpdb->escape( $value ) . "')" );
	return $wpdb->insert_id;
}

// expects $key, $value not to be SQL escaped
function update_site_option( $key, $value ) {
	global $wpdb;

	$safe_key = $wpdb->escape( $key );

	if ( $value == get_site_option( $key ) )
	 	return false;

	$exists = $wpdb->get_row("SELECT meta_value FROM $wpdb->sitemeta WHERE meta_key = '$safe_key' AND site_id = '{$wpdb->siteid}'");

	if ( false == is_object( $exists ) ) // It's a new record
		return add_site_option( $key, $value );

	if ( is_array($value) || is_object($value) )
		$value = serialize($value);

	$wpdb->query( "UPDATE $wpdb->sitemeta SET meta_value = '" . $wpdb->escape( $value ) . "' WHERE site_id='{$wpdb->siteid}' AND meta_key = '$safe_key'" );
	wp_cache_delete( $wpdb->siteid . $key, 'site-options' );
	return true;
}

/*
function get_blog_option( $id, $key, $default='na' ) {
	switch_to_blog($id);
	$opt = get_option( $key );
	restore_current_blog();

	return $opt;
}
*/

function get_blog_option( $blog_id, $setting, $default='na' ) {
	global $wpdb;

	$key = $blog_id."-".$setting."-blog_option";
	$value = wp_cache_get( $key, "site-options" );
	if( $value == null ) {
		$row = $wpdb->get_row( "SELECT * FROM {$wpdb->base_prefix}{$blog_id}_options WHERE option_name = '{$setting}'" );
		if( is_object( $row) ) { // Has to be get_row instead of get_var because of funkiness with 0, false, null values
			$value = $row->option_value;
			if( $value == false ) {
				wp_cache_set($key, 'falsevalue', 'site-options');
				return false;
			} else {
				wp_cache_set($key, $value, 'site-options');
			}
		} else { // option does not exist, so we must cache its non-existence
			wp_cache_set($key, 'noop', 'site-options');
		}
	} elseif( $value == 'noop' ) {
		return false;
	} elseif( $value == 'falsevalue' ) {
		return false;
	}
	// If home is not set use siteurl.
	if ( 'home' == $setting && '' == $value )
		return get_blog_option($blog_id, 'siteurl');

	if ( 'siteurl' == $setting || 'home' == $setting || 'category_base' == $setting )
		$value = preg_replace('|/+$|', '', $value);

	if (! unserialize($value) )
		$value = stripslashes( $value );

	return apply_filters( 'option_' . $setting, maybe_unserialize($value) );
}

function add_blog_option( $id, $key, $value ) {
	switch_to_blog($id);
	add_option( $key, $value );
	restore_current_blog();
	$opt = $id."-".$key."-blog_option";
	wp_cache_set($opt, $value, 'site-options');
}

function delete_blog_option( $id, $key ) {
	switch_to_blog($id);
	delete_option( $key );
	restore_current_blog();
	$opt = $id."-".$key."-blog_option";
	wp_cache_set($opt, '', 'site-options');
}

function update_blog_option( $id, $key, $value, $refresh = true ) {
	switch_to_blog($id);
	update_option( $key, $value );
	restore_current_blog();
	
	if( $refresh == true ) {
		refresh_blog_details( $id );
	}
	wp_cache_set( $id."-".$key."-blog_option", $value, 'site-options');
}

function switch_to_blog( $new_blog ) {
	global $wpdb, $table_prefix, $blog_id, $switched, $switched_stack, $wp_roles, $current_user, $wp_object_cache;

	if ( empty($new_blog) )
		return;

	if ( empty($switched_stack) )
		$switched_stack = array();

	$switched_stack[] = $blog_id;

	if ( $blog_id == $new_blog )
		return;

	$wp_object_cache->switched_cache[ $blog_id ] = $wp_object_cache->cache;
	unset( $wp_object_cache->cache );

	$wpdb->set_blog_id($new_blog);
	$table_prefix = $wpdb->prefix;
	$prev_blog_id = $blog_id;
	$blog_id = $new_blog;

	if( is_object( $wp_roles ) ) {
		$wpdb->suppress_errors();
		$wp_roles->_init();
		$wpdb->suppress_errors( false );
	}

	if ( is_object( $current_user ) )
		$current_user->_init_caps();

	do_action('switch_blog', $blog_id, $prev_blog_id);
	$switched = true;
}

function restore_current_blog() {
	global $table_prefix, $wpdb, $blog_id, $switched, $switched_stack, $wp_roles, $current_user, $wp_object_cache;

	if ( !$switched )
		return;

	$blog = array_pop($switched_stack);
	if ( $blog_id == $blog )
		return;

	$wp_object_cache->cache = $wp_object_cache->switched_cache[ $blog ];
	unset( $wp_object_cache->switched_cache[ $blog ] );

	$wpdb->set_blog_id($blog);
	$prev_blog_id = $blog_id;
	$blog_id = $blog;
	$table_prefix = $wpdb->prefix;

	if( is_object( $wp_roles ) ) {
		$wpdb->suppress_errors();
		$wp_roles->_init();
		$wpdb->suppress_errors( false );
	}

	if ( is_object( $current_user ) )
		$current_user->_init_caps();

	do_action('switch_blog', $blog_id, $prev_blog_id);

	$switched = false;
}

function get_blogs_of_user( $id, $all = false ) {
	global $wpdb;

	$user = get_userdata( $id );
	if ( !$user )
		return false;

	$blogs = $match = array();
	foreach ( (array) $user as $key => $value ) {
		if ( strstr( $key, '_capabilities') && strstr( $key, $wpdb->base_prefix) ) {
			preg_match('/' . $wpdb->base_prefix . '(\d+)_capabilities/', $key, $match);
			$blog = get_blog_details( $match[1] );
			if ( $blog && isset( $blog->domain ) && ( $all == true || $all == false && ( $blog->archived == 0 && $blog->spam == 0 && $blog->deleted == 0 ) ) ) {
				$blogs[$match[1]]->userblog_id = $match[1];
				$blogs[$match[1]]->blogname    = $blog->blogname;
				$blogs[$match[1]]->domain      = $blog->domain;
				$blogs[$match[1]]->path        = $blog->path;
				$blogs[$match[1]]->site_id     = $blog->site_id;
				$blogs[$match[1]]->siteurl     = $blog->siteurl;
			}
		}
	}

	return $blogs;
}

function get_active_blog_for_user( $user_id ) { // get an active blog for user - either primary blog or from blogs list
	$primary_blog = get_usermeta( $user_id, "primary_blog" );
	if( $primary_blog == false ) {
		$details = false;
	} else {
		$details = get_blog_details( $primary_blog );
	}

	if( ( is_object( $details ) == false ) || ( is_object( $details ) && $details->archived == 1 || $details->spam == 1 || $details->deleted == 1 ) ) {
		$blogs = get_blogs_of_user( $user_id, true ); // if a user's primary blog is shut down, check their other blogs.
		$ret = false;
		if( is_array( $blogs ) && count( $blogs ) > 0 ) {
			foreach( (array) $blogs as $blog_id => $blog ) {
				$details = get_blog_details( $blog_id );
				if( is_object( $details ) && $details->archived == 0 && $details->spam == 0 && $details->deleted == 0 ) {
					$ret = $blog;
					break;
				}
			}
		} else {
			$ret = "username only"; // user has no blogs. We can add details for dashboard.wordpress.com here.
		}
		return $ret;
	} else {
		return $details;
	}
}

function is_user_member_of_blog( $user_id, $blog_id = 0 ) {
	global $wpdb;
	if( $blog_id == 0 )
		$blog_id = $wpdb->blogid;

	$blogs = get_blogs_of_user( $user_id );
	if( is_array( $blogs ) ) {
		return array_key_exists( $blog_id, $blogs );
	} else {
		return false;
	}
}

function is_archived( $id ) {
	return get_blog_status($id, 'archived');
}

function update_archived( $id, $archived ) {
	update_blog_status($id, 'archived', $archived);
	return $archived;
}

function update_blog_status( $id, $pref, $value, $refresh = 1 ) {
	global $wpdb;

	$wpdb->query( "UPDATE {$wpdb->blogs} SET {$pref} = '{$value}', last_updated = NOW() WHERE blog_id = '$id'" );

	if( $refresh == 1 )
		refresh_blog_details($id);

	if( $pref == 'spam' ) {
		if( $value == 1 ) {
			do_action( "make_spam_blog", $id );
		} else {
			do_action( "make_ham_blog", $id );
		}
	}

	return $value;
}

function get_blog_status( $id, $pref ) {
	global $wpdb;

	$details = get_blog_details( $id, false );
	if( $details ) {
		return $details->$pref;
	}
	return $wpdb->get_var( "SELECT $pref FROM {$wpdb->blogs} WHERE blog_id = '$id'" );
}

function get_last_updated( $display = false ) {
	global $wpdb;
	return $wpdb->get_results( "SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = '$wpdb->siteid' AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' AND last_updated != '0000-00-00 00:00:00' ORDER BY last_updated DESC limit 0,40", ARRAY_A );
}

function get_most_active_blogs( $num = 10, $display = true ) {
	$most_active = get_site_option( "most_active" );
	$update = false;
	if( is_array( $most_active ) ) {
		if( ( $most_active['time'] + 60 ) < time() ) { // cache for 60 seconds.
			$update = true;
		}
	} else {
		$update = true;
	}

	if( $update == true ) {
		unset( $most_active );
		$blogs = get_blog_list( 0, 'all', false ); // $blog_id -> $details
		if( is_array( $blogs ) ) {
			reset( $blogs );
			foreach ( (array) $blogs as $key => $details ) {
				$most_active[ $details['blog_id'] ] = $details['postcount'];
				$blog_list[ $details['blog_id'] ] = $details; // array_slice() removes keys!!
			}
			arsort( $most_active );
			reset( $most_active );
			foreach ( (array) $most_active as $key => $details ) {
				$t[ $key ] = $blog_list[ $key ];
			}
			unset( $most_active );
			$most_active = $t;
		}
		update_site_option( "most_active", $most_active );
	}

	if( $display == true ) {
		if( is_array( $most_active ) ) {
			reset( $most_active );
			foreach ( (array) $most_active as $key => $details ) {
				$url = clean_url("http://" . $details['domain'] . $details['path']);
				echo "<li>" . $details['postcount'] . " <a href='$url'>$url</a></li>";
			}
		}
	}
	return array_slice( $most_active, 0, $num );
}

function get_blog_list( $start = 0, $num = 10, $display = true ) {
	global $wpdb;

	$blogs = get_site_option( "blog_list" );
	$update = false;
	if( is_array( $blogs ) ) {
		if( ( $blogs['time'] + 60 ) < time() ) { // cache for 60 seconds.
			$update = true;
		}
	} else {
		$update = true;
	}

	if( $update == true ) {
		unset( $blogs );
		$blogs = $wpdb->get_results( "SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = '$wpdb->siteid' AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY registered DESC", ARRAY_A );

		foreach ( (array) $blogs as $details ) {
			$blog_list[ $details['blog_id'] ] = $details;
			$blog_list[ $details['blog_id'] ]['postcount'] = $wpdb->get_var( "SELECT count(*) FROM " . $wpdb->base_prefix . $details['blog_id'] . "_posts WHERE post_status='publish' AND post_type='post'" );
		}
		unset( $blogs );
		$blogs = $blog_list;
		update_site_option( "blog_list", $blogs );
	}

	if( $num == 'all' ) {
		return array_slice( $blogs, $start, count( $blogs ) );
	} else {
		return array_slice( $blogs, $start, $num );
	}
}

function get_blog_count( $id = 0 ) {
	global $wpdb;

	if( $id == 0 )
		$id = $wpdb->siteid;

	$count_ts = get_site_option( "blog_count_ts" );
	if( time() - $count_ts > 3600 ) {
		$count = $wpdb->get_var( "SELECT count(*) as c FROM $wpdb->blogs WHERE site_id = '$id' AND spam='0' AND deleted='0' and archived='0'" );
		update_site_option( "blog_count", $count );
		update_site_option( "blog_count_ts", time() );
	}

	$count = get_site_option( "blog_count" );

	return $count;
}

function get_blog_post( $blog_id, $post_id ) {
	global $wpdb;

	$key = $blog_id."-".$post_id."-blog_post";
	$post = wp_cache_get( $key, "site-options" );
	if( $post == false ) {
		$post = $wpdb->get_row( "SELECT * FROM {$wpdb->base_prefix}{$blog_id}_posts WHERE ID = '{$post_id}'" );
		wp_cache_add( $key, $post, "site-options", 120 );
	}

	return $post;

}

function add_user_to_blog( $blog_id, $user_id, $role ) {
	switch_to_blog($blog_id);

	$user = new WP_User($user_id);

	if ( empty($user) )
		return new WP_Error('user_does_not_exist', __('That user does not exist.'));

	if ( !get_usermeta($user_id, 'primary_blog') ) {
		update_usermeta($user_id, 'primary_blog', $blog_id);
		$details = get_blog_details($blog_id);
		update_usermeta($user_id, 'source_domain', $details->domain);
	}

	$user->set_role($role);

	do_action('add_user_to_blog', $user_id, $role, $blog_id);
	wp_cache_delete( $user_id, 'users' );
	restore_current_blog();
	return true;
}

function remove_user_from_blog($user_id, $blog_id = '') {
	switch_to_blog($blog_id);
	$user_id = (int) $user_id;
	do_action('remove_user_from_blog', $user_id, $blog_id);

	// If being removed from the primary blog, set a new primary if the user is assigned
	// to multiple blogs.
	$primary_blog = get_usermeta($user_id, 'primary_blog');
	if ( $primary_blog == $blog_id ) {
		$new_id = '';
		$new_domain = '';
		$blogs = get_blogs_of_user($user_id);
		foreach ( (array) $blogs as $blog ) {
			if ( $blog->userblog_id == $blog_id )
				continue;
			$new_id = $blog->userblog_id;
			$new_domain = $blog->domain;
			break;
		}

		update_usermeta($user_id, 'primary_blog', $new_id);
		update_usermeta($user_id, 'source_domain', $new_domain);
	}

	wp_revoke_user($user_id);

	$blogs = get_blogs_of_user($user_id);
	if ( count($blogs) == 0 ) {
		update_usermeta($user_id, 'primary_blog', '');
		update_usermeta($user_id, 'source_domain', '');
	}

	restore_current_blog();
}

function create_empty_blog( $domain, $path, $weblog_title, $site_id = 1 ) {
	$domain       = addslashes( $domain );
	$weblog_title = addslashes( $weblog_title );

	if( empty($path) )
		$path = '/';

	// Check if the domain has been used already. We should return an error message.
	if ( domain_exists($domain, $path, $site_id) )
		return __('error: Blog URL already taken.');

	// Need to backup wpdb table names, and create a new wp_blogs entry for new blog.
	// Need to get blog_id from wp_blogs, and create new table names.
	// Must restore table names at the end of function.

	if ( ! $blog_id = insert_blog($domain, $path, $site_id) )
		return __('error: problem creating blog entry');

	switch_to_blog($blog_id);
	install_blog($blog_id);
	restore_current_blog();

	return $blog_id;
}

function get_blog_permalink( $blog_id, $post_id ) {
	$key = "{$blog_id}-{$post_id}-blog_permalink";
	$link = wp_cache_get( $key, 'site-options' );
	if( $link == false ) {
		switch_to_blog( $blog_id );
		$link = get_permalink( $post_id );
		restore_current_blog();
		wp_cache_add( $key, $link, "site-options", 30 );
	}
	return $link;
}

// wpmu admin functions

function wpmu_admin_do_redirect( $url = '' ) {
	$ref = '';
	if ( isset( $_GET['ref'] ) )
		$ref = $_GET['ref'];
	if ( isset( $_POST['ref'] ) )
		$ref = $_POST['ref'];
	
	if( $ref ) {
		$ref = wpmu_admin_redirect_add_updated_param( $ref );
		wp_redirect( $ref );
		die();
	}
	if( empty( $_SERVER['HTTP_REFERER'] ) == false ) {
		wp_redirect( $_SERVER['HTTP_REFERER'] );
		die();
	}

	$url = wpmu_admin_redirect_add_updated_param( $url );
	if( isset( $_GET['redirect'] ) ) {
		if( substr( $_GET['redirect'], 0, 2 ) == 's_' ) {
			$url .= "&action=blogs&s=". wp_specialchars( substr( $_GET['redirect'], 2 ) );
		}
	} elseif( isset( $_POST['redirect'] ) ) {
		$url = wpmu_admin_redirect_add_updated_param( $_POST['redirect'] );
	}
	wp_redirect( $url );
	die();
}

function wpmu_admin_redirect_add_updated_param( $url = '' ) {
	if( strpos( $url, 'updated=true' ) === false ) {
		if( strpos( $url, '?' ) === false ) {
			return $url . '?updated=true';
		} else {
			return $url . '&updated=true';
		}
	}
	return $url;
}

function wpmu_admin_redirect_url() {
	if( isset( $_GET['s'] ) ) {
		return "s_".$_GET['s'];
	}
}

function is_blog_user( $blog_id = 0 ) {
	global $current_user, $wpdb;

	if ( !$blog_id )
		$blog_id = $wpdb->blogid;

	$cap_key = $wpdb->base_prefix . $blog_id . '_capabilities';

	if ( is_array($current_user->$cap_key) && in_array(1, $current_user->$cap_key) )
		return true;

	return false;
}

function validate_email( $email, $check_domain = true) {
    if (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'.'@'.
        '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'.
        '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $email))
    {
        if ($check_domain && function_exists('checkdnsrr')) {
            list (, $domain)  = explode('@', $email);

            if (checkdnsrr($domain.'.', 'MX') || checkdnsrr($domain.'.', 'A')) {
                return true;
            }
            return false;
        }
        return true;
    }
    return false;
}

function is_email_address_unsafe( $user_email ) {
	$banned_names = get_site_option( "banned_email_domains" );
	if ( is_array( $banned_names ) && empty( $banned_names ) == false ) {
		$email_domain = strtolower( substr( $user_email, 1 + strpos( $user_email, '@' ) ) );
		foreach( (array) $banned_names as $banned_domain ) {
			if( $banned_domain == '' )
				continue;
			if (
				strstr( $email_domain, $banned_domain ) ||
				(
					strstr( $banned_domain, '/' ) &&
					preg_match( $banned_domain, $email_domain )
				)
			) 
			return true;
		}
	}
	return false;
}

function wpmu_validate_user_signup($user_name, $user_email) {
	global $wpdb;

	$errors = new WP_Error();

	$user_name = preg_replace( "/\s+/", '', sanitize_user( $user_name, true ) );
	$user_email = sanitize_email( $user_email );

	if ( empty( $user_name ) )
	   	$errors->add('user_name', __("Please enter a username"));

	$maybe = array();
	preg_match( "/[a-z0-9]+/", $user_name, $maybe );

	if( $user_name != $maybe[0] ) {
	    $errors->add('user_name', __("Only lowercase letters and numbers allowed"));
	}

	$illegal_names = get_site_option( "illegal_names" );
	if( is_array( $illegal_names ) == false ) {
		$illegal_names = array(  "www", "web", "root", "admin", "main", "invite", "administrator" );
		add_site_option( "illegal_names", $illegal_names );
	}
	if( in_array( $user_name, $illegal_names ) == true ) {
	    $errors->add('user_name',  __("That username is not allowed"));
	}

	if( is_email_address_unsafe( $user_email ) ) 
		$errors->add('user_email',  __("You cannot use that email address to signup. We are having problems with them blocking some of our email. Please use another email provider."));

	if( strlen( $user_name ) < 4 ) {
	    $errors->add('user_name',  __("Username must be at least 4 characters"));
	}

	if ( strpos( " " . $user_name, "_" ) != false )
		$errors->add('user_name', __("Sorry, usernames may not contain the character '_'!"));

	// all numeric?
	$match = array();
	preg_match( '/[0-9]*/', $user_name, $match );
	if ( $match[0] == $user_name )
		$errors->add('user_name', __("Sorry, usernames must have letters too!"));

	if ( !is_email( $user_email ) )
	    $errors->add('user_email', __("Please enter a correct email address"));

	if ( !validate_email( $user_email ) )
		$errors->add('user_email', __("Please check your email address."));

	$limited_email_domains = get_site_option( 'limited_email_domains' );
	if ( is_array( $limited_email_domains ) && empty( $limited_email_domains ) == false ) {
		$emaildomain = substr( $user_email, 1 + strpos( $user_email, '@' ) );
		if( in_array( $emaildomain, $limited_email_domains ) == false ) {
			$errors->add('user_email', __("Sorry, that email address is not allowed!"));
		}
	}

	// Check if the username has been used already.
	if ( username_exists($user_name) )
		$errors->add('user_name', __("Sorry, that username already exists!"));

	// Check if the email address has been used already.
	if ( email_exists($user_email) )
		$errors->add('user_email', __("Sorry, that email address is already used!"));

	// Has someone already signed up for this username?
	$signup = $wpdb->get_row("SELECT * FROM $wpdb->signups WHERE user_login = '$user_name'");
	if ( $signup != null ) {
		$registered_at =  mysql2date('U', $signup->registered);
		$now = current_time( 'timestamp', true );
		$diff = $now - $registered_at;
		// If registered more than two days ago, cancel registration and let this signup go through.
		if ( $diff > 172800 ) {
			$wpdb->query("DELETE FROM $wpdb->signups WHERE user_login = '$user_name'");
		} else {
			$errors->add('user_name', __("That username is currently reserved but may be available in a couple of days."));
		}
		if( $signup->active == 0 && $signup->user_email == $user_email )
			$errors->add('user_email_used', __("username and email used"));
	}

	$signup = $wpdb->get_row("SELECT * FROM $wpdb->signups WHERE user_email = '$user_email'");
	if ( $signup != null ) {
		$registered_at =  mysql2date('U', $signup->registered);
		$now = current_time( 'timestamp', true );
		$diff = $now - $registered_at;
		// If registered more than two days ago, cancel registration and let this signup go through.
		if ( $diff > 172800 ) {
			$wpdb->query("DELETE FROM $wpdb->signups WHERE user_email = '$user_email'");
		} else {
			$errors->add('user_email', __("That email address has already been used. Please check your inbox for an activation email. It will become available in a couple of days if you do nothing."));
		}
	}

	$result = array('user_name' => $user_name, 'user_email' => $user_email,	'errors' => $errors);

	return apply_filters('wpmu_validate_user_signup', $result);
}

function wpmu_validate_blog_signup($blogname, $blog_title, $user = '') {
	global $wpdb, $domain, $base;

	$blogname = preg_replace( "/\s+/", '', sanitize_user( $blogname, true ) );
	$blog_title = strip_tags( $blog_title );
	$blog_title = substr( $blog_title, 0, 50 );

	$errors = new WP_Error();
	$illegal_names = get_site_option( "illegal_names" );
	if( $illegal_names == false ) {
	    $illegal_names = array( "www", "web", "root", "admin", "main", "invite", "administrator" );
	    add_site_option( "illegal_names", $illegal_names );
	}

	if ( empty( $blogname ) )
	    $errors->add('blogname', __("Please enter a blog name"));

	$maybe = array();
	preg_match( "/[a-z0-9]+/", $blogname, $maybe );
	if( $blogname != $maybe[0] ) {
	    $errors->add('blogname', __("Only lowercase letters and numbers allowed"));
	}
	if( in_array( $blogname, $illegal_names ) == true ) {
	    $errors->add('blogname',  __("That name is not allowed"));
	}
	if( strlen( $blogname ) < 4 && !is_site_admin() ) {
	    $errors->add('blogname',  __("Blog name must be at least 4 characters"));
	}

	if ( strpos( " " . $blogname, "_" ) != false )
		$errors->add('blogname', __("Sorry, blog names may not contain the character '_'!"));

	// all numeric?
	$match = array();
	preg_match( '/[0-9]*/', $blogname, $match );
	if ( $match[0] == $blogname )
		$errors->add('blogname', __("Sorry, blog names must have letters too!"));

	$blogname = apply_filters( "newblogname", $blogname );

	$blog_title = stripslashes(  $blog_title );

	if ( empty( $blog_title ) )
	    $errors->add('blog_title', __("Please enter a blog title"));

	// Check if the domain/path has been used already.
	if( constant( "VHOST" ) == 'yes' ) {
		$mydomain = "$blogname.$domain";
		$path = $base;
	} else {
		$mydomain = "$domain";
		$path = $base.$blogname.'/';
	}
	if ( domain_exists($mydomain, $path) )
		$errors->add('blogname', __("Sorry, that blog already exists!"));

	if ( username_exists($blogname) ) {
		if  ( !is_object($user) && ( $user->user_login != $blogname ) )
			$errors->add('blogname', __("Sorry, that blog is reserved!"));
	}

	// Has someone already signed up for this domain?
	// TODO: Check email too?
	$signup = $wpdb->get_row("SELECT * FROM $wpdb->signups WHERE domain = '$mydomain' AND path = '$path'");
	if ( ! empty($signup) ) {
		$registered_at =  mysql2date('U', $signup->registered);
		$now = current_time( 'timestamp', true );
		$diff = $now - $registered_at;
		// If registered more than two days ago, cancel registration and let this signup go through.
		if ( $diff > 172800 ) {
			$wpdb->query("DELETE FROM $wpdb->signups WHERE domain = '$mydomain' AND path = '$path'");
		} else {
			$errors->add('blogname', __("That blog is currently reserved but may be available in a couple days."));
		}
	}

	$result = array('domain' => $mydomain, 'path' => $path, 'blogname' => $blogname, 'blog_title' => $blog_title,
				'errors' => $errors);

	return apply_filters('wpmu_validate_blog_signup', $result);
}

// Record signup information for future activation. wpmu_validate_signup() should be run
// on the inputs before calling wpmu_signup().
function wpmu_signup_blog($domain, $path, $title, $user, $user_email, $meta = '') {
	global $wpdb;

	$key = substr( md5( time() . rand() . $domain ), 0, 16 );
	$registered = current_time('mysql', true);
	$meta = serialize($meta);
	$domain = $wpdb->escape($domain);
	$path = $wpdb->escape($path);
	$title = $wpdb->escape($title);
	$wpdb->query( "INSERT INTO $wpdb->signups ( domain, path, title, user_login, user_email, registered, activation_key, meta )
					VALUES ( '$domain', '$path', '$title', '$user', '$user_email', '$registered', '$key', '$meta' )" );

	wpmu_signup_blog_notification($domain, $path, $title, $user, $user_email, $key, $meta);
}

function wpmu_signup_user($user, $user_email, $meta = '') {
	global $wpdb;

	$user = preg_replace( "/\s+/", '', sanitize_user( $user, true ) );
	$user_email = sanitize_email( $user_email );

	$key = substr( md5( time() . rand() . $user_email ), 0, 16 );
	$registered = current_time('mysql', true);
	$meta = serialize($meta);
	$wpdb->query( "INSERT INTO $wpdb->signups ( domain, path, title, user_login, user_email, registered, activation_key, meta )
					VALUES ( '', '', '', '$user', '$user_email', '$registered', '$key', '$meta' )" );

	wpmu_signup_user_notification($user, $user_email, $key, $meta);
}

// Notify user of signup success.
function wpmu_signup_blog_notification($domain, $path, $title, $user, $user_email, $key, $meta = '') {
	global $current_site;

	if( !apply_filters('wpmu_signup_blog_notification', $domain, $path, $title, $user, $user_email, $key, $meta) )
		return;

	// Send email with activation link.
	if( constant( "VHOST" ) == 'no' ) {
		$activate_url = "http://" . $current_site->domain . $current_site->path . "wp-activate.php?key=$key";
	} else {
		$activate_url = "http://{$domain}{$path}wp-activate.php?key=$key";
	}
	$activate_url = clean_url($activate_url);
	$admin_email = get_site_option( "admin_email" );
	if( $admin_email == '' )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];
	$from_name = get_site_option( "site_name" ) == '' ? 'WordPress' : wp_specialchars( get_site_option( "site_name" ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	$message = sprintf(__("To activate your blog, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login.\n\nAfter you activate, you can visit your blog here:\n\n%s"), $activate_url, clean_url("http://{$domain}{$path}"));
	// TODO: Don't hard code activation link.
	$subject = '[' . $from_name . '] ' . sprintf(__('Activate %s'), clean_url('http://' . $domain . $path));
	wp_mail($user_email, $subject, $message, $message_headers);
}

function wpmu_signup_user_notification($user, $user_email, $key, $meta = '') {
	global $current_site;

	if( !apply_filters('wpmu_signup_user_notification', $user, $user_email, $key, $meta) )
		return;

	// Send email with activation link.
	$admin_email = get_site_option( "admin_email" );
	if( $admin_email == '' )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];
	$from_name = get_site_option( "site_name" ) == '' ? 'WordPress' : wp_specialchars( get_site_option( "site_name" ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	$message = sprintf(__("To activate your user, please click the following link:\n\n%s\n\nAfter you activate, you will receive *another email* with your login.\n\n"), clean_url("http://{$current_site->domain}{$current_site->path}wp-activate.php?key=$key") );
	// TODO: Don't hard code activation link.
	$subject = sprintf(__('Activate %s'), $user);
	wp_mail($user_email, $subject, $message, $message_headers);
}

function wpmu_activate_signup($key) {
	global $wpdb;

	$signup = $wpdb->get_row("SELECT * FROM $wpdb->signups WHERE activation_key = '$key'");

	if ( empty($signup) )
		return new WP_Error('invalid_key', __('Invalid activation key.'));

	if ( $signup->active )
		return new WP_Error('already_active', __('The blog is already active.'), $signup);

	$meta = unserialize($signup->meta);
	$user_login = $wpdb->escape($signup->user_login);
	$user_email = $wpdb->escape($signup->user_email);
	wpmu_validate_user_signup($user_login, $user_email);
	$password = generate_random_password();

	$user_id = username_exists($user_login);

	if ( ! $user_id )
		$user_id = wpmu_create_user($user_login, $password, $user_email);
	else
		$user_already_exists = true;

	if ( ! $user_id )
		return new WP_Error('create_user', __('Could not create user'), $signup);

	$now = current_time('mysql', true);

	if ( empty($signup->domain) ) {
		$wpdb->query("UPDATE $wpdb->signups SET active = '1', activated = '$now' WHERE activation_key = '$key'");
		if ( isset($user_already_exists) )
			return new WP_Error('user_already_exists', __('That username is already activated.'), $signup);
		wpmu_welcome_user_notification($user_id, $password, $meta);
		add_user_to_blog('1', $user_id, 'subscriber');
		do_action('wpmu_activate_user', $user_id, $password, $meta);
		return array('user_id' => $user_id, 'password' => $password, 'meta' => $meta);
	}

	wpmu_validate_blog_signup($signup->domain, $signup->title);
	$blog_id = wpmu_create_blog($signup->domain, $signup->path, $signup->title, $user_id, $meta);

	// TODO: What to do if we create a user but cannot create a blog?
	if ( is_wp_error($blog_id) ) {
		// If blog is taken, that means a previous attempt to activate this blog failed in between creating the blog and
		// setting the activation flag.  Let's just set the active flag and instruct the user to reset their password.
		if ( 'blog_taken' == $blog_id->get_error_code() ) {
			$blog_id->add_data($signup);
			$wpdb->query("UPDATE $wpdb->signups SET active = '1', activated = '$now' WHERE activation_key = '$key'");
		}

		return $blog_id;
	}

	$wpdb->query("UPDATE $wpdb->signups SET active = '1', activated = '$now' WHERE activation_key = '$key'");

	wpmu_welcome_notification($blog_id, $user_id, $password, $signup->title, $meta);

	do_action('wpmu_activate_blog', $blog_id, $user_id, $password, $signup->title, $meta);

	return array('blog_id' => $blog_id, 'user_id' => $user_id, 'password' => $password, 'title' => $signup->title, 'meta' => $meta);
}

function generate_random_password( $len = 8 ) {
	$random_password = substr(md5(uniqid(microtime())), 0, intval( $len ) );
	$random_password = apply_filters('random_password', $random_password);
	return $random_password;
}

function wpmu_create_user( $user_name, $password, $email) {
	$user_name = preg_replace( "/\s+/", '', sanitize_user( $user_name, true ) );
	if ( username_exists($user_name) )
		return false;

	// Check if the email address has been used already.
	if ( email_exists($email) )
		return false;

	$user_id = wp_create_user( $user_name, $password, $email );
	$user = new WP_User($user_id);
	// Newly created users have no roles or caps until they are added to a blog.
	update_usermeta($user_id, 'capabilities', '');
	update_usermeta($user_id, 'user_level', '');

	do_action( 'wpmu_new_user', $user_id );

	return $user_id;
}

function wpmu_create_blog($domain, $path, $title, $user_id, $meta = '', $site_id = 1) {
	$domain = preg_replace( "/\s+/", '', sanitize_user( $domain, true ) );
	if( constant( 'VHOST' ) == 'yes' )
		$domain = str_replace( '@', '', $domain );
	$title = strip_tags( $title );
	$user_id = (int) $user_id;

	if( empty($path) )
		$path = '/';

	// Check if the domain has been used already. We should return an error message.
	if ( domain_exists($domain, $path, $site_id) )
		return new WP_Error('blog_taken', __('Blog already exists.'));

	if ( !defined("WP_INSTALLING") )
		define( "WP_INSTALLING", true );

	if ( ! $blog_id = insert_blog($domain, $path, $site_id) )
		return new WP_Error('insert_blog', __('Could not create blog.'));

	switch_to_blog($blog_id);

	install_blog($blog_id, $title);

	install_blog_defaults($blog_id, $user_id);

	add_user_to_blog($blog_id, $user_id, 'administrator');

	if ( is_array($meta) ) foreach ($meta as $key => $value) {
		if( $key == 'public' || $key == 'archived' || $key == 'mature' || $key == 'spam' || $key == 'deleted' || $key == 'lang_id' ) {
			update_blog_status( $blog_id, $key, $value );
		} else {
			update_option( $key, $value );
		}
	}

	add_option( 'WPLANG', get_site_option( 'WPLANG' ) );

	update_option( 'blog_public', $meta['public'] );

	if(get_usermeta( $user_id, 'primary_blog' ) == 1 )
		update_usermeta( $user_id, 'primary_blog', $blog_id );


	restore_current_blog();

	do_action( 'wpmu_new_blog', $blog_id, $user_id );

	return $blog_id;
}

function newblog_notify_siteadmin( $blog_id, $user_id ) {
	global $current_site;
	if( get_site_option( 'registrationnotification' ) != 'yes' )
		return;
		
	$email = get_site_option( 'admin_email' );
	if( is_email($email) == false )
		return false;
	
	$options_site_url = clean_url("http://{$current_site->domain}{$current_site->path}wp-admin/wpmu-options.php");
	
	$msg = sprintf(__("New Blog: %1s
URL: %2s
Remote IP: %3s

Disable these notifications: %4s"), get_blog_option( $blog_id, "blogname" ), get_blog_option( $blog_id, "siteurl" ), $_SERVER['REMOTE_ADDR'], $options_site_url);
	$msg = apply_filters( 'newblog_notify_siteadmin', $msg );
	
	wp_mail( $email, sprintf(__("New Blog Registration: %s"), get_blog_option( $blog_id, "siteurl" )), $msg );
}
add_action( "wpmu_new_blog", "newblog_notify_siteadmin", 10, 2 );

function newuser_notify_siteadmin( $user_id ) {
	global $current_site;
	if( get_site_option( 'registrationnotification' ) != 'yes' )
		return false;
		
	$email = get_site_option( 'admin_email' );
	if( is_email($email) == false )
		return false;
	$user = new WP_User($user_id);

	$options_site_url = clean_url("http://{$current_site->domain}{$current_site->path}wp-admin/wpmu-options.php");
	$msg = sprintf(__("New User: %1s
Remote IP: %2s

Disable these notifications: %3s"), $user->user_login, $_SERVER['REMOTE_ADDR'], $options_site_url);
	
	$msg = apply_filters( 'newuser_notify_siteadmin', $msg );
	wp_mail( $email, sprintf(__("New User Registration: %s"), $user->user_login), $msg );
	return true;
}
add_action( "wpmu_new_user", "newuser_notify_siteadmin" );

function domain_exists($domain, $path, $site_id = 1) {
	global $wpdb;
	return $wpdb->get_var("SELECT blog_id FROM $wpdb->blogs WHERE domain = '$domain' AND path = '$path' AND site_id = '$site_id'" );
}

function insert_blog($domain, $path, $site_id) {
	global $wpdb;
	$path = trailingslashit($path);
	$site_id = (int) $site_id;
	
	$result = $wpdb->query( "INSERT INTO $wpdb->blogs ( blog_id, site_id, domain, path, registered ) VALUES ( NULL, '$site_id', '$domain', '$path', NOW( ))" );
	if ( !$result )
		return false;

	refresh_blog_details($wpdb->insert_id);
	return $wpdb->insert_id;
}

// Install an empty blog.  wpdb should already be switched.
function install_blog($blog_id, $blog_title = '') {
	global $wpdb, $table_prefix, $wp_roles;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
	$wpdb->suppress_errors();
	$installed = $wpdb->get_results("SELECT * FROM $wpdb->posts");
	$wpdb->suppress_errors( false);
	if ($installed) die(__('<h1>Already Installed</h1><p>You appear to have already installed WordPress. To reinstall please clear your old database tables first.</p>') . '</body></html>');

	$url = get_blogaddress_by_id($blog_id);

	// Set everything up
	make_db_current_silent();
	populate_options();
	populate_roles();
	$wp_roles->_init();
	// fix url.
	update_option('siteurl', $url);
	update_option('home', $url);
	update_option('fileupload_url', $url . "files" );
	update_option('upload_path', "wp-content/blogs.dir/" . $blog_id . "/files");
	update_option('blogname', $blog_title);
	update_option('admin_email', '');
	$wpdb->query("UPDATE $wpdb->options SET option_value = '' WHERE option_name = 'admin_email'");

	// Default category
	$cat_name = $wpdb->escape(__('Uncategorized'));
	$cat_slug = sanitize_title(__('Uncategorized'));
	$wpdb->query("INSERT INTO $wpdb->terms (term_id, name, slug, term_group) VALUES ('1', '$cat_name', '$cat_slug', '0')");

	$wpdb->query("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ('1', 'category', '', '0', '1')");

	// Default link category
	$cat_name = $wpdb->escape(__('Blogroll'));
	$cat_slug = sanitize_title(__('Blogroll'));
	$blogroll_id = $wpdb->get_var( "SELECT cat_ID FROM {$wpdb->sitecategories} WHERE category_nicename = '$cat_slug'" );
	if( $blogroll_id == null ) {
		$wpdb->query( "INSERT INTO " . $wpdb->sitecategories . " (cat_ID, cat_name, category_nicename, last_updated) VALUES (0, '$cat_name', '$cat_slug', NOW())" );
		$blogroll_id = $wpdb->insert_id;
	}
	$wpdb->query("INSERT INTO $wpdb->terms (term_id, name, slug, term_group) VALUES ('$blogroll_id', '$cat_name', '$cat_slug', '0')");
	$wpdb->query("INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ('$blogroll_id', 'link_category', '', '0', '2')");

	update_option('default_link_category', $blogroll_id);

	// remove all perms
	$wpdb->query( "DELETE FROM ".$wpdb->usermeta." WHERE meta_key = '".$table_prefix."user_level'" );
	$wpdb->query( "DELETE FROM ".$wpdb->usermeta." WHERE meta_key = '".$table_prefix."capabilities'" );

	wp_cache_delete('notoptions', 'options');
	wp_cache_delete('alloptions', 'options');

	$wpdb->suppress_errors( false );
}

function install_blog_defaults($blog_id, $user_id) {
	global $wpdb, $wp_rewrite, $current_site, $table_prefix;

	$wpdb->suppress_errors();

	// Default links
	$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_category, link_owner, link_rss) VALUES ('http://wordpress.com/', 'WordPress.com', 1356, '$user_id', 'http://wordpress.com/feed/');");
	$wpdb->query("INSERT INTO $wpdb->links (link_url, link_name, link_category, link_owner, link_rss) VALUES ('http://wordpress.org/', 'WordPress.org', 1356, '$user_id', 'http://wordpress.org/development/feed/');");
	$wpdb->query( "INSERT INTO $wpdb->term_relationships (`object_id`, `term_taxonomy_id`) VALUES (1, 2)" );
	$wpdb->query( "INSERT INTO $wpdb->term_relationships (`object_id`, `term_taxonomy_id`) VALUES (2, 2)" );

	// First post
	$now = date('Y-m-d H:i:s');
	$now_gmt = gmdate('Y-m-d H:i:s');
	$first_post = get_site_option( 'first_post' );
	if( $first_post == false )
		$first_post = stripslashes( __( 'Welcome to <a href="SITE_URL">SITE_NAME</a>. This is your first post. Edit or delete it, then start blogging!' ) );

	$first_post = str_replace( "SITE_URL", clean_url("http://" . $current_site->domain . $current_site->path), $first_post );
	$first_post = str_replace( "SITE_NAME", $current_site->site_name, $first_post );
	$first_post = stripslashes( $first_post );

	$wpdb->query("INSERT INTO $wpdb->posts (post_author, post_date, post_date_gmt, post_content, post_title, post_category, post_name, post_modified, post_modified_gmt, comment_count) VALUES ('".$user_id."', '$now', '$now_gmt', '".addslashes($first_post)."', '".addslashes(__('Hello world!'))."', '0', '".addslashes(__('hello-world'))."', '$now', '$now_gmt', '1')");
	$wpdb->query( "INSERT INTO $wpdb->term_relationships (`object_id`, `term_taxonomy_id`) VALUES (1, 1)" );
	update_option( "post_count", 1 );

	// First page
	$wpdb->query("INSERT INTO $wpdb->posts (post_author, post_date, post_date_gmt, post_content, post_excerpt, post_title, post_category, post_name, post_modified, post_modified_gmt, post_status, post_type, to_ping, pinged, post_content_filtered) VALUES ('$user_id', '$now', '$now_gmt', '".$wpdb->escape(__('This is an example of a WordPress page, you could edit this to put information about yourself or your site so readers know where you are coming from. You can create as many pages like this one or sub-pages as you like and manage all of your content inside of WordPress.'))."', '', '".$wpdb->escape(__('About'))."', '0', '".$wpdb->escape(__('about'))."', '$now', '$now_gmt', 'publish', 'page', '', '', '')");
	// Flush rules to pick up the new page.
	$wp_rewrite->init();
	$wp_rewrite->flush_rules();

	// Default comment
	$wpdb->query("INSERT INTO $wpdb->comments (comment_post_ID, comment_author, comment_author_email, comment_author_url, comment_author_IP, comment_date, comment_date_gmt, comment_content) VALUES ('1', '".addslashes(__('Mr WordPress'))."', '', 'http://" . $current_site->domain . $current_site->path . "', '127.0.0.1', '$now', '$now_gmt', '".addslashes(__('Hi, this is a comment.<br />To delete a comment, just log in, and view the posts\' comments, there you will have the option to edit or delete them.'))."')");

	$user = new WP_User($user_id);
	$wpdb->query("UPDATE $wpdb->options SET option_value = '$user->user_email' WHERE option_name = 'admin_email'");

	// Remove all perms except for the login user.
	$wpdb->query( "DELETE FROM ".$wpdb->usermeta." WHERE  user_id != '".$user_id."' AND meta_key = '".$table_prefix."user_level'" );
	$wpdb->query( "DELETE FROM ".$wpdb->usermeta." WHERE  user_id != '".$user_id."' AND meta_key = '".$table_prefix."capabilities'" );
	// Delete any caps that snuck into the previously active blog. (Hardcoded to blog 1 for now.) TODO: Get previous_blog_id.
	if ( !is_site_admin( $user->user_login ) && $user_id != 1 )
		$wpdb->query( "DELETE FROM ".$wpdb->usermeta." WHERE  user_id = '".$user_id."' AND meta_key = '" . $wpdb->base_prefix . "1_capabilities'" );

	$wpdb->suppress_errors( false );
}

function wpmu_welcome_notification($blog_id, $user_id, $password, $title, $meta = '') {
	global $current_site;

	if( !apply_filters('wpmu_welcome_notification', $blog_id, $user_id, $password, $title, $meta) )
		return;

	$welcome_email = stripslashes( get_site_option( 'welcome_email' ) );
	if( $welcome_email == false )
		$welcome_email = stripslashes( __( "Dear User,

Your new SITE_NAME blog has been successfully set up at:
BLOG_URL

You can log in to the administrator account with the following information:
Username: USERNAME
Password: PASSWORD
Login Here: BLOG_URLwp-login.php

We hope you enjoy your new weblog.
Thanks!

--The WordPress Team
SITE_NAME" ) );

	$url = get_blogaddress_by_id($blog_id);
	$user = new WP_User($user_id);

	$welcome_email = str_replace( "SITE_NAME", $current_site->site_name, $welcome_email );
	$welcome_email = str_replace( "BLOG_URL", $url, $welcome_email );
	$welcome_email = str_replace( "USERNAME", $user->user_login, $welcome_email );
	$welcome_email = str_replace( "PASSWORD", $password, $welcome_email );

	$welcome_email = apply_filters( "update_welcome_email", $welcome_email, $blog_id, $user_id, $password, $title, $meta);
	$admin_email = get_site_option( "admin_email" );
	if( $admin_email == '' )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];
	$from_name = get_site_option( "site_name" ) == '' ? 'WordPress' : wp_specialchars( get_site_option( "site_name" ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	$message = $welcome_email;
	if( empty( $current_site->site_name ) )
		$current_site->site_name = "WordPress MU";
	$subject = sprintf(__('New %1$s Blog: %2$s'), $current_site->site_name, $title);
	wp_mail($user->user_email, $subject, $message, $message_headers);
}

function wpmu_welcome_user_notification($user_id, $password, $meta = '') {
	global $current_site;

	if( !apply_filters('wpmu_welcome_user_notification', $user_id, $password, $meta) )
		return;

	$welcome_email = __( "Dear User,

Your new account is setup.

You can log in with the following information:
Username: USERNAME
Password: PASSWORD

Thanks!

--The WordPress Team
SITE_NAME" );

	$user = new WP_User($user_id);

	$welcome_email = apply_filters( "update_welcome_user_email", $welcome_email, $user_id, $password, $meta);
	$welcome_email = str_replace( "SITE_NAME", $current_site->site_name, $welcome_email );
	$welcome_email = str_replace( "USERNAME", $user->user_login, $welcome_email );
	$welcome_email = str_replace( "PASSWORD", $password, $welcome_email );

	$admin_email = get_site_option( "admin_email" );
	if( $admin_email == '' )
		$admin_email = 'support@' . $_SERVER['SERVER_NAME'];
	$from_name = get_site_option( "site_name" ) == '' ? 'WordPress' : wp_specialchars( get_site_option( "site_name" ) );
	$message_headers = "MIME-Version: 1.0\n" . "From: \"{$from_name}\" <{$admin_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n";
	$message = $welcome_email;
	if( empty( $current_site->site_name ) )
		$current_site->site_name = "WordPress MU";
	$subject = sprintf(__('New %1$s User: %2$s'), $current_site->site_name, $user->user_login);
	wp_mail($user->user_email, $subject, $message, $message_headers);
}

function get_current_site() {
	global $current_site;
	return $current_site;
}

function get_user_id_from_string( $string ) {
	global $wpdb;
	if( is_email( $string ) ) {
		$user_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->users} WHERE user_email = '$string'" );
	} elseif ( is_numeric( $string ) ) {
		$user_id = $string;
	} else {
		$user_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->users} WHERE user_login = '$string'" );
	}

	return $user_id;
}

function get_most_recent_post_of_user( $user_id ) {
	global $wpdb;

	$user_id = (int) $user_id;

	$user_blogs = get_blogs_of_user($user_id);
	$most_recent_post = array();

	// Walk through each blog and get the most recent post
	// published by $user_id
	foreach ( $user_blogs as $blog ) {
		$recent_post = $wpdb->get_row("SELECT ID, post_date_gmt FROM {$wpdb->base_prefix}{$blog->userblog_id}_posts WHERE post_author = '{$user_id}' AND post_type = 'post' AND post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT 1", ARRAY_A);

		// Make sure we found a post
		if ( isset($recent_post['ID']) ) {
			$post_gmt_ts = strtotime($recent_post['post_date_gmt']);

			// If this is the first post checked or if this post is
			// newer than the current recent post, make it the new
			// most recent post.
			if (
				!isset($most_recent_post['post_gmt_ts'])
				|| ($post_gmt_ts > $most_recent_post['post_gmt_ts'])
			) {
				$most_recent_post = array(
					'blog_id'		=> $blog->userblog_id,
					'post_id'		=> $recent_post['ID'],
					'post_date_gmt'	=> $recent_post['post_date_gmt'],
					'post_gmt_ts'	=> $post_gmt_ts
				);
			}
		}
	}

	return $most_recent_post;
}

/* Misc functions */

function fix_upload_details( $uploads ) {
	$uploads['url'] = str_replace( UPLOADS, "files", $uploads['url'] );
	return $uploads;
}
add_filter( "upload_dir", "fix_upload_details" );


function get_dirsize($directory) {
	$size = 0;
	if(substr($directory,-1) == '/') $directory = substr($directory,0,-1);
	if(!file_exists($directory) || !is_dir($directory) || !is_readable($directory)) return false;
	if($handle = opendir($directory)) {
		while(($file = readdir($handle)) !== false) {
			$path = $directory.'/'.$file;
			if($file != '.' && $file != '..') {
				if(is_file($path)) {
					$size += filesize($path);
				} elseif(is_dir($path)) {
					$handlesize = get_dirsize($path);
					if($handlesize >= 0) {
						$size += $handlesize;
					} else {
						return false;
					}
				}
			}
		}
		closedir($handle);
	}
	return $size;
}

function upload_is_user_over_quota( $echo = true ) {
	// Default space allowed is 10 MB 
	$spaceAllowed = get_space_allowed();
	if(empty($spaceAllowed) || !is_numeric($spaceAllowed)) $spaceAllowed = 10;
	
	$dirName = constant( "ABSPATH" ) . constant( "UPLOADS" );
	$size = get_dirsize($dirName) / 1024 / 1024;
	
	if( ($spaceAllowed-$size) < 0 ) { 
		if( $echo )
			_e( "Sorry, you have used your space allocation. Please delete some files to upload more files." ); //No space left
		return true;
	} else {
		return false;
	}
}
add_action( 'upload_files_upload', 'upload_is_user_over_quota' );
add_action( 'upload_files_browse', 'upload_is_user_over_quota' );
add_action( 'upload_files_browse-all', 'upload_is_user_over_quota' );

function check_upload_mimes($mimes) {
	$site_exts = explode( " ", get_site_option( "upload_filetypes" ) );
	foreach ( $site_exts as $ext )
		foreach ( $mimes as $ext_pattern => $mime )
			if( strpos( $ext_pattern, $ext ) !== false )
				$site_mimes[$ext_pattern] = $mime;
	return $site_mimes;
}
add_filter('upload_mimes', 'check_upload_mimes');

function update_posts_count( $post_id ) {
	global $wpdb;
	$post_id = intval( $post_id );
	$c = $wpdb->get_var( "SELECT count(*) FROM {$wpdb->posts} WHERE post_status = 'publish' and post_type='post'" );
	update_option( "post_count", $c );
}
add_action( "publish_post", "update_posts_count" );

function wpmu_log_new_registrations( $blog_id, $user_id ) {
	global $wpdb;
	$user = new WP_User($user_id);
	$email = $wpdb->escape($user->user_email);
	$IP = preg_replace( '/[^0-9., ]/', '',$_SERVER['REMOTE_ADDR'] );
	$wpdb->query( "INSERT INTO {$wpdb->registration_log} ( email , IP , blog_id, date_registered ) VALUES ( '{$email}', '{$IP}', '{$blog_id}', NOW( ))" );
}

add_action( "wpmu_new_blog" ,"wpmu_log_new_registrations", 10, 2 );

function fix_import_form_size( $size ) {
	if( upload_is_user_over_quota( false ) == true )
		return 0;
	$spaceAllowed = 1024 * 1024 * get_space_allowed();
	$dirName = constant( "ABSPATH" ) . constant( "UPLOADS" );
	$dirsize = get_dirsize($dirName) ;
	if( $size > $spaceAllowed - $dirsize ) {
		return $spaceAllowed - $dirsize; // remaining space
	} else {
		return $size; // default
	}
}
add_filter( 'import_upload_size_limit', 'fix_import_form_size' );

if ( !function_exists('graceful_fail') ) :
function graceful_fail( $message ) {
	die('
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Error!</title>
<style type="text/css">
img {
	border: 0;
}
body {
line-height: 1.6em; font-family: Georgia, serif; width: 390px; margin: auto;
text-align: center;
}
.message {
	font-size: 22px;
	width: 350px;
	margin: auto;
}
</style>
</head>
<body>
<p class="message">' . $message . '</p>
</body>
</html>
	');
}
endif;

/* Delete blog */

class delete_blog {
	function delete_blog() {
		$this->reallydeleteblog = false;
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('admin_footer', array(&$this, 'admin_footer'));
	}

	function admin_footer() {
		global $wpdb;

		if( $this->reallydeleteblog == true ) {
			wpmu_delete_blog( $wpdb->blogid ); 
		}
	}

	function admin_menu() {
		add_submenu_page('options-general.php', __('Delete Blog'), __('Delete Blog'), 'manage_options', 'delete-blog', array(&$this, 'plugin_content'));
	}

	function plugin_content() {
		global $current_blog, $current_site;
		$this->delete_blog_hash = get_settings('delete_blog_hash');
		echo '<div class="wrap"><h2>' . __('Delete Blog') . '</h2>';
		if( $_POST['action'] == "deleteblog" && $_POST['confirmdelete'] == '1' ) {
			$hash = substr( md5( $_SERVER['REQUEST_URI'] . time() ), 0, 6 );
			update_option( "delete_blog_hash", $hash );
			$url_delete = get_option( "siteurl" ) . "/wp-admin/options-general.php?page=delete-blog&h=" . $hash;
			$msg = __("Dear User,
You recently clicked the 'Delete Blog' link on your blog and filled in a 
form on that page.
If you really want to delete your blog, click the link below. You will not
be asked to confirm again so only click this link if you are 100% certain:
URL_DELETE

If you delete your blog, please consider opening a new blog here
some time in the future! (But remember your current blog and username 
are gone forever.)

Thanks for using the site,
Webmaster
SITE_NAME
");
			$msg = str_replace( "URL_DELETE", $url_delete, $msg );
			$msg = str_replace( "SITE_NAME", $current_site->site_name, $msg );
			wp_mail( get_option( "admin_email" ), "[ " . get_option( "blogname" ) . " ] ".__("Delete My Blog"), $msg );
			?>
			<p><?php _e('Thank you. Please check your email for a link to confirm your action. Your blog will not be deleted until this link is clicked.') ?></p>
			<?php
		} elseif( isset( $_GET['h'] ) && $_GET['h'] != '' && get_option('delete_blog_hash') != false ) {
			if( get_option('delete_blog_hash') == $_GET['h'] ) {
				$this->reallydeleteblog = true;
				echo "<p>" . sprintf(__('Thank you for using %s, your blog has been deleted. Happy trails to you until we meet again.'), $current_site->site_name) . "</p>";
			} else {
				$this->reallydeleteblog = false;
				echo "<p>" . __("I'm sorry, the link you clicked is stale. Please select another option.") . "</p>";
			}
		} else {
?>
			<p><?php printf(__('If you do not want to use your %s blog any more, you can delete it using the form below. When you click <strong>Delete My Blog</strong> you will be sent an email with a link in it. Click on this link to delete your blog.'), $current_site->site_name); ?></p>
			<p><?php _e('Remember, once deleted your blog cannot be restored.') ?></p>
			<form method='post' name='deletedirect'>
			<input type="hidden" name="page" value="<?php echo $_GET['page'] ?>" />
			<input type='hidden' name='action' value='deleteblog' />
			<p><input id='confirmdelete' type='checkbox' name='confirmdelete' value='1' /> <label for='confirmdelete'><strong><?php printf( __("I'm sure I want to permanently disable my blog, and I am aware I can never get it back or use %s again."), $current_blog->domain); ?></strong></label></p>
			<p class="submit"><input type='submit' value='<?php _e('Delete My Blog Permanently &raquo;') ?>' /></p>
			</form>
<?php
		}
		echo "</div>";
	}
}

$delete_blog_obj = new delete_blog();

/* Global Categories */

function global_terms( $term_id, $tt_id ) {
	global $wpdb;

	$term_id = intval( $term_id );
	$c = $wpdb->get_row( "SELECT * FROM $wpdb->terms WHERE term_id = '$term_id'" );

	$global_id = $wpdb->get_var( "SELECT cat_ID FROM $wpdb->sitecategories WHERE category_nicename = '" . $wpdb->escape( $c->slug ) . "'" );

	if ( $global_id == null ) {
		$wpdb->query( "INSERT INTO $wpdb->sitecategories ( cat_name, category_nicename ) VALUES ( '" . $wpdb->escape( $c->name ) . "', '" . $wpdb->escape( $c->slug ) . "' )" );
		$global_id = $wpdb->insert_id;
	}

	if ( $global_id == $term_id )
		return $global_id;

	if( get_option( 'default_category' ) == $term_id )
		update_option( 'default_category', $global_id );
	$wpdb->query( "UPDATE $wpdb->terms SET term_id = '$global_id' WHERE term_id = '$term_id'" ); 
	$wpdb->query( "UPDATE $wpdb->term_taxonomy SET term_id = '$global_id' WHERE term_id = '$term_id'" );
	$wpdb->query( "UPDATE $wpdb->term_taxonomy SET parent = '$global_id' WHERE parent = '$term_id'" );

	clean_term_cache($term_id);

	return $global_id; 
}   
add_filter( 'term_id_filter', 'global_terms', 10, 2 ); // taxonomy specific filter

function choose_primary_blog() {
	global $current_user;
	?>
	<table class="form-table">
	<tr>
		<th scope="row"><?php _e('Primary Blog'); ?></th>
		<td>
		<?php
		$all_blogs = get_blogs_of_user( $current_user->ID );
		if( count( $all_blogs ) > 1 ) {
			$primary_blog = get_usermeta($current_user->ID, 'primary_blog');
			?>
			<select name="primary_blog">
				<?php foreach( (array) $all_blogs as $blog ) { ?>
					<option value='<?php echo $blog->userblog_id ?>'<?php if( $primary_blog == $blog->userblog_id ) echo ' selected="selected"' ?>>http://<?php echo $blog->domain.$blog->path ?></option>
				<?php } ?>
			</select>
			<?php
		} else {
			echo $_SERVER['HTTP_HOST'];
		}
		?>
		</td>
	</tr>
	</table>
	<?php	
}
add_action( 'profile_personal_options', 'choose_primary_blog' );

function redirect_this_site( $hosts ) {
	global $current_site;
	return array( $current_site->domain );
}
add_filter( 'allowed_redirect_hosts', 'redirect_this_site' );

function upload_is_file_too_big( $upload ) {
	if( is_array( $upload ) == false || defined( 'WP_IMPORTING' ) )
		return $upload;
	if( strlen( $upload[ 'bits' ] )  > ( 1024 * get_site_option( 'fileupload_maxk', 1500 ) ) ) {
		return sprintf(__( "This file is too big. Files must be less than %dKb in size.<br />" ), get_site_option( 'fileupload_maxk', 1500 )); 
	}

	return $upload;
}
add_filter( "wp_upload_bits", "upload_is_file_too_big" );

function safecss_filter_attr( $css, $element ) {
	$css = wp_kses_no_null($css);
	$css = str_replace(array("\n","\r","\t"), '', $css);
	$css_array = split( ';', trim( $css ) );
	$allowed_attr = apply_filters( 'safe_style_css', array( 'text-align', 'margin', 'color', 'float', 
	'text-direction', 'font', 'font-family', 'font-size', 'font-style', 'font-variant', 'font-weight', 'height',
	'margin-bottom', 'margin-left', 'margin-right', 'margin-top', 'padding', 'padding-bottom',
	'padding-left', 'padding-right', 'padding-top', 'width', 'border', 'vertical-align', 'text-decoration' ) );
	$css = '';
	foreach( $css_array as $css_item ) {
		if( $css_item == '' )
			continue;
		$css_item = trim( $css_item );
		$found = false;
		if( strpos( $css_item, ':' ) === false ) {
			$found = true;
		} elseif( in_array( substr( $css_item, 0, strpos( $css_item, ':' ) ), $allowed_attr ) ) {
			$found = true;
		}
		if( $found ) {
			if( $css != '' )
				$css .= ';';
			$css .= $css_item;
		}
	}

	return $css;
}

function wordpressmu_authenticate_siteadmin( $user, $password ) {
	if( is_site_admin( $user->user_login ) == false && ( $primary_blog = get_usermeta( $user->user_id, "primary_blog" ) ) ) {
		$details = get_blog_details( $primary_blog );
		if( is_object( $details ) && $details->spam == 1 ) {
			return new WP_Error('blog_suspended', __('Blog Suspended.'));
		}
	}
	return $user;
}
add_filter( 'wp_authenticate_user', 'wordpressmu_authenticate_siteadmin', 10, 2 );

function wordpressmu_wp_mail_from( $email ) {
	if( strpos( $email, 'wordpress@' ) !== false )
		$email = get_option( 'admin_email' );
	return $email;
}
add_filter( 'wp_mail_from', 'wordpressmu_wp_mail_from' );

/*
XMLRPC getUsersBlogs() for a multiblog environment
http://trac.mu.wordpress.org/attachment/ticket/551/xmlrpc-mu.php
*/
function wpmu_blogger_getUsersBlogs($args) {
	$site_details = get_blog_details( 1, true );
	$domain = $site_details->domain;
	$path = $site_details->path . 'xmlrpc.php';

	$rpc = new IXR_Client("http://{$domain}{$path}");
	$rpc->query('wp.getUsersBlogs', $args[1], $args[2]);
	$blogs = $rpc->getResponse();

	if ( isset($blogs['faultCode']) ) {
		return new IXR_Error($blogs['faultCode'], $blogs['faultString']);
	}

	if ( $_SERVER['HTTP_HOST'] == $domain && $_SERVER['REQUEST_URI'] == $path ) {
		return $blogs;
	} else {
		foreach ( (array) $blogs as $blog ) {
			if ( strpos($blog['url'], $_SERVER['HTTP_HOST']) )
				return array($blog);
		}
		return array();
	}
}

function attach_wpmu_xmlrpc($methods) {
	$methods['blogger.getUsersBlogs'] = 'wpmu_blogger_getUsersBlogs';
	return $methods;
}
add_filter('xmlrpc_methods', 'attach_wpmu_xmlrpc');

/*
Users
*/
function promote_if_site_admin(&$user) {
    if ( !is_site_admin( $user->user_login ) )
        return;

    global $wpdb;
    $level = $wpdb->prefix . 'user_level';
    $user->{$level} = 10;
    $user->user_level = 10;
    $cap_key = $wpdb->prefix . 'capabilities';
    $user->{$cap_key} = array( 'administrator' => '1' );
}

if( is_object( $wp_object_cache ) ) {
	$wp_object_cache->global_groups = array ('users', 'userlogins', 'usermeta', 'site-options', 'site-lookup', 'blog-lookup', 'blog-details', 'rss');
	$wp_object_cache->non_persistent_groups = array('comment', 'counts');
}

function mu_locale( $locale ) {
	if( defined('WP_INSTALLING') == false ) {
		$mu_locale = get_option('WPLANG');
		if( $mu_locale === false )
			$mu_locale = get_site_option('WPLANG');

		if( $mu_locale !== false )
			return $mu_locale;
	}
	return $locale;
}
add_filter( 'locale', 'mu_locale' );

function signup_nonce_fields() {
	$id = mt_rand();
	echo "<input type='hidden' name='signup_form_id' value='{$id}' />";
	wp_nonce_field('signup_form_' . $id, '_signup_form', false);
}
add_action( 'signup_hidden_fields', 'signup_nonce_fields' );

function signup_nonce_check( $result ) {
	if( !strpos( $_SERVER[ 'PHP_SELF' ], 'wp-signup.php' ) )
		return $result;

	if ( wp_create_nonce('signup_form_' . $_POST[ 'signup_form_id' ]) != $_POST['_signup_form'] )
		wp_die( 'Please try again!' );

	return $result;
}
add_filter( 'wpmu_validate_blog_signup', 'signup_nonce_check' );
add_filter( 'wpmu_validate_user_signup', 'signup_nonce_check' );

function maybe_redirect_404() {
	global $wpdb;
	if( is_main_blog() && is_404() && defined( 'NOBLOGREDIRECT' ) && constant( 'NOBLOGREDIRECT' ) != '' ) {
		header( "Location: " . constant( 'NOBLOGREDIRECT' ) );
		die();
	}
}
add_action( 'template_redirect', 'maybe_redirect_404' );

function remove_tinymce_media_button( $buttons ) {
	unset( $buttons[ array_search( 'media', $buttons ) ] );
	return $buttons;
}
add_filter( 'mce_buttons_2', 'remove_tinymce_media_button' );

function add_existing_user_to_blog() {
	if( false !== strpos( $_SERVER[ 'REQUEST_URI' ], '/newbloguser/' ) ) {
		$parts = explode( '/', $_SERVER[ 'REQUEST_URI' ] );
		$key = array_pop( $parts );
		if( $key == '' )
			$key = array_pop( $parts );
		$details = get_option( "new_user_" . $key );
		if( is_array( $details ) ) {
			add_user_to_blog( '', $details[ 'user_id' ], $details[ 'role' ] );
			do_action( "added_existing_user", $details[ 'user_id' ] );
			wp_die( 'You have been added to this blog. Please visit the <a href="' . site_url() . '">homepage</a> or <a href="' . site_url( '/wp-admin/' ) . '">login</a> using your username and password.' );
		}
	}
}
add_action( 'init', 'add_existing_user_to_blog' );
?>
