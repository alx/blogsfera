<?php
/*
Plugin Name: Cimy User Extra Fields
Plugin URI: http://www.cimatti.it/blog/cimy-wordpress-plugins/cimy-user-extra-fields/
Plugin Description: Add some useful fields to registration and user's info
Version: 1.1.1
Author: Marco Cimmino
Author URI: mailto:cimmino.marco@gmail.com
*/

/*

Cimy User Extra Fields - Allows adding mySQL Data fields to store/add more user info
Copyright (c) 2006-2008 Marco Cimmino

Code for drop-down support is in part from Raymond Elferink raymond@raycom.com

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.


The full copy of the GNU General Public License is available here: http://www.gnu.org/licenses/gpl.txt

*/

// added for WordPress 2.5 compatibility
global $wpdb, $old_wpdb_data_table, $wpdb_data_table, $old_wpdb_fields_table, $wpdb_fields_table, $wpdb_wp_fields_table, $cimy_uef_options, $cimy_uef_version, $is_mu, $cuef_upload_path;

if (!stristr($wp_version, "mu") === FALSE) {
	$is_mu = true;

	$old_wpdb_data_table = $wpmuBaseTablePrefix."cimy_data";
	$old_wpdb_fields_table = $wpmuBaseTablePrefix."cimy_fields";
	
	$wpdb_data_table = $wpmuBaseTablePrefix."cimy_uef_data";
	$wpdb_fields_table = $wpmuBaseTablePrefix."cimy_uef_fields";
	$wpdb_wp_fields_table = $wpmuBaseTablePrefix."cimy_uef_wp_fields";
}
else {
	$is_mu = false;

	$old_wpdb_data_table = $wpdb->prefix."cimy_data";
	$old_wpdb_fields_table = $wpdb->prefix."cimy_fields";

	$wpdb_data_table = $wpdb->prefix."cimy_uef_data";
	$wpdb_fields_table = $wpdb->prefix."cimy_uef_fields";
	$wpdb_wp_fields_table = $wpdb->prefix."cimy_uef_wp_fields";
}

$cimy_uef_options = "cimy_uef_options";
$cimy_uef_options_descr = "Cimy User Extra Fields options are stored here and modified only by admin";

/*

RULES (stored into an associative array and serialized):

- 'min_length':			[int]		=> specify min length
[only for text, textarea, password, picture, picture-url]

- 'exact_length':		[int]		=> specify exact length
[only for text, textarea, password, picture, picture-url]

- 'max_length':			[int]		=> specify max length
[only for text, textarea, password, picture, picture-url]

- 'email':			[true | false]	=> check or not for email syntax
[only for text, textarea, password]

- 'can_be_empty':		[true | false]	=> field can or cannot be empty
[only for text, textarea, password, picture, picture-url, dropdown]

- 'edit':
	'ok_edit' 				=> field can be modified
	'edit_only_if_empty' 			=> field can be modified if it's still empty
	'edit_only_by_admin' 			=> field can be modified only by administrator
	'edit_only_by_admin_or_if_empty' 	=> field can be modified only by administrator or if it's still empty
	'no_edit' 				=> field cannot be modified
[only for text, textarea, password, picture, picture-url, checkbox, radio and dropdown]
[for radio and checkbox 'edit_only_if_empty' has no effects and 'edit_only_by_admin_or_if_empty' has the same effect as edit_only_by_admin]

- 'equal_to':			[string] => field should be equal to a specify string
[all]

- 'equal_to_case_sensitive':	[true | false] => equal_to if selected can be case sensitive or not
[only for text, textarea, password, dropdown]

- 'show_in_reg':		[true | false]	=> field is visible or not in the registration
[all]

- 'show_in_profile':		[true | false]	=> field is visible or not in user's profile
[all]

- 'show_in_aeu':		[true | false]	=> field is visible or not in A&U Extended page
[all]

TYPE can be:
- 'text'
- 'textarea'
- 'password'
- 'checkbox'
- 'radio'
- 'dropdown'
- 'picture'
- 'picture-url'
- 'registration-date'

*/

$cuef_plugin_dir = dirname(__FILE__);
$cuef_plugin_name = basename(__FILE__);

if ($is_mu) {
	$cuef_plugin_path_pos = strpos($cuef_plugin_dir, "mu-plugins");
	$cuef_plugin_path = substr($cuef_plugin_dir, $cuef_plugin_path_pos + 11);
}
else {
	$cuef_plugin_path_pos = strpos($cuef_plugin_dir, "plugins");
	$cuef_plugin_path = substr($cuef_plugin_dir, $cuef_plugin_path_pos + 8);
}

if ($cuef_plugin_path == FALSE)
	$cuef_plugin_path = "";
else if ($cuef_plugin_path != "")
	$cuef_plugin_path.= "/";

require_once($cuef_plugin_dir.'/cimy_uef_db.php');
require_once($cuef_plugin_dir.'/cimy_uef_register.php');
require_once($cuef_plugin_dir.'/cimy_uef_profile.php');
require_once($cuef_plugin_dir.'/cimy_uef_functions.php');
require_once($cuef_plugin_dir.'/cimy_uef_options.php');
require_once($cuef_plugin_dir.'/cimy_uef_admin.php');

$cuef_blog_url = get_bloginfo("wpurl");
$cuef_upload_path = ABSPATH."wp-content/".$cuef_plugin_path;
$cuef_upload_webpath = $cuef_blog_url."/wp-content/".$cuef_plugin_path;
$cuef_css_webpath = $cuef_blog_url."/wp-content/plugins/".$cuef_plugin_path."css/";

$cimy_uef_name = "Cimy User Extra Fields";
$cimy_uef_version = "1.1.1";
$cimy_uef_url = "http://www.cimatti.it/blog/cimy-wordpress-plugins/cimy-user-extra-fields/";

$start_cimy_uef_comment = "<!--\n";
$start_cimy_uef_comment .= "\tStart code from ".$cimy_uef_name." ".$cimy_uef_version."\n";
$start_cimy_uef_comment .= "\tCopyright (c) 2006-2008 Marco Cimmino\n";
$start_cimy_uef_comment .= "\t".$cimy_uef_url."\n";
$start_cimy_uef_comment .= "-->\n";

$end_cimy_uef_comment = "\n<!--\n";
$end_cimy_uef_comment .= "\tEnd of code from ".$cimy_uef_name."\n";
$end_cimy_uef_comment .= "-->\n";

$wp_hidden_fields = array(
			'firstname' => array(
						'name' => "FIRSTNAME",
						'post_name' => "first_name",
						'type' => "text",
						'label' => "First name:",
						'desc' => '',
						'value' => '',
						'store_rule' => array(
								'max_length' => 100,
								'can_be_empty' => true,
								'edit' => 'ok_edit',
								'email' => false,
								'show_in_reg' => true,
								'show_in_profile' => true,
								'show_in_aeu' => true,
								),
					),
			'lastname' => array(
						'name' => "LASTNAME",
						'post_name' => "last_name",
						'type' => "text",
						'label' => "Last name:",
						'desc' => '',
						'value' => '',
						'store_rule' => array(
								'max_length' => 100,
								'can_be_empty' => true,
								'edit' => 'ok_edit',
								'email' => false,
								'show_in_reg' => true,
								'show_in_profile' => true,
								'show_in_aeu' => true,
								),
					),
			'nickname' => array(
						'name' => "NICKNAME",
						'post_name' => "nickname",
						'type' => "text",
						'label' => "Nickname:",
						'desc' => '',
						'value' => '',
						'store_rule' => array(
								'max_length' => 100,
								'can_be_empty' => true,
								'edit' => 'ok_edit',
								'email' => false,
								'show_in_reg' => true,
								'show_in_profile' => true,
								'show_in_aeu' => true,
								),
					),
			'website' => array(
						'name' => "WEBSITE",
						'post_name' => "user_url",
						'type' => "text",
						'label' => "Website:",
						'desc' => '',
						'value' => '',
						'store_rule' => array(
								'max_length' => 100,
								'can_be_empty' => true,
								'edit' => 'ok_edit',
								'email' => false,
								'show_in_reg' => true,
								'show_in_profile' => true,
								'show_in_aeu' => true,
								),
					),
			'aim' => array(
						'name' => "AIM",
						'post_name' => "aim",
						'type' => "text",
						'label' => "AIM:",
						'desc' => '',
						'value' => '',
						'store_rule' => array(
								'max_length' => 100,
								'can_be_empty' => true,
								'edit' => 'ok_edit',
								'email' => false,
								'show_in_reg' => true,
								'show_in_profile' => true,
								'show_in_aeu' => true,
								),
					),
			'yahoo' => array(
						'name' => "YAHOO",
						'post_name' => "yim",
						'type' => "text",
						'label' => "Yahoo IM:",
						'desc' => '',
						'value' => '',
						'store_rule' => array(
								'max_length' => 100,
								'can_be_empty' => true,
								'edit' => 'ok_edit',
								'email' => false,
								'show_in_reg' => true,
								'show_in_profile' => true,
								'show_in_aeu' => true,
								),
					),
			'jgt' => array(
						'name' => "JGT",
						'post_name' => "jabber",
						'type' => "text",
						'label' => "Jabber / Google Talk:",
						'desc' => '',
						'value' => '',
						'store_rule' => array(
								'max_length' => 100,
								'can_be_empty' => true,
								'edit' => 'ok_edit',
								'email' => false,
								'show_in_reg' => true,
								'show_in_profile' => true,
								'show_in_aeu' => true,
								),
					),
			);

// strong illegal charset
$strong_illegal_chars = "/(\%27)|(\/)|(\\\)|(\[)|(\])|(\')|(\")|(\<)|(\>)|(\-\-)|(\%23)|(\#)/ix";

// light illegal charset
$light_illegal_chars = "/(\%27)|(\/)|(\\\)|(\[)|(\])|(\-\-)|(\%23)|(\#)/ix";

// all available types
$available_types = array("text", "textarea", "password", "checkbox", "radio", "dropdown", "picture", "picture-url", "registration-date");

// types that should be pass registration check for equal to rule
$apply_equalto_rule = array("text", "textarea", "password", "checkbox", "radio", "dropdown");

// types that can have 'can be empty' rule
$rule_canbeempty = array("text", "textarea", "password", "picture", "picture-url", "dropdown");

// common for min, exact and max length
$rule_maxlen = array("text", "password", "textarea", "picture", "picture-url");

// common for min, exact and max length
$rule_maxlen_needed = array("text", "password", "picture", "picture-url");

// types that can have 'check for email syntax' rule
$rule_email = array("text", "textarea", "password");

// types that can admit a default value if empty
$rule_profile_value = array("text", "textarea", "password", "picture", "picture-url");

// types that can have 'equal to' rule
$rule_equalto = array("text", "textarea", "password", "checkbox", "radio", "dropdown", "picture", "picture-url", "registration-date");

// types that can have 'case (in)sensitive equal to' rule
$rule_equalto_case_sensitive = array("text", "textarea", "password", "dropdown");

$max_length_name = 20;
$max_length_label = 5000;
$max_length_desc = 5000;
$max_length_value = 5000;
$max_length_fieldset_value = 1024;

// max size in KiloByte
$max_size_file = 20000;

$fields_name_prefix = "cimy_uef_";
$wp_fields_name_prefix = "cimy_uef_wp_";

// add checks for extra fields in the registration form
add_action('register_post', 'cimy_registration_check', 10, 3);

// add extra fields to registration form
add_action('register_form', 'cimy_registration_form');

// added for WordPress MU support
add_action('signup_extra_fields', 'cimy_registration_form');
add_action('preprocess_signup_form', 'cimy_registration_check');

// add extra fields to user's profile
add_action('show_user_profile', 'cimy_extract_ExtraFields');

// add extra fields in users edit profiles (for ADMIN)
add_action('edit_user_profile', 'cimy_extract_ExtraFields');

// this hook is no more used since the one below is enough for all
//add_action('personal_options_update', 'cimy_update_ExtraFields');

// add update engine for extra fields to users edit profiles
add_action('profile_update', 'cimy_update_ExtraFields');

// function that is executed during activation of the plug-in
add_action('activate_'.$cuef_plugin_path.$cuef_plugin_name,'cimy_plugin_install');

// add update engine for extra fields to user's registration
add_action('user_register', 'cimy_register_user_extra_fields');

// function that add the submenu under 'Users'
add_action('admin_menu', 'cimy_admin_menu_custom');

// delete user extra fields data when a user is deleted
add_action('delete_user', 'cimy_delete_user_info');

// add custom login/registration css
add_action('login_head', 'cimy_uef_register_css');

function cimy_uef_register_css() {
	global $cuef_css_webpath;
	
	echo "<link rel='stylesheet' href='".$cuef_css_webpath."cimy_uef_register.css"."' type='text/css' />\n";
}

$cimy_uef_domain = 'cimy_uef';
$cimy_uef_i18n_is_setup = 0;
cimy_uef_i18n_setup();

function cimy_uef_i18n_setup() {
	global $cimy_uef_domain, $cimy_uef_i18n_is_setup, $cuef_plugin_path;

	if ($cimy_uef_i18n_is_setup)
		return;

	load_plugin_textdomain($cimy_uef_domain, 'wp-content/plugins/'.$cuef_plugin_path.'langs/');
}

function cimy_admin_menu_custom() {
	global $cimy_uef_name, $cimy_uef_domain, $is_mu, $cimy_top_menu;
	
	if (!cimy_check_admin('level_10'))
		return;
	
	if (isset($cimy_top_menu)) {
		add_submenu_page('cimy_series.php', $cimy_uef_name.": ".__("Options"), "UEF: ".__("Options"), 10, "user_extra_fields_options", 'cimy_show_options_notembedded');
		add_submenu_page('cimy_series.php', $cimy_uef_name.": ".__("Fields", $cimy_uef_domain), "UEF: ".__("Fields", $cimy_uef_domain), 10, "user_extra_fields", 'cimy_admin_define_extra_fields');
		add_submenu_page('profile.php', __('Authors &amp; Users Extended', $cimy_uef_domain), __('A&amp;U Extended', $cimy_uef_domain), 10, "au_extended", 'cimy_admin_users_list_page');
	}
	else {
		add_options_page($cimy_uef_name, $cimy_uef_name, 10, "user_extra_fields", 'cimy_admin_define_extra_fields');
		add_submenu_page('profile.php', __('Authors &amp; Users Extended', $cimy_uef_domain), __('A&amp;U Extended', $cimy_uef_domain), 10, "au_extended", 'cimy_admin_users_list_page');
	}
}

function cimy_manage_upload($input_name, $user_login, $rules, $old_file=false, $delete_file=false) {
	global $cuef_upload_path, $cuef_upload_webpath;

	$file_path = $cuef_upload_path.$user_login."/";
	$file_name = $_FILES[$input_name]['name'];
	
	// protect from site traversing
	$file_name = str_replace('../', '', $file_name);
	
	// delete old file if requested
	if ($delete_file) {
		if (is_file($file_path.$old_file))
			unlink($file_path.$old_file);
	
		$old_thumb_file = cimy_get_thumb_path($old_file);
		
		if (is_file($file_path.$old_thumb_file))
			unlink($file_path.$old_thumb_file);
	}

	if (!is_dir($file_path)) {
		if (!is_writable($cuef_upload_path))
			return "";
		
		mkdir($file_path, 0777);
		chmod($file_path, 0777);
	}
		
	// picture filesystem path
	$file_full_path = $file_path.$file_name;
	
	// picture url to write in the DB
	$data = $cuef_upload_webpath.$user_login."/".$file_name;
	
	// filesize in Byte transformed in KiloByte
	$file_size = $_FILES[$input_name]['size'] / 1024;
	$file_type = $_FILES[$input_name]['type'];
	$file_tmp_name = $_FILES[$input_name]['tmp_name'];
	$file_error = $_FILES[$input_name]['error'];

	// CHECK IF IT IS A REAL PICTURE
	if (stristr($file_type, "image/") === false)
		$file_error = 1;
	
	// MIN LENGTH
	if (isset($rules['min_length']))
		if ($file_size < (intval($rules['min_length'])))
			$file_error = 1;
	
	// EXACT LENGTH
	if (isset($rules['exact_length']))
		if ($file_size != (intval($rules['exact_length'])))
			$file_error = 1;

	// MAX LENGTH
	if (isset($rules['max_length']))
		if ($file_size > (intval($rules['max_length'])))
			$file_error = 1;
	
	// if there are no errors and filename is empty
	if (($file_error == 0) && ($file_name != "")) {
		if (move_uploaded_file($file_tmp_name, $file_full_path)) {
			// change file permissions for broken servers
			@chmod($file_full_path, 0644);
			
			// if there is an old file to delete
			if ($old_file) {
				// delete old file if the name is different, if equal NOPE because new file is already uploaded
				if ($file_name != $old_file)
					if (is_file($file_path.$old_file))
						unlink($file_path.$old_file);
				
				$old_thumb_file = cimy_get_thumb_path($old_file);
				
				if (is_file($file_path.$old_thumb_file))
					unlink($file_path.$old_thumb_file);
			}
			
			// should be stay AFTER DELETIONS
			if (isset($rules['equal_to'])) {
				if ($maxside = intval($rules['equal_to'])) {
					if (!function_exists(image_resize))
						require_once(ABSPATH . 'wp-includes/media.php');
					
					image_resize($file_full_path, $maxside, $maxside, false, "thumbnail");
				}
			}
		}
	}
	else
		$data = "";
	
	return $data;
}

?>