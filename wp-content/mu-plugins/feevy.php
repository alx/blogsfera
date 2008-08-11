<?php
/*
Plugin Name: Feevy
Plugin URI: http://www.feevy.com
Description: Feevy plugin for Wordpress Mu
Version: 1.0
Author: Alexandre Girard
Author URI: http://blog.alexgirard.com
*/



/*
* =========
*  HEADER
* =========
*/
include_once( ABSPATH . 'wp-content/mu-plugins/feevy_soup.php');
define('API_KEY', get_site_option('api_code'));		# Your Feevy api key, available at: http://feevy.com/api/view_key
define('FEEVY_CODE', get_site_option('feevy_number'));	# Your feevy code, the number appearing in your Feevy javascript code: http://feevy.com/admin/

add_action('admin_menu', 'add_menu');

function add_menu(){
	if (is_site_admin()){
		add_submenu_page('wpmu-admin.php', 'Feevy admin', 'Feevy admin', 8, 'wpmu-feevy.php');
	}
}

/*
* ===========
*  Feevy API 
* ===========
*/

/**
* Get blog details on feevy
* Parameters: 
* 	- $blog_id: id of the blog to get on feevy
*/
function feevy_get_blog($blog_id) {
	
	if(!API_KEY)
		return null;
		
	switch_to_blog($blog_id);
	
	// Set request to Feevy API
    $api = new feevySoup;
    $api->api_key = API_KEY;
    $api->type = 'get_feed';
	$api->params = array('feed_url' => "http://feevy.bbvablogs.com/feed/");
	
	// Execute request
	$content = $api->get_content();
	
	restore_current_blog();
	
	return $content['result']['feed'];
}

/**
* Add blog to feevy
* Parameters: 
* 	- $blog_id: id of the blog to add to feevy
*/
function feevy_add_blog($new_blog_id) {
	
	if(!API_KEY)
		return null;
		
	switch_to_blog($blog_id);
	
	// Set request to Feevy API
    $api = new feevySoup;
    $api->api_key = API_KEY;
    $api->type = 'add_feed';
	$api->params = array('href' => get_option('home'), 'url' => get_feed_link('rss2'));
	
	// Execute request
	$api->get_content();
	
	restore_current_blog();
}



/**
* Update blog tags on feevy
* Parameters: 
* 	- $blog_id: id of the blog to update
*	- $tags: tags list to set
*/
function feevy_update_tag($blog_id, $tags){

	if(!API_KEY)
		return null;
		
	// Get blog from blog_id
	$blog_details = get_blog_details($blog_id);
	
	if(strlen($blog_details->domain) == 0 or $blog_details->deleted == 1 or $blog_details->spam == 1)
		return false;
	
	$feed_url = $blog_details->siteurl."/feed/";
	
	// Set request to Feevy API
    $api = new feevySoup;
    $api->api_key = API_KEY;
    $api->type = 'edit_tags';
	$api->params = array('feed_url' => $feed_url, 
						 'tag_list' => $tags);
	
	// Execute request
	$api->get_content();
}



/**
* Update blog tags on feevy when user edit his profile
* Parameters: 
* 	- $user_id: id of the blog to update
*/
function feevy_update_tag_from_user($blog_id){
	
	// Get first user from blog
	$user = array_pop(get_users_of_blog($blog_id));
	
	// Build tag list
	$tags = user_meta_to_feevy_tags($user->user_id);
	
	// update feevy 
	feevy_update_tag($blog_id, $tags);
}



/**
* Build tags list from user metadata
* Parameters: 
* 	- $user_id: user to fech metadata from
*/
function user_meta_to_feevy_tags($user_id) {
	$tags = "";
	
	// collect tags from user
	$pais = get_usermeta( $user_id, "pais" );
	if(strlen($pais) > 0)
		$tags .= "+$pais";
		
	// You might want to check count($blog_users) to avoid corporative sexuality 
	$sex = get_usermeta( $user_id, "genero" );
	if(strcmp($sex,'Masculino') == 0){
		$tags .= "+Hombre";
	} 
	if(strcmp($sex,'Femenino') == 0){
		$tags .= "+Mujer";
	}
	
	// Area tag
	$area = get_usermeta($user_id, 'area');
	if(strlen($area) > 0)
		$tags .= "+$area";
	
	// Unidad tag
	$unidad = get_usermeta($user_id, 'unidad');
	if(strlen($unidad) > 0)
		$tags .= "+$unidad";
	
	return preg_replace('/\+(.*)/','$1',$tags);
}


/**
* Update blog avatar on feevy
* Parameters: 
* 	- $user_id: user to fech metadata from
*/
function feevy_update_avatar(){

	if(!API_KEY)
		return null;
		
	global $user_ID;
	
	$avatar_url = author_image_path($user_ID, false);
	
	// Check if user has an avatar
	if(strlen($avatar_url) > 0){
		
		// Get list of user blogs
		$blogs = get_blogs_of_user($user_ID);
		
		// Set avatar for each blog
		foreach($blogs as $blog){
			
			$user_count = count(get_users_of_blog( $blog->userblog_id ));
			
			// Verify blog url
			if($user_count == 1 and strlen($blog->siteurl) > 0) {
				
				// Set request to Feevy API
				$api = new feevySoup;
			    $api->api_key = API_KEY;
			    $api->type = 'edit_avatar';
				$api->params = array('feed_url' => "$blog->siteurl/feed/", 
									 'avatar_url' => $avatar_url);

				// Execute request
				$api->get_content();
			}
		}
	}
}



/**
* Ping Feevy when a new post is published
*/
function feevy_ping(){
	global $wpdb;
	switch_to_blog($wpdb->blogid);
	$params = array('http' => array('method' => 'POST', 'content' => 'url='.urlencode(get_feed_link('rss2'))));
    @fopen('http://www.feevy.com/ping/update', 'rb', false, stream_context_create($params));                                                           
	restore_current_blog();
}



/*
* ==============
*  Feevy script 
* ==============
*/

/**
* Generate javascript code to display Feevy
* Parameters: 
* 	- $display: return value only if false
*	- $style: style to display feevy with
*	- $_POST: set area, unidad, pais and sexo to display
*/
function feevy_code($display = true, $style = "white"){

	if(!FEEVY_CODE)
		return null;
		
	// Initialize feevy code
	$feevy = "<script type='text/javascript' src='http://www.feevy.com/code/".FEEVY_CODE;
	
	// Close code
	$feevy .= "/$style'></script>";
	
	if($display){
		echo $feevy;
	} else {
		return $feevy;
	}
}

/*
* ===============
*  Feevy options 
* ===============
*/

/**
* Add Feevy option panel
*/
function feevy_options() {
	if (function_exists('add_options_page')) {
		add_options_page(
			__('Feevy Portal Options')
			, __('Feevy Portal')
			, 10
			, basename(__FILE__)
			, 'feevy_options_form'
		);
	}
}

/**
* Print Feevy option panel form
*/
function feevy_options_form() {
	global $blog_id;
	$feed = feevy_get_blog($blog_id);
	print('
		<div class="wrap">
			<h2>'.__('Feevy Portal Options').'</h2>
			<p>You can update your Feevy options for your blog here, they will then appear in <a href="http://feevy.bbvablogs.com">Feevy Portal</a>.</p>
			<form name="feevy_portal" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=feevy.php" method="post">
				'.wp_nonce_field("feevy-update-options").'
				<input type="hiddent" name="feevy_portal_update" value="feevy_options_update" id="feevy_options_update">
				<fieldset class="options">
					<legend>Tag list</legend>
				</fieldset>
				'.add_avatar_fields($feed['avatar']).'
				<p class="submit">
					<input type="submit" name="submit" value="'.__('Update Your Feevy').'" />
				</p>
			</form>
		</div>
	');
}

function add_avatar_fields($current_avatar) {
	global $user_ID, $image_extensions, $profile_picture_options;
	// build extension check string for the js
	$image_extensions_array = explode(' ', $image_extensions);
	$checkstr = "";
	foreach ($image_extensions_array as $count => $exe) {
		$checkstr .= "(ext != '.$exe') && ";
	}
	$checkstr = rtrim($checkstr, ' && ');

	// HTML GUI, js changes form encoding and adds error check
	$form = "<script type='text/javascript' language='javascript'>
		<!--
		
		function uploadPic() {
			document.profile.enctype = 'multipart/form-data';
			var upload = document.profile.picture.value;
			upload = upload.toLowerCase();
			var ext = upload.substring((upload.length-4),(upload.length));
				if ($checkstr){
					alert('Please upload an image with one of the following extentions: $image_extensions');
					
				}
		}
		//-->
		</script>
		<fieldset class='options'>
		<legend>Fotograf&iacute;a</legend>
		<p>Actual: <br />
		<img src='$current_avatar' /><br /></p>
		<p>Suba una nueva fotograf&iacute;a:  <input type='file' name='picture' onchange='uploadPic();' /><br />
			Nota: <strong>Las im&aacute;genes deben estar en formato '.jpg .gif .png'. Si el tama&ntilde;o de la imagen excede el maximo, ésta ser&aacute; automaticamente rechazada.</strong><br />
			Compatibilidad con navegadores: esta opci&aacute;n est&aacute; inhabilitada para <strong>Internet Explorer</strong>. Estamos trabajando en ello, mientras tanto recomendamos utilizar un navegador que respete los est&aacute;ndares como <a href='www.mozilla.com/firefox/'>Firefox</a> o <a href='http://opera.com'>Opera</a>.
		</p>
		</fieldset>";
	return $form;
}


//*** INTERNAL FUNCTION: stores pic submitted via profile editing page
function upload_feevy_pic() {
	global $image_dir, $user_ID, $image_extensions, $profile_picture_options;
	
	$raw_name = (isset($_FILES['picture']['name'])) ? $_FILES['picture']['name'] : "";
	
	// if file was sumbitted, continue
	if ($raw_name != "") {
		// build the path and filename 		
		$clean_name = ereg_replace("[^a-z0-9._]", "", ereg_replace(" ", "_", ereg_replace("%20", "_", strtolower($raw_name))));
		$file_ext = substr(strrchr($clean_name, "."), 1);
		$file_path = clean_path(ABSPATH . '/' . $image_dir . '/' . $user_ID . '.' . $file_ext);
	// store file
		if(filesize($_FILES['picture']['tmp_name']) > $profile_picture_options['maximum_size'] * 1000)
		{
			unlink($_FILES['picture']['tmp_name']);
			die("<p><strong>Error:</strong> Profile pic size is above the maximum size " . $profile_picture_options['maximum_size'] . "KB.</code></p><p>Please <a href='javascript:history.go(-1)'>go back</a> and try again.");//Stolen from Andrew Ferguson 
			return;
		}
		
		$dimensions = getimagesize($_FILES['picture']['tmp_name']);
		
		if (extension_loaded('gd') && function_exists('gd_info')) {
				
			switch ($file_ext) {
				case "jpg": $image = imagecreatefromjpeg($_FILES['picture']['tmp_name']);
				break;
		
				case "png": $image = imagecreatefrompng($_FILES['picture']['tmp_name']);
				break;
		
				case "gif": $image = imagecreatefromgif($_FILES['picture']['tmp_name']);
				break;
				
				default:
				unlink ($file_path);
        		die("<p><strong>Error:</strong> Profile pic size is larger than Maximum Width/Height of " . $profile_picture_options['maximum_width'] . " pixels and cannot be resized.</code></p><p>Please <a href='javascript:history.go(-1)'>go back</a> and try again.");//Stolen from Andrew Ferguson 
				break;
			}
			
			if (isset($image))//Copied from PHP Manuals / with inspiration from myGallery
			{
				if($dimensions[0] > $profile_picture_options['maximum_width'] || $dimensions[1] > $profile_picture_options['maximum_width']){
				if ($dimensions[0] >= $dimensions[1]) {
        			$resize_x = $profile_picture_options['maximum_width'];
          			$resize_y = (int) ($dimensions[1]*($profile_picture_options['maximum_width']/$dimensions[0]));
        		} else {
        			$resize_x = (int) ($dimensions[0]*($profile_picture_options['maximum_width']/$dimensions[1]));
          			$resize_y = $profile_picture_options['maximum_width'];
        		}
        		if ($file_ext=='gif') {
					$new_image = imagecreate($resize_x, $resize_y);
				} else {
					$new_image = imagecreatetruecolor($resize_x, $resize_y);
				}  
        		imagecopyresampled($new_image, $image, 0, 0, 0, 0, $resize_x, $resize_y, $dimensions[0], $dimensions[1]);
				$image = $new_image;
				}
				
				// Clean previous files
				unlink(clean_path(ABSPATH . '/' . $image_dir . '/' . $user_ID . '.gif'));
				unlink(clean_path(ABSPATH . '/' . $image_dir . '/' . $user_ID . '.jpg'));
				unlink(clean_path(ABSPATH . '/' . $image_dir . '/' . $user_ID . '.png'));
				
				switch ($file_ext) {
				case "jpg":imagejpeg($image, "$file_path",80);
				break;
				case "png": imagepng($image, "$file_path",8);
				break;
				case "gif": imagegif($image, "$file_path");
				break;
				}
			}
    	} else {	
    		unlink($_FILES['picture']['tmp_name']);
        	die("<p><strong>Error:</strong> Profile pic size is larger than Maximum Width/Height of " . $profile_picture_options['maximum_width'] . " pixels and cannot be resized.</code></p><p>Please <a href='javascript:history.go(-1)'>go back</a> and try again.");//Stolen from Andrew Ferguson 
		}
	} else {
		return false;
	}
}

/*
* ================
*  Plugin actions 
* ================
*/

if(isset($_POST['feevy_portal_update'])) {
	upload_feevy_pic();
	feevy_update_avatar();
	feevy_update_tag();
}

//add_action('admin_menu', 'feevy_options');

add_action('wpmu_new_blog','feevy_add_blog');
add_action('wpmu_new_blog','feevy_update_avatar');

add_action('profile_update','feevy_update_avatar');

add_action('publish_post', 'feevy_ping');

// Blog tagging with blog or user info. Activate when Extra_Fields plugin ready
//add_action('wpmu_new_blog','feevy_update_tag');
//add_action('profile_update','feevy_update_tag_from_user');
?>
