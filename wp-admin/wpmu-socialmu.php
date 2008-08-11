<?php
require_once('admin.php');

$title = __('Comunidad Admin');
$parent_file = 'wpmu-admin.php';

require_once('admin-header.php');

if( is_site_admin() == false ) {
	wp_die( __('<p>You do not have permission to access this page.</p>') );
}

if (isset($_POST['updated'])) {
	?>
	<div id="message" class="updated fade"><p><?php _e('Options updated.') ?></p></div>
	<?php
}
?>

<?php 
	global $wpdb;
if (($_GET['action']=='addcomunidad')){
	check_admin_referer('add-blog');

		$blog = $_POST['blog'];
		$domain = sanitize_user( str_replace( '/', '', $blog[ 'domain' ] ) );
		$email = sanitize_email( $blog[ 'email' ] );
		$title = $blog[ 'title' ];

		if ( empty($domain) || empty($email))
			wp_die( __('Missing blog address or email address.') );
		if( !is_email( $email ) ) 
			wp_die( __('Invalid email address') ); 

		if( constant('VHOST') == 'yes' ) {
			$newdomain = $domain.".".$current_site->domain;
			$path = $base;
		} else {
			$newdomain = $current_site->domain;
			$path = $base.$domain.'/';
		}

		$user_id = email_exists($email);
		if( !$user_id ) {
			$password = generate_random_password();
			$user_id = wpmu_create_user( $domain, $password, $email );
			if(false == $user_id) {
				wp_die( __('There was an error creating the user') );
			} else {
				wp_new_user_notification($user_id, $password);
			}
		}

		$wpdb->hide_errors();
		$id = wpmu_create_blog($newdomain, $path, $title, $user_id , array( "public" => 1 ), $current_site->id);
		$wpdb->show_errors();
		if( !is_wp_error($id) ) {
			if( get_user_option( $user_id, 'primary_blog' ) == 1 )
				update_user_option( $user_id, 'primary_blog', $id, true );
			//$content_mail = sprintf( __( "New blog created by %1s\n\nAddress: http://%2s\nName: %3s"), $current_user->user_login , $newdomain.$path, stripslashes( $title ) );
			//wp_mail( get_site_option('admin_email'),  sprintf(__('[%s] New Blog Created'), $current_site->site_name), $content_mail, 'From: "Site Admin" <' . get_site_option( 'admin_email' ) . '>' );
			//wp_redirect( add_query_arg( array('updated' => 'true', 'action' => 'add-blog'), $_SERVER['HTTP_REFERER'] ) );
			//exit();
		} else {
			wp_die( $id->get_error_message() );
		}
		update_blog_option( $id, 'template', 'people');
		update_blog_option( $id, 'stylesheet', 'people');
		
		add_site_option("comunidad_url", "http://".$newdomain.$path);
}



?>


<div class="wrap">

<h2><?php _e('Comunidad Automatic install '); ?></h2>

<?php if (strlen(get_site_option("comunidad_url")) == 0){ ?>
			<form method="post" action="wpmu-socialmu.php?action=addcomunidad">
				<?php wp_nonce_field('add-blog') ?>
				<table class="form-table">
					<tr class="form-field form-required">
						<th style="text-align:center;" scope='row'><?php _e('Comunidad Address') ?></th>
						<td>
						<?php if( constant( "VHOST" ) == 'yes' ) : ?>
							<input name="blog[domain]" type="text" title="<?php _e('Domain') ?>"/>.<?php echo $current_site->domain;?>
						<?php else:
							echo $current_site->domain . $current_site->path ?><input name="blog[domain]" type="text" title="<?php _e('Domain') ?>"/>
						<?php endif; ?>
						</td>
					</tr>
					<tr class="form-field form-required">
						<th style="text-align:center;" scope='row'><?php _e('Comunidad Title') ?></th>
						<td><input name="blog[title]" type="text" size="20" title="<?php _e('Title') ?>"/></td>
					</tr>
					<tr class="form-field form-required">
						<th style="text-align:center;" scope='row'><?php _e('Admin Email') ?></th>
						<td><input name="blog[email]" type="text" size="20" title="<?php _e('Email') ?>"/></td>
					</tr>
					<tr class="form-field">
						<td colspan='2'><?php _e('A new user will be created if the above email address is not in the database.') ?><br /><?php _e('The username and password will be mailed to this email address.') ?></td>
					</tr>
				</table>
				<p class="submit">
					<input type="hidden" name="updated" value="updated">
					<input class="button" type="submit" name="go" value="<?php _e('Add Comunidad') ?>" /></p>
			</form>
<?php 
}else{
	echo '<p>comunidad is instaled on <a href="' . get_site_option("comunidad_url").'">'.get_site_option("comunidad_url").'</a></p>';
}




?>

</div>



<?php include('admin-footer.php'); ?>
