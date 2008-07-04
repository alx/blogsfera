<?php
function widget_wordspew($args) {
    extract($args);
	$jal_wp_url = get_bloginfo('wpurl').'/wp-content/plugins/wordspew';

	$options = get_option('widget_wordspew');
	$title = $options['title'];

	echo $before_widget . $before_title . $title . ' <a href="'.$jal_wp_url.'/wordspew-rss.php"><img 
	src="'.$jal_wp_url.'/rss.gif" border="0" alt="" 
	title="'.__('Wordspew-RSS-Feed for:', wordspew).' ' . get_bloginfo('name').'"/></a>'.$after_title;
	jal_get_shoutbox();
	echo $after_widget;
}

function widget_wordspew_control() {
	$options = get_option('widget_wordspew');
	if ( !is_array($options) )
		$options = array('title'=>'ShoutBox');
	if ( $_POST['wordspew-submit'] ) {
		$options['title'] = strip_tags(stripslashes($_POST['wordspew-title']));
		update_option('widget_wordspew', $options);
	}

	$title = htmlspecialchars($options['title'], ENT_QUOTES);

	echo '<p><label for="wordspew-title">';
	_e('Title:',wordspew);
	echo ' <input style="width: 200px;" id="wordspew-title" name="wordspew-title" type="text" value="'.$title.'" /></label></p>
		  <input type="hidden" id="wordspew-submit" name="wordspew-submit" value="1" />';
}

function jal_on_plugins_loaded() {
	if (function_exists('register_sidebar_widget')) {
		register_sidebar_widget("Shoutbox",'widget_wordspew');
	}
	if (function_exists('register_widget_control')) {
		register_widget_control("Shoutbox", "widget_wordspew_control", 250, 80);
	}
}
?>