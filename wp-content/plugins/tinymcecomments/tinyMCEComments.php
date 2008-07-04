<?php
/*
Plugin Name: TinyMCEComments
Plugin URI: http://mk.netgenes.org/my-plugins/mcecomments/
Description: A simple hack to enable WYSIWYG editor TinyMCE on the comment field.
Version: 0.4.1
Author: Thomas Au(MK)
Author URI: http://mk.netgenes.org
*/

if ( !function_exists('wp_nonce_field') ) {
	function mceComment_nonce_field($action = -1) { return; }
	$mceComment_nonce = -1;
} else {
	function mceComment_nonce_field($action = -1) { return wp_nonce_field($action); }
	$mceComment_nonce = 'mceComment-update-key';
}

function mce_addAdminPages() {
	add_options_page('TinyMCEComments Options', 'TinyMCEComments', 8, 'tinyMCEComments', 'mceComment_optionPage');
}

//Get available plugins of TinyMCE
function getMCEPlugins() {
    if ($h = opendir(ABSPATH . 'wp-includes/js/tinymce/plugins')) {
        while (($file = readdir($h)) !== false) {
            if (is_file(ABSPATH . 'wp-includes/js/tinymce/plugins/' . $file . '/editor_plugin.js')) 
                $plugins[] = $file;
        }
        closedir($h);
    }

    return $plugins;
}

//Get Available Languages in the directory
function getMCELangs() {
    if ($h = opendir(ABSPATH . 'wp-includes/js/tinymce/langs/')) {
        while (($file = readdir($h)) !== false) {
            if (is_file(ABSPATH . 'wp-includes/js/tinymce/langs/' . $file) && strpos($file, '.js') !== false) 
                $langs[] = basename($file, '.js');
        }
        closedir($h);
    }

    return $langs;
}

function mceComment_optionPage() {
	global $mceComment_nonce;

	if ( isset($_POST['submit']) ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));

		check_admin_referer($mceComment_nonce);

		if (isset($_POST['use_rtl'])) {
			update_option('mcecomment_rtl', '1');
		} else {
			update_option('mcecomment_rtl', '0');
		}

		if (isset($_POST['use_htmlsrc'])) {
			update_option('mcecomment_viewhtml', '1');
		} else {
			update_option('mcecomment_viewhtml', '0');
		}
		
        	if (isset($_POST['language'])) {
			update_option('mcecomment_lang', $_POST['language']);
		} else {
			update_option('mcecomment_lang', $_POST['language']);
		}
        
        	if (isset($_POST['mcecomment_scripts'])) {
			update_option('mcecomment_scripts', '1');
		} else {
			update_option('mcecomment_scripts', '0');
		}

		if (isset($_POST['mcecomment_add_plugins'])) {
			$plugins = trim($_POST['mcecomment_add_plugins']);
			if ($plugins[strlen($plugins)-1] == ',') {
				$plugins = substr($plugins, 0, -1);
			}
			update_option('mcecomment_add_plugins', $plugins);
		}

		if (isset($_POST['mcecomment_add_buttons'])) {
			$buttons = trim($_POST['mcecomment_add_buttons']);
			if ($buttons[strlen($buttons)-1] == ',') {
				$buttons = substr($buttons, 0, -1);
			}
			update_option('mcecomment_add_buttons', $buttons);
		}
            
	        if (isset($_POST['mcecomment_css']))
	            	update_option('mcecomment_css', trim($_POST['mcecomment_css']));
	}
?>

<?php if ( !empty($_POST ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
<?php endif; ?>
	<div class="wrap">
<h2>TinyMCEComments Options</h2>

<form method="post">
<?php mceComment_nonce_field($mceComment_nonce);?>
<fieldset class="options"> 
<legend>Interface</legend> 
<script type="text/javascript">
//<![CDATA[
function addplug(obj) {
	plugs = $('mcecomment_add_plugins');
	//alert(plugs.getValue().length);
	if (plugs.getValue().length > 0){
		plugs.value += ',';
	}
	plugs.value += $(obj).innerHTML;
}
//]]>
</script>
<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform"> 
<tr valign="top"> 
<th width="33%" scope="row">Language:</th> 
<td><select name="language" id="language">
<?php
$langs = getMCELangs();
for ($i=0;$i<count($langs);$i++) echo "<option".($langs[$i] == get_option('mcecomment_lang')?' selected="selected"':'').">$langs[$i]</option>\n";
?>
</select>
</td></tr>
<tr valign="top"> 
<th>Options:</th>
<td>
<p><input name="use_rtl" type="checkbox" id="use_rtl" value="1" <?php echo (get_option('mcecomment_rtl') ? 'checked="checked"':'') ?>/>
<label for="use_linksupdate">Right-To-Left(RTL) Mode</label></p>

<p><input name="use_htmlsrc" type="checkbox" id="use_htmlsrc" value="1" <?php echo (get_option('mcecomment_viewhtml') ? 'checked="checked"':'') ?>  />
<label for="use_linksupdate">Allow visitors to view HTML source of their comment</label></p>
<p><input name="mcecomment_scripts" type="checkbox" id="mcecomment_scripts" value="1" <?php echo (get_option('mcecomment_scripts') ? 'checked="checked"':'') ?>  />
<label for="use_linksupdate">Display subscript/superscript buttons</label></p>
</td></tr></table>
</fieldset>
<fieldset class="options"> 
<legend>Advance Options</legend> 
<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform"> 
<tr valign="top"> 
<th>Additional plugins:</th>
<td><input name="mcecomment_add_plugins" type="text" id="mcecomment_add_plugins" value="<?php echo get_option('mcecomment_add_plugins')?>" style="width:98%" />(separated with commas)<br /> 
Detected plugins:
<?php $pls = getMCEPlugins(); 
for ($i=0; $i<count($pls); $i++) {
echo '<a href="#" onclick="addplug(this);return false;">'.$pls[$i].'</a> '; 
}?>
</td>
</tr><tr>
<th>Additional buttons:</th>
<td><input name="mcecomment_add_buttons" type="text" id="mcecomment_add_buttons" value="<?php echo get_option('mcecomment_add_buttons')?>" style="width:98%" /><br />
seperated with commas
</td>
</tr><tr>
<th>User defined CSS:</th>
<td><input name="mcecomment_css" type="text" id="mcecomment_css" value="<?php echo get_option('mcecomment_css')?>" style="width:98%" /><br />
Fully qualified URL required(Leave it blank to use default CSS)
</td></tr>
</table>
<p class="submit">
<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="hack_file,use_linksupdate,uploads_use_yearmonth_folders,upload_path" />
<input type="submit" name="submit" value="Update Options &raquo;" />
</p>
</form>
</div>
<?php
}

function addTinyMCESupport() {
    global $post;
    if (('open' == $post-> comment_status) or ('comment' == $post-> comment_type) ) { ?>
<script type="text/javascript" src="<?php echo get_settings('home'); ?>/wp-includes/js/tinymce/tiny_mce_gzip.php?ver=20070326"></script>
<script type="text/javascript">
//<![CDATA[
function brstonewline(element_id, html, body) {
	html = html.replace(/<br\s*\/>/gi, '\n');;
	return html;
}

function insertHTML(html) {
	tinyMCE.execCommand('mceInsertContent',false, html);
}

tinyMCE.init({
	mode : "exact",
	elements : "comment",
	theme : "advanced",
	theme_advanced_buttons1 : "bold,italic,underline,separator,strikethrough,undo,redo,link,unlink<?php echo (get_option('mcecomment_viewhtml') ? ',code':''); echo (get_option('mcecomment_scripts') ? ',sub,sup':''); echo (get_option('mcecomment_add_buttons') == '' ? '':(','.get_option('mcecomment_add_buttons'))); ?>",
	force_p_newlines : false,
    force_br_newlines : true,
	gecko_spellcheck : true,
	content_css : "<?php echo (get_option('mcecomment_css') == '' ? get_settings('home') . '/wp-includes/js/tinymce/plugins/wordpress/wordpress.css' : get_option('mcecomment_css'));?>",
	theme_advanced_buttons2 : "",
	theme_advanced_buttons3 : "",
	theme_advanced_toolbar_location : "top",
	theme_advanced_toolbar_align : "left",
	<?php echo (get_option('mcecomment_rtl') ? 'directionality : "rtl",'."\n":'') ?>
	save_callback : "brstonewline",
	language : "<?php echo get_option('mcecomment_lang') ?>",
	entity_encoding : "raw",
    	plugins : "<?php echo get_option('mcecomment_add_plugins') ?>",
	extended_valid_elements : "a[name|href|title],font[face|size|color|style],span[class|align|style]"
});

//]]>
</script>
<?php
    }
}
function detect_submit() { 
if (('open' == $post-> comment_status) or ('comment' == $post-> comment_type) ) { ?>
<script type="text/javascript">
//<![CDATA[
if(typeof jQuery != 'function') {
$(function(event){
     $("#submit").click( function() {
     var inst = tinyMCE.getInstanceById('comment');
     $("#comment").value = inst.getHTML(); 
     return false;});}
}
//]]>
</script>
<?php }
}
add_action('comment_form', 'addTinyMCESupport');
add_action('wp_footer', 'detect_submit');
add_action('admin_menu', 'mce_addAdminPages');
add_option('mcecomment_rtl', '0', 'Make TinyMCE Comment Field supports RTL languages');
add_option('mcecomment_viewhtml', '1', 'Allow visitors display html code in comment field');
add_option('mcecomment_lang', 'en', 'TinyMCEComments Language');
add_option('mcecomment_scripts', '0', 'Sub/superscripts in Comments');
add_option('mcecomment_add_plugins', '', 'User supplied additional plugins');
add_option('mcecomment_add_buttons', '', 'User supplied additional buttons');
add_option('mcecomment_css', '', 'User supplied content_css');
?>
