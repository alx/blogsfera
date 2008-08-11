<?php
/*
Plugin Name: MCEComments
Plugin URI: http://mk.netgenes.org/my-plugins/mcecomments/
Description: A simple hack to enable WYSIWYG editor TinyMCE on the comment field.
Version: 0.4.5
Author: Thomas Au(MK)
Author URI: http://mk.netgenes.org
*/

define('SETTINGS', dirname(__FILE__) . '/settings.js');

if (isset($_GET['init']) || isset($_GET['regen'])) {
	$mcecomment_expiresOffset = 3600 * 24 * 10; // Cache for 10 days in browser cache
	
	include_once(realpath(dirname(__FILE__) . '/../../../wp-config.php'));
	header("Content-type: text/javascript");
	header("Vary: Accept-Encoding");  // Handle proxies
	header("Expires: " . gmdate("D, d M Y H:i:s", time() + $mcecomment_expiresOffset) . " GMT");
	if (!isset($_GET['regen']))
		$content = mcecomment_getFileContents(SETTINGS);
	if ($content == '' || isset($_GET['regen'])) {
		$content = mcecomment_getInitJS();
		mcecomment_putFileContents(SETTINGS, $content);
	}
	echo $content;
	die();
}

if ( !function_exists('wp_nonce_field') ) {
	function mcecomment_nonce_field($action = -1) { return; }
	$mcecomment_nonce = -1;
} else {
	function mcecomment_nonce_field($action = -1) { return wp_nonce_field($action); }
	$mcecomment_nonce = 'mcecomment-update-key';
}

function mcecomment_adminpages() {
	add_options_page('MCEComments Options', 'MCEComments', 8, 'tinyMCEComments', 'mcecomment_optionpage');
}

//Get available plugins of TinyMCE
function mcecomment_getplugins() {
	if ($h = opendir(ABSPATH . 'wp-includes/js/tinymce/plugins')) {
		while (($file = readdir($h)) !== false) {
			if (is_file(ABSPATH . 'wp-includes/js/tinymce/plugins/' . $file . '/editor_plugin.js')) 
				$plugins[] = $file;
			}
		closedir($h);
	}

	return $plugins;
}

//Get Available Languages in the directory (for pre-2.5 only)
function mcecomment_getlangs() {
	if ($h = opendir(ABSPATH . 'wp-includes/js/tinymce/langs/')) {
		while (($file = readdir($h)) !== false) {
			if (is_file(ABSPATH . 'wp-includes/js/tinymce/langs/' . $file) && strpos($file, '.js') !== false) 
				$langs[] = basename($file, '.js');
			}
		closedir($h);
	}	
	return $langs;
}

function mcecomment_optionpage() {
	global $mcecomment_nonce;
	$mcecomment_options = get_option('mcecomment_options');

	if ( isset($_POST['submit']) ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));

		check_admin_referer($mcecomment_nonce);

		$mcecomment_options['language'] = (isset($_POST['mcecomment_language']) ? $_POST['mcecomment_language'] : $mcecomment_options['language']);
			
		$mcecomment_options['rtl'] = (isset($_POST['mcecomment_rtl']) ? '1' : '0');
		$mcecomment_options['viewhtml'] = (isset($_POST['mcecomment_viewhtml']) ? '1' : '0');
		$mcecomment_options['resize'] = (isset($_POST['mcecomment_resize']) ? '1' : '0');

		if (isset($_POST['mcecomment_buttons'])) {
			$buttons = trim($_POST['mcecomment_buttons']);
			if ($buttons[strlen($buttons)-1] == ',') {
				$buttons = substr($buttons, 0, -1);
			}
			$mcecomment_options['buttons'] = $buttons;
		}

		if (isset($_POST['mcecomment_plugins'])) {
			$plugins = trim($_POST['mcecomment_plugins']);
			if ($plugins[strlen($plugins)-1] == ',') {
				$plugins = substr($plugins, 0, -1);
			}
			$mcecomment_options['plugins'] = $plugins;
		}

		if (isset($_POST['mcecomment_css']))
			$mcecomment_options['css'] = trim($_POST['mcecomment_css']);
	
		update_option('mcecomment_options', $mcecomment_options);
		//Save the settings into cache file
		mcecomment_putFileContents(SETTINGS, mcecomment_getInitJS());
	}
	 
	mcecomment_init();
?>

<?php if ( !empty($_POST ) ) : 
?>
<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
<?php endif; ?>

<div class="wrap">
<h2>MCEComments Options</h2>

<form method="post">
<?php mcecomment_nonce_field($mcecomment_nonce);?>
<h3>Interface</h3> 
<script type="text/javascript">
//<![CDATA[
function inserttext(obj_out,obj_in) {
	obj = document.getElementById(obj_out);
	obj.value += ((obj.value != '') ? ',' : '') + (obj_in).innerHTML;
}
//]]>
</script>
<table width="100%" cellspacing="2" cellpadding="5" class="form-table">
<?php if(!mcecomment_isNewWP()) : ?> 
<tr class="form-field"> 
<th scope="row">Language:</th> 
<td><select name="mcecomment_language" id="mcecomment_language">
<?php
$langs = mcecomment_getlangs();
for ($i=0;$i<count($langs);$i++) echo "<option".($langs[$i] == $mcecomment_options['language'] ? ' selected="selected"' : '') . ">$langs[$i]</option>\n";
?>
</select>
</td></tr>
<?php endif; ?>
<tr class="form-field"> 
<th>Options:(TinyM:<?php echo $mcecomment_options['rtl']; ?>)</th>
<td>
<p><input name="mcecomment_rtl" type="checkbox" id="mcecomment_rtl" value="1" <?php echo ($mcecomment_options['rtl'] ? 'checked="checked"':''); ?>/>
<label for="mcecomment_rtl">Enable right-to-left (RTL) editing mode in comment field</label></p>
<p><input name="mcecomment_viewhtml" type="checkbox" id="mcecomment_viewhtml" value="1" <?php echo ($mcecomment_options['viewhtml'] ? 'checked="checked"':''); ?>  />
<label for="mcecomment_viewhtml">Enable HTML source editing of the comment field</label></p>
<p><input name="mcecomment_resize" type="checkbox" id="mcecomment_resize" value="1" <?php echo ($mcecomment_options['resize'] ? 'checked="checked"':''); ?>  />
<label for="mcecomment_resize">Enable vertical resizing of the comment field writing area</label></p>
</td></tr></table>

<h3>Advance Options</h3> 
<table width="100%" cellspacing="2" cellpadding="5" class="form-table"> 
<tr class="form-field">
<th>Buttons in use:</th>
<td><input name="mcecomment_buttons" type="text" id="mcecomment_buttons" value="<?php echo $mcecomment_options['buttons']; ?>" style="width:98%" /><br />
(separated with commas)<br /> 
Available buttons: 
<?php $pls = array('separator','bold','italic','underline','strikethrough','justifyleft','justifycenter','justifyright','justifyfull','bullist','numlist','outdent','indent','cut','copy','paste','undo','redo','link','unlink','cleanup','help','code','hr','removeformat','sub','sup','forecolor','backcolor','charmap','visualaid','blockquote','spellchecker','fullscreen');
for ($i=0; $i<count($pls); $i++) {
echo '<span style="cursor: pointer; text-decoration: underline;" onclick="inserttext(\'mcecomment_buttons\', this);">'.$pls[$i].'</span>  '; 
}?>
</td>
</tr><tr class="form-field"> 
<th>Plugins in use:</th>
<td><input name="mcecomment_plugins" type="text" id="mcecomment_plugins" value="<?php echo $mcecomment_options['plugins']; ?>" style="width:98%" /><br />
(separated with commas)<br /> 
Detected plugins: 
<?php $pls = mcecomment_getplugins(); 
for ($i=0; $i<count($pls); $i++) {
echo '<span style="cursor: pointer; text-decoration: underline;" onclick="inserttext(\'mcecomment_plugins\', this);">'.$pls[$i].'</span>  '; 
}?>
</td>
</tr><tr class="form-field">
<th>User defined CSS:</th>
<td><input name="mcecomment_css" type="text" id="mcecomment_css" value="<?php echo $mcecomment_options['css']; ?>" style="width:98%" /><br />
Fully qualified URL required (leave blank to use default CSS)
</td></tr>
</table>

<h3>Preview</h3> 
<table width="100%" cellspacing="2" cellpadding="5" class="form-table"> 
<tr valign="top" class="form-field"> 
<th>Preview:</th>
<td>Update options to preview how the comment textarea box will appear.<br /><textarea name="comment" id="comment" rows="5" tabindex="4" style="width:99%">This is a preview textarea</textarea>
</td></tr>
</table>

<p class="submit">
<input type="hidden" name="action" value="update" />
<input type="submit" name="submit" value="Update Options &raquo;" />
</p>
</form>
</div>
<?php
}

function mcecomment_getcss() {
	$mcecomment_options = get_option('mcecomment_options');

	if ($mcecomment_options['css'] != '') {
		return $mcecomment_options['css'];
	} elseif (mcecomment_isNewWP()) {
		return get_option('siteurl') . '/wp-includes/js/tinymce/wordpress.css';
	} else {
		return get_option('siteurl') . '/wp-includes/js/tinymce/plugins/wordpress/wordpress.css';
	}
}

function mcecomment_getInitJS() {
	
	$mcecomment_options = get_option('mcecomment_options');
	if (!is_array($mcecomment_options))
	{
		$mcecomment_options['language'] = 'en';
		$mcecomment_options['buttons'] = 'bold,italic,underline,|,strikethrough,|,bullist,numlist,|,undo,redo,|,link,unlink,|,removeformat';
		update_option('mcecomment_options', $mcecomment_options);
	}
	
	$res = 'function brstonewline(element_id, html, body) {';
	$res .= 'html = html.replace(/<br\s*\/>/gi, "\n");';
	$res .= 'return html;}';
			

	$res .= 'function insertHTML(html) {';
	$res .= 'tinyMCE.execCommand("mceInsertContent",false, html);}';

	$res .= 'tinyMCE.init({';
	$res .= 'mode : "exact",';
	$res .= 'elements : "comment",';
	$res .= 'theme : "advanced",';
	$res .= 'theme_advanced_buttons1 : "' . $mcecomment_options['buttons'] . '",';
	$res .= 'theme_advanced_buttons2 : "",';
	$res .= 'theme_advanced_buttons3 : "",';
	$res .= 'theme_advanced_toolbar_location : "top",';
	$res .= 'theme_advanced_toolbar_align : "left",';
	$res .= 'theme_advanced_statusbar_location : "' . ($mcecomment_options['resize'] ? 'bottom' : 'none') . '",';
	$res .= 'theme_advanced_resizing : ' . ($mcecomment_options['resize'] ? 'true' : 'false') . ',';
	$res .= 'theme_advanced_resize_horizontal : false,';
	$res .= 'theme_advanced_disable : "' . ($mcecomment_options['viewhtml'] ? '':'code') . '",';
	$res .= 'force_p_newlines : false,';
	$res .= 'force_br_newlines : true,';
	$res .= 'forced_root_block : "",';
	$res .= 'gecko_spellcheck : true,';
	$res .= 'content_css : "' . mcecomment_getcss() . '",';
	$res .= 'directionality : "' . ($mcecomment_options['rtl'] ? 'rtl' : 'ltr') . '",';
	$res .= 'save_callback : "brstonewline",';
	$res .= 'language : "' . $mcecomment_options['language'] . '",';
	$res .= 'entity_encoding : "raw",';
	$res .= 'plugins : "' . $mcecomment_options['plugins'] . '",';
	$res .= 'extended_valid_elements : "a[name|href|title],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style],blockquote[cite]"});';
	if (mcecomment_isNewWP()) {
		$language = ('' == get_locale()) ? $mcecomment_options['language'] : strtolower( substr(get_locale(), 0, 2) );
		include_once(ABSPATH . 'wp-includes/js/tinymce/langs/wp-langs.php');
		$res .= $strings;
	}
	$res .= 'var subBtn = document.getElementById("submit");';
	$res .= 'if (subBtn != null) {';
	$res .= 'subBtn.onclick=function() {';
	$res .= 'var inst = tinyMCE.getInstanceById("comment");';
	$res .= 'document.getElementById("comment").value = inst.getContent();';
	$res .= 'document.getElementById("commentform").submit();';
	$res .= 'return false;}}';

	return $res;
}

function mcecomment_init() {
	global $post;
	
	$mcecomment_options = get_option('mcecomment_options');
	
	$mcecomment_mce_path = get_option('siteurl');
	if (mcecomment_isNewWP()) {
		//TODO: Gzip & cache support
		$mcecomment_mce_path .= '/wp-includes/js/tinymce/tiny_mce.js';
	} else {
		$mcecomment_mce_path .= '/wp-includes/js/tinymce/tiny_mce_gzip.php';
	}
	$mcecomment_mce_init = get_option('siteurl') . '/wp-content/plugins/' . basename(dirname(__FILE__)) . '/tinyMCEComments.php?init';
	if (('open' == $post-> comment_status) or ('comment' == $post-> comment_type) or (is_plugin_page()) ) {
		echo '<script type="text/javascript" src="' . $mcecomment_mce_path . '"></script>';
		echo '<script type="text/javascript" src="' . $mcecomment_mce_init . '"></script>';
	}
}

//Handy function from TinyMCE Editor PHP GZip Compressor
function mcecomment_getFileContents($path) {
		$path = realpath($path);
		
		if (!$path || !@is_file($path))
			return "";

		if (function_exists("file_get_contents"))
			return @file_get_contents($path);

		$content = "";
		$fp = @fopen($path, "r");
		if (!$fp)
			return "";

		while (!feof($fp))
			$content .= fgets($fp);

		fclose($fp);

		return $content;
	}

function mcecomment_putFileContents($path, $content) {
	if (function_exists("file_put_contents"))
		return @file_put_contents($path, $content);

	$fp = @fopen($path, "wb");
	if ($fp) {
		fwrite($fp, $content);
		fclose($fp);
	}
}

function mcecomment_isNewWP() {
	global $wp_version;

	list($major, $minor, $rev) = explode('.', $wp_version);
	if ($major >= 2 && $minor >= 5) return true;
	else return false;
}

add_action('comment_form', 'mcecomment_init');
add_action('admin_menu', 'mcecomment_adminpages');

add_option('mcecomment_options', '', 'TinyMCE Comments');
?>
