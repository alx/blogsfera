<?php
/*
Plugin Name: widget suscribe
Plugin URI: http://bbvablogs.com
Description: add a buton for suscribe users in a blog
Version: 1.0
Author: David Arias
*/
function widget_suscribe_init() {

    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
        return;

	function widget_subscribe_form() {
		return "<form method='post' action='".get_option('home')."'>" .
				"<input type='hidden' name='suscribe' value='true' />" .
				"<input type='submit' value='Suscríbete'' />" .
				"</form>";
	}
	
	function widget_unsubscribe_form() {
		return "<form method='post' action='".get_option('home')."'>" .
				"<input type='hidden' name='unsuscribe' value='true' />" .
				"<input type='submit' value='Desuscríbete'' />" .
				"</form>";
	}

    function widget_suscribe() {
		global $wpdb, $current_user;
		
        $options = get_option('widget_suscribe');

		$before_widget 	= "<li id='subscribe'>";
		$before_title 	= "<h2>";
		$title 			= empty($options['title']) ? 'Suscr&iacute;bete a este blog' : $options['title'];
		$after_title 	= "</h2>";
		$content 		= "";
		$after_widget 	= "</li>";
		
		//error_log("[widget subscribe] current blog: ". $wpdb->blogid);
		//error_log("[widget subscribe] current user: ". $current_user->ID);
		
		// User is already in blog database 
		if (is_blog_user($wpdb->blogid)) {
			
			//error_log("[widget subscribe] is blog user");
			
			// User wants to unsubscribe
			if (isset($_POST['unsuscribe'])) {
				
				//error_log("[widget subscribe] POST[unsuscribe]");
				
				remove_user_from_blog($current_user->ID, $wpdb->blogid);
				$content .= '<p>Te has desuscrito de este blog</p>';
				$content .= widget_subscribe_form();
			}
			
			// Only subscribers can unsubscribe
			elseif ($current_user->has_cap('subscriber')) {
				
				//error_log("[widget subscribe] has_cap('subscriber')");
				
				$content .= widget_unsubscribe_form();
			}
			
			// Show widget for others
			elseif ($current_user->has_cap('edit_posts')) {
				$content .= "<p>Este blog tiene activado el widget de suscripci&oacute;n</p>";
			}
		}
		// If user is not from this blog
		else {
			
			//error_log("[widget subscribe] not in log");
			
			// User wants to subscribe
			if (isset($_POST['suscribe'])) {
				
				//error_log("[widget subscribe] POST[suscribe]");
				
				add_user_to_blog($wpdb->blogid, $current_user->ID, 'subscriber');
				$content .= '<p>Te has suscrito a este blog</p>';
				$content .= widget_unsubscribe_form();
			}
			// display user subscribe form
			else {
				
				//error_log("[widget subscribe] subscribe form");
				
				$content .= widget_subscribe_form();
			}
		}
		
		echo $before_widget . $before_title . $title . $after_title . $content . $after_widget;
	
    }
    register_sidebar_widget('Suscripcion', 'widget_suscribe');

}
add_action('plugins_loaded', 'widget_suscribe_init');
?>
