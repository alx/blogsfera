<?php
/*
Plugin Name: WPMU Author Profile Pics (Avatars)
Plugin URI: http://geekgrl.net/2007/01/02/profile-pics-plugin-release/
Description: Adds picture to Author profile
Version: 0.1
Author: Hannah Gray (heavily modified for WPMU as an Avatar system WDuluoz)
Author URI: http://geekgrl.net
*/
//WDuluoz: Changed all functions to site also modify to be more avatar system
// Get stored options -- substitute defaults if none exist
$profile_picture_options = get_site_option("profile_picture_options");
$image_dir = (isset($profile_picture_options['image_dir']) && ($profile_picture_options['image_dir'] != '') ? $profile_picture_options['image_dir'] : '/wp-content/avatars/');
$image_extensions = (isset($profile_picture_options['image_extensions']) && ($profile_picture_options['image_extensions'] != '')?  $profile_picture_options['image_extensions'] : 'gif png jpg jpeg');
$image_default = (isset($profile_picture_options['image_default']) && ($profile_picture_options['image_default'] != '') ?  $profile_picture_options['image_default'] : 'default.png');
$gravatar_width = (isset($profile_picture_options['gravatar_width']) && ($profile_picture_options['gravatar_width'] != '') ?  $profile_picture_options['gravatar_width'] : '80');

// Add actions/filters to appropriete hooks
add_action('show_user_profile', 'add_userpic_fields');
add_action('profile_update','upload_pic',1);
add_action('admin_menu', 'profile_picture_config');
add_filter('get_comment_author_link', 'pn_get_avatar');//Stolen From Comvatars
add_filter('comment_author', 'pn_get_avatar');//Stolen from Comvatars

//*** GUI FUNCTION: add menu item for plugin config to Options page
function profile_picture_config() {
		get_currentuserinfo();
		if (!is_site_admin()) return false;
		add_submenu_page('wpmu-admin.php', 'Profile Picture Options', 'Profile Pictures', 10, 'wpmu_profile_picture', 'profile_picture_conf_page');
	}

//*** GUI FUNCTION: Show config form
function profile_picture_conf_page() {
	global $image_dir, $image_extensions, $gravatar_width, $image_default, $profile_picture_options;
	require_once('admin.php');
	require_once('upload-functions.php');

	// if submit was pressed, process config data
	if (!is_site_admin()) die(__('<p>You do not have permission to access this page.</p>'));
	if ( isset($_POST['submit'])) {
		// check user permissions
		if ( !current_user_can('manage_options') ) {
			die(__('Cheatin&#8217; uh?'));
		// if okay, store data
		} else {
			$profile_picture_options = array();
			$profile_picture_options['image_extensions'] = (isset($_POST['image_extensions']) ? strtolower($_POST['image_extensions']) : '');
			$profile_picture_options['image_dir'] = (isset($_POST['image_dir']) ? $_POST['image_dir'] : '');
			$profile_picture_options['image_default'] = (isset($_POST['image_default']) ? $_POST['image_default'] : '');
			$profile_picture_options['default_act'] = (isset($_POST['default_act']) ? $_POST['default_act'] : '');
			$profile_picture_options['maximum_width'] = (isset($_POST['maximum_width']) ? $_POST['maximum_width'] : '');
			$profile_picture_options['maximum_size'] = (isset($_POST['maximum_size']) ? $_POST['maximum_size'] : '');
			$profile_picture_options['gravatar_width'] = (isset($_POST['gravatar_width']) ? $_POST['gravatar_width'] : '');
			$profile_picture_options['link_profile'] = (isset($_POST['link_profile']) ? $_POST['link_profile'] : '');
			update_site_option('profile_picture_options', $profile_picture_options);
			 ?>		 
	<div id="message" class="updated fade"><p><?php _e('Options saved.') ?></p></div>
<?php	
	$profile_picture_options = get_site_option("profile_picture_options");
		}
	// if submit not pressed, display config options
	} elseif (isset($_POST['delete_avatar'])) {
		foreach ($_POST  as $key => $value){;
			if ($value == 'on'){
				$image_extensions_array = explode(' ', $image_extensions);
				foreach ($image_extensions_array as $image_extension) {
					$old_pic_path = clean_path(ABSPATH . '/' . $image_dir . '/' . $key . '.' . $image_extension);
					if ( file_exists($old_pic_path) ) { 
							unlink($old_pic_path);
					}
				}
			}
		}
?>
	<div id="message" class="updated fade"><p><?php _e('Avatars Deleted saved.') ?></p></div>
<?php	
	$profile_picture_options = get_site_option("profile_picture_options");//Added more options
	}
?>
<div class="wrap">
	<h2>Profile Picture Options</h2>	
		<form action="" method="post" id="picture_uploader" style="margin: auto;">
		<p><b>Profile Pics Upload Directory: * </b><input size="45" name='image_dir' value='<?php _e(($profile_picture_options['image_dir'] == "") ? "wp-content/avatars/" : $profile_picture_options['image_dir']); ?>' style="font-family: 'Courier New', Courier, mono;" /><br />
		Recommended: wp-content/avatars/  &nbsp; *must be set to chmod 777 </p>

		<p><b>Allowed File Extensions: </b><input size="45" name='image_extensions' value='<?php _e(($profile_picture_options['image_extensions'] == "") ? 'png gif jpg' : $profile_picture_options['image_extensions']); ?>' style="font-family: 'Courier New', Courier, mono;" /><br />
		Seperate each three digit extension with a space; field is case-insensitive</p>
		
		<p><b>Maximum Width/Height of Profile Pics: </b><input size="45" name='maximum_width' value='<?php _e(($profile_picture_options['maximum_width'] == "") ? '80' : $profile_picture_options['maximum_width']); ?>' style="font-family: 'Courier New', Courier, mono;" /><br />
		Width/Height in px</p>
		
		<p><b>Maximum Filesize of Profile Pics: </b><input size="45" name='maximum_size' value='<?php _e(($profile_picture_options['maximum_size'] == "") ? '80' : $profile_picture_options['maximum_size']); ?>' style="font-family: 'Courier New', Courier, mono;" /><br />
		Size in KB</p>
		
		<p><b>Standard Width for Comment Author "Gravatar": </b><input size="45" name='gravatar_width' value='<?php _e(($profile_picture_options['gravatar_width'] == "") ? '80' : $profile_picture_options['gravatar_width']); ?>' style="font-family: 'Courier New', Courier, mono;" /><br />
		Width in px</p>
		
		<p><b>Default Image: </b><input size="45" name='image_default' value='<?php _e(($profile_picture_options['image_default'] == "") ? 'default.png': $profile_picture_options['image_default']); ?>' style="font-family: 'Courier New', Courier, mono;" /><br />
		Must be stored in the profile pics directory specified above</p>
		
		<p><b>Add Profile Pics to Comment Author link: </b><input type="checkbox" name="default_act" value="yes"	<?php if ($profile_picture_options['default_act'] == 'yes'){ ?> checked="checked"<?php } ?>style="font-family: 'Courier New', Courier, mono;"  />
		<b>Link to Profile: </b><input type="checkbox" name="link_profile" value="yes"	<?php if ($profile_picture_options['link_profile'] == 'yes'){ ?> checked="checked"<?php } ?>style="font-family: 'Courier New', Courier, mono;"  />
		
		</p>
		
		<p class="submit"><input type="submit" name="submit" value="<?php _e('Update Settings&raquo;'); ?>" /></p>
	</form>
</div>
<div class='wrap'>
<h2><?php _e('Uploaded Avatars'); ?></h2>
<?php _e('(to delete check the box below the avatar, then click Delete Avatars.)');?>
<?php //Added Gallery
echo pn_gallery_display();

echo "<div class='clear'></div></div>";

}
//*** GUI FUNCTION: displays "add picture" box when editing your profile
function add_userpic_fields() {
	global $user_ID, $image_extensions, $profile_picture_options;
	// build extension check string for the js
	$image_extensions_array = explode(' ', $image_extensions);
	$checkstr = "";
	foreach ($image_extensions_array as $count => $exe) {
		$checkstr .= "(ext != '.$exe') && ";
	}
	$checkstr = rtrim($checkstr, ' && ');

	// HTML GUI, js changes form encoding and adds error check
	?>
		<script type="text/javascript" language="javascript">
		<!--
		
		function uploadPic() {
			document.profile.enctype = "multipart/form-data";
			var upload = document.profile.picture.value;
			upload = upload.toLowerCase();
			var ext = upload.substring((upload.length-4),(upload.length));
				if (<?php _e($checkstr) ?>){
					alert('Please upload an image with one of the following extentions: <?php _e($image_extensions); ?>');
					
				}
		}
		//-->
		</script>
		<fieldset>
		<legend>Fotograf&iacute;a</legend>
		<p>Actual: <br />
		<img src="<?php author_image_path($user_ID); ?>" /><br /></p>
		<p>Suba una nueva fotograf&iacute;a:  <input type="file" name="picture" onchange="uploadPic();" /><br />
			Nota: <strong>Las im&aacute;genes deben estar en formato ".jpg .gif .png". Si el tama&ntilde;o de la imagen excede el maximo, ésta ser&aacute; automaticamente rechazada.</strong><br />
			Compatibilidad con navegadores: esta opci&aacute;n est&aacute; inhabilitada para <strong>Internet Explorer</strong>. Estamos trabajando en ello, mientras tanto recomendamos utilizar un navegador que respete los est&aacute;ndares como <a href='www.mozilla.com/firefox/'>Firefox</a> o <a href='http://opera.com'>Opera</a>.
		</p>
		</fieldset>
	<?php
}

//*** GUI FUNCTION: displays "add picture" box when editing your profile
function add_userpic_fields_yahoo() {
	global $user_ID, $image_extensions, $profile_picture_options;
	
	// build extension check string for the js
	$image_extensions_array = explode(' ', $image_extensions);
	$checkstr = "";
	foreach ($image_extensions_array as $count => $exe) {
		$checkstr .= "(ext != '.$exe') && ";
	}
	$checkstr = rtrim($checkstr, ' && ');

	// HTML GUI, js changes form encoding and adds error check
	?>
		
		<!-- Dependencies -->
		<link rel="stylesheet" type="text/css" href="<?php echo get_option('siteurl'); ?>/wp-includes/js/yui/2.5.0/build/fonts/fonts-min.css" /> 
		<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-includes/js/yui/2.5.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
		<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-includes/js/yui/2.5.0/build/element/element-beta-min.js"></script>
		<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-includes/js/yui/2.5.0/build/json/json-min.js"></script>

		<!-- Source files -->
		<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-includes/js/yui/2.5.0/build/uploader/uploader-experimental-min.js"></script>
		
		<!-- Logger CSS and JS -->  
		<link type="text/css" rel="stylesheet" href="<?php echo get_option('siteurl'); ?>/wp-includes/js/yui/2.5.0/build/logger/assets/skins/sam/logger.css">  
		<script type="text/javascript" src="<?php echo get_option('siteurl'); ?>/wp-includes/js/yui/2.5.0/build/logger/logger-min.js"></script>
		
		<fieldset>
		<legend>Fotograf&iacute;a</legend>
		<p>Actual: <br />
		<img src="<?php author_image_path($user_ID); ?>" /></p>
		<div id="photoUploader" style="width:0px;height:0px"><br /> 
			Unable to load Flash content. Photo Uploader requires Flash Player 9.0.45 or higher.  
			You can download the latest version of Flash Player from the  
			<a href="http://www.adobe.com/go/getflashplayer">Adobe Flash Player Download Center</a>
		</div>
		
		<input type="Button" value="Buscar" onClick="browse();" /> 
		<input type="Button" value="Subir" onClick="upload();" id='button_subir'/>
		
		<script type="text/javascript">
		
			YAHOO.widget.Uploader.SWFURL = "<?php echo get_option('siteurl'); ?>/wp-includes/js/yui/2.5.0/build/uploader/assets/uploader.swf";
			var uploader = new YAHOO.widget.Uploader( "photoUploader" );
			
			uploader.addListener('fileSelect',onFileSelect)
			uploader.addListener('uploadStart',onUploadStart);
			uploader.addListener('uploadProgress',onUploadProgress);
			uploader.addListener('uploadCancel',onUploadCancel);
			uploader.addListener('uploadComplete',onUploadComplete);
			uploader.addListener('uploadCompleteData',onUploadResponse);
			uploader.addListener('uploadError', onUploadError);

			var fileList;

			function browse() { 
				uploader.clearFileList(); 
				uploader.browse(false, [{description:"Images", extensions:"*.jpg"}]);
			}
			
			function upload() { 
				if (fileList != null) { 
					for(var i in fileList) { 
						uploader.upload(i, '<?php echo get_option('siteurl'); ?>/wp-content/avatars/upload.php', "GET", {userid: "<?php echo $user_ID; ?>"}); 
					} 
				}    
			}
			
			function onFileSelect(event) { 
				fileList = event.fileList;
				if (fileList != null) {
					
				}
			}
			
		  	function onUploadStart(event) {
			}

			function onUploadProgress(event) {
			}

			function onUploadComplete(event) {
			}

			function onUploadError(event) {
			}
			
			function onUploadCancel(event) {
			}

			function onUploadResponse(event) {
			}
		</script>
		
		</fieldset>
	<?php
}

//*** INTERNAL FUNCTION: stores pic submitted via profile editing page
function upload_pic() {
	global $image_dir, $user_ID, $image_extensions, $profile_picture_options;
	
	$raw_name = (isset($_FILES['picture']['name'])) ? $_FILES['picture']['name'] : "";
	
	error_log("Profile_pic - raw_name: $raw_name");
	
	// if file was sumbitted, continue
	if ($raw_name != "") {
		// build the path and filename 		
		$clean_name = ereg_replace("[^a-z0-9._]", "", ereg_replace(" ", "_", ereg_replace("%20", "_", strtolower($raw_name))));
		$file_ext = substr(strrchr($clean_name, "."), 1);
		$file_path = clean_path(ABSPATH . '/' . $image_dir . '/' . $user_ID . '.' . $file_ext);
		
		$maximum_size = $profile_picture_options['maximum_size'] == "" ? 80 : $profile_picture_options['maximum_size'];
		error_log("Profile_pic - file_path: $file_path");
		error_log("Profile_pic - filesize: ".filesize($_FILES['picture']['tmp_name']) );
		error_log("Profile_pic - filesize max: ".$maximum_size * 1000 );
	// store file
		if(filesize($_FILES['picture']['tmp_name']) > $maximum_size * 1000)
		{
			unlink($_FILES['picture']['tmp_name']);
			die("<p><strong>Error:</strong> Profile pic size is above the maximum size " . $maximum_size . "KB.</code></p><p>Please <a href='javascript:history.go(-1)'>go back</a> and try again.");//Stolen from Andrew Ferguson 
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
				$maximum_width = $profile_picture_options['maximum_width'] == "" ? 80 : $profile_picture_options['maximum_width'];
				if($dimensions[0] > $maximum_width || $dimensions[1] > $maximum_width){
				if ($dimensions[0] >= $dimensions[1]) {
        			$resize_x = $maximum_width;
          			$resize_y = (int) ($dimensions[1]*($maximum_width/$dimensions[0]));
        		} else {
        			$resize_x = (int) ($dimensions[0]*($maximum_width/$dimensions[1]));
          			$resize_y = $maximum_width;
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

//*** TEMPLATE FUNCTION: returns requested dimension from specific image
//    USAGE: 
//		path: absolute path to image from server root', 
//		dimension: the dimension you want, can be either 'height' or width'
//		display: display results (ie. echo)? true or false
function author_image_dimensions($path, $dimension, $display = false) {
	$size = getimagesize($path);
	$width = $size[0];
	$height = $size[1];
	
	switch ($dimension) {
		case 'width':
			if ($display) { echo $width; } else { return $width; }
			break;
		case 'height':
			if ($display) { echo $height; } else { return $height; }
			break;
	}
}



//*** TEMPLATE FUNCTION: returns image for comment author
//    USAGE: 
//		authorID: id number of author
//		tags: attributes to include in img tag (optional, defaults to no tags)
function author_gravatar_tag($authorID, $tags = '', $width = '') 
{
	global $gravatar_width, $profile_picture_options;
	if ($authorID != 0) 
	{
		$path = author_image_path($authorID, false, 'absolute');
		$width = ($width != '')? $width: $gravatar_width;
		//$height = author_image_dimensions($path, 'height') * ($gravatar_width / author_image_dimensions($path, 'width'));// not needed
		$tag = '<img src="' . author_image_path($authorID, false, 'url') . '" width=' . $width . ' '. $tags . ' hspace=10 />';
		if ($profile_picture_options['link_profile'] == 'yes')
		{
				$primary_blog = get_usermeta($authorID, 'primary_blog');
				if ($primary_blog != '')
				{
				$url = get_blogaddress_by_id($primary_blog);
				}else
				{
					$url = get_blogaddress_by_id(1);
				}
			$tag = '<a href="'. $url . '?author=' . $authorID .'">' . $tag . '</a>';
		}
		
		return $tag;
	} else {
		return false;
	}
}


//*** TEMPLATE FUNCTION: returns image for author wrapped in image tag
//    USAGE: 
//		authorID: id number of author
//		tags: attributes to include in img tag (optional, defaults to no tags)
//		display: display results (ie. echo)? true or false (optional, defaults to true)
function author_image_tag($authorID, $tags = '', $display = true) {
	$path = author_image_path($authorID, false, 'absolute');
	$width = author_image_dimensions($path, 'width');
	//$height = author_image_dimensions($path, 'height');//again not needed
	$tag = '<img src="' . author_image_path($authorID, false, 'url') . '" width=' . $width . ' '. $tags . ' ' . ' id="authorpic" />';
	if ($display) { echo $tag; } else { return $tag; }
}

//*** TEMPLATE FUNCTION: returns url or absolute path to author's picture
//    USAGE: 
//		authorID: id number of author
//		display: display results (ie. echo)? true or false (optional, defaults to true)
//		type: specify what kind of path requested: 'url' or 'absolute' (optional, defaults to url)
function author_image_path($authorID, $display = true, $type = 'url') {

	switch($type) {
		case 'url' :
			$ref =  clean_path(get_settings('siteurl') . pick_image($authorID)) . '?' . time();
			if ($display) { echo $ref; } else { return $ref; }
			break;
		case 'absolute':
			$ref =  clean_path(ABSPATH . pick_image($authorID));
			if ($display) { echo $ref; } else { return $ref; }
			break;
	}
} 

function email_image_path($author_email, $display = false) {
	global $wpdb;
	$author = $wpdb->get_row("SELECT ID FROM $wpdb->users WHERE user_email = '$author_email'");
	$ref =  author_image_path($author->ID, $display, $type = 'url');
	if ($display) { echo $ref; } else { return $ref; }
}

function get_avatar_list($count = 20){
	global $image_dir, $wpdb;

	$query = "SELECT ID FROM {$wpdb->users} ORDER BY user_registered DESC LIMIT $count";

	// get last user list
	$user_list = $wpdb->get_col( $query );
	
	// Produce list of $count avatars
	foreach( (array) $user_list as $user_id) {
		
		// Get user display name
		$user = get_userdata( $user_id );
		
		// Get user active blog
		//$blog = get_active_blog_for_user( $user_id );
		//$blog->siteurl
		// User avatar url
		$avatar = author_image_path( $user_id, $display = false);
			
		// Create element
		$list .= "<li><a href='http://comunidad.bbvablogs.com/?id=".$user_id."' title='$user->display_name'>";
		$list .= "<img src='$avatar' alt='$user->display_name' width='48px' height='48px'/></a></li>";
	}
	echo "<ul class='clearfix'>$list</ul>";
}

function get_recent_avatar_list($count = 20){
	global $image_dir;
	
	$directory = ABSPATH . '/' . $image_dir . '/';
	
	// create an array to hold directory list
    $result = array();

    // create a handler for the directory
    $handler = opendir($directory);

    // keep going until all files in directory have been read
    while ($file = readdir($handler)) {

        // if $file is a picture, and not the default one
        if ((strpos($file, '.gif',1) || 
			strpos($file, '.jpg',1) || 
			strpos($file, '.png',1)) &&
			strcmp($file, 'default.png') != 0)
			// Only keep avatar number
            $result[] = substr($file, 0, -4);
    }

    // tidy up: close the handler
    closedir($handler);
	
	// Sort result
	rsort($result);
	
	// And keep only $count result
	$result = array_slice($result, 0, $count);
	
	$list = "";
	
	// Produce list of $count avatars
	foreach( $result as $user_id) {
		
		// Get user display name
		$user = get_userdata( $user_id );
		
		// Get user active blog
		$blog = get_active_blog_for_user($user_id);
		
		// User avatar url
		$avatar = author_image_path($user_id, $display = false);
			
		// Create element
		$list .= "<li><a href='$blog->siteurl' title='$user->display_name'>";
		$list .= "<img src='$avatar' alt='$user->display_name' width='48px' height='48px'/></a></li>";
	}
	echo "<ul class='clearfix'>$list</ul>";
}


//*** INTERNAL FUNCTION: strips extra slashes from paths; means user-end 
//    configuration is not picky about leading and trailing slashes
function clean_path($dirty_path) {
	$nasties = array(1 => "///", 2 => "//", 3 => "http:/");
	$cleanies = array(1 => "/", 2 => "/", 3 => "http://");
	$clean_path = str_replace($nasties, $cleanies, $dirty_path);
	return $clean_path;
}

//*** INTERNAL FUNCTION: finds the appropriete path to the author's picture
function pick_image($authorID) {
	global $image_dir, $image_extensions, $image_default;
	$image_extensions_array = explode(' ', $image_extensions);
	// look for image file based on user id
	$path = "";
	foreach ($image_extensions_array as $image_extension) {
		$path_fragment = '/' . $image_dir . '/' . $authorID . '.' . $image_extension;
		$path_to_check = clean_path(ABSPATH . $path_fragment);
		if ( file_exists($path_to_check) ) { 
			$path = $path_fragment;
			break;
		}
	}
	// if not found, use default
	if ($path == "") {
		$path = '/' . $image_dir . '/' . $image_default;
	}
	return $path;
}

function pn_gallery_display(){
	global $image_dir, $image_extensions, $image_default;
	$profile_picture_options = get_site_option("profile_picture_options");
	$file_path = clean_path(ABSPATH . '/' . $profile_picture_options['image_dir']);
	$image_path = clean_path (get_settings('siteurl') . '/' . $profile_picture_options['image_dir']);
  if (is_dir($file_path))
  {
  	$dir = opendir($file_path);
  }else
  {
		$output .= '<div  id="message" class="error fade">
			<p>'. __("Error: Directory not found.", "profile_pics") .'</p>
		</div>';
		return $output;
	}
	
	$image_extensions_array = explode(' ', $image_extensions);
	// look for image file based on user id
	$path = "";

	foreach ($image_extensions_array as $image_extension) {
		$file_types[] = $image_extension;
	}

	while (false !== ($filename = readdir($dir))) {
           $files[] = $filename;
  }
  sort($files);
    foreach ($files as $file) {
      $file_ext = substr(strrchr($file, "."), 1);
      if($file != '.' && $file != '..' && array_search($file_ext, $file_types) !== false){
      	$images[] = $file;
      }
    $output .= '<form action="" method="post" id="picture_deleter" style="margin: auto;">
								<table border=0 cellpadding=3 cellspacing=1 >';  
		}
		$row = 0;
  	$col = 0;
  	if ($images[0] != ''){
		foreach ($images as $file_name){
		if ($col == 0) {
		if ($row > 0) {
		   $output .= "</tr>\n";
		}
			$output .= "<tr bgcolor=#ffffff>\n";
	  } 
			$output .= "<td align=center valign=bottom>"; 
			$id = str_replace("_", " ", preg_replace('/^(.*)\..*$/', '\1', $file_name));
			$user_info = get_userdata($id);
			$user_name  = ( $user_info == '' ) ? ( $file_name == $image_default) ? 'Default' : "Unknown" : $user_info->display_name;
			$output .= '<img src="'. $image_path . $file_name . '" hspace = 10 vspace = 10 width ="' . $profile_picture_options['gravatar_width'] .'" title="' . ucfirst(str_replace("_", " ", preg_replace('/^(.*)\..*$/', '\1', $file_name))) . '" ><br>
			<br><input type="checkbox" name="'.ucfirst(str_replace("_", " ", preg_replace('/^(.*)\..*$/', '\1', $file_name))).'"> <b>' . $user_name . '</b>';						
			$col++;
	    if ($col >= 6) {
				$row++;
				$col = 0;
	    }
	  } 
	  
	    if ($col > 0) {
	# Put in some empty cells to fill out the row.
	while ($col < 6)
	{
	    $output .=  "<td></td>";
	    $col++;
	}
    }
    $output .=  "</tr></table>\n";
	
	$output .= '<p class="submit"><input type="submit" name="delete_avatar" value="' . _('Delete Avatars&raquo;') . '" /></p>';
	$output .= '</form>';
	} else {
		$output .= '<div  id="message" class="error fade">
			<p>'. __("Error: No Image Files.", "private_notes") .'</p>
		</div>';
	}
  closedir($dir);
	return $output;	
}

	function pn_get_avatar($s)
	{
		global $profile_picture_options, $comment;
		if (isset($comment->user_id)){
			if ($profile_picture_options['default_act'] == 'yes'){
				$avatar = author_gravatar_tag($comment->user_id,'class="gravatar"');
				$s = $avatar . $s;
			}
		}
		return $s;
	}

?>
