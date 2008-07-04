<?php
require_once('admin.php');

$title = __('WordPress MU &rsaquo; Admin &rsaquo; Users');
$parent_file = 'wpmu-admin.php';

wp_enqueue_script( 'admin-forms' );

require_once('admin-header.php');

if( is_site_admin() == false ) {
	wp_die( __('You do not have permission to access this page.') );
}

if ( $_GET['updated'] == 'true' ) {
	?>
	<div id="message" class="updated fade"><p>
		<?php
		switch ($_GET['action']) {
			case 'delete':
				_e('User deleted !');
			break;
			case 'all_spam':
				_e('Users mark as spam !');
			break;
			case 'all_delete':
				_e('Users deleted !');
			break;
			case 'add':
				_e('User added !');
			break;
		}
		?>
	</p></div>
	<?php
}
?>

<div class="wrap" style="position:relative;">
	<?php
	$apage = isset( $_GET['apage'] ) ? intval( $_GET['apage'] ) : 1;
	$num = isset( $_GET['num'] ) ? intval( $_GET['num'] ) : 15;

	$query = "SELECT * FROM {$wpdb->users}";
	
	if( !empty($_GET['s']) ) {
		$search = '%' . trim(addslashes($_GET['s'])) . '%';
		$query .= " WHERE user_login LIKE '$search' OR user_email LIKE '$search'";
	}
	
	if( !isset($_GET['sortby']) ) {
		$_GET['sortby'] = 'id';
	}
	
	if( $_GET['sortby'] == 'email' ) {
		$query .= ' ORDER BY user_email ';
	} elseif( $_GET['sortby'] == 'id' ) {
		$query .= ' ORDER BY ID ';
	} elseif( $_GET['sortby'] == 'login' ) {
		$query .= ' ORDER BY user_login ';
	} elseif( $_GET['sortby'] == 'name' ) {
		$query .= ' ORDER BY display_name ';
	} elseif( $_GET['sortby'] == 'registered' ) {
		$query .= ' ORDER BY user_registered ';
	}
	
	$query .= ( $_GET['order'] == 'DESC' ) ? 'DESC' : 'ASC';

	if( !empty($_GET['s'])) {
		$user_list = $wpdb->get_results( $query, ARRAY_A );
		$total = count($user_list);	
	} else {
		$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}");
	}
	
	$query .= " LIMIT " . intval( ( $apage - 1 ) * $num) . ", " . intval( $num );
	
	$user_list = $wpdb->get_results( $query, ARRAY_A );

	// Pagination
	$user_navigation = paginate_links( array(
		'base' => add_query_arg( 'apage', '%#%' ),
		'format' => '',
		'total' => ceil($total / $num),
		'current' => $apage
	));
	?>
	<h2><?php _e("Users"); ?></h2>
	<form action="wpmu-users.php" method="get" style="position:absolute;right:0;top:0;"> 
		<input type="text" name="s" value="<?php if (isset($_GET['s'])) echo stripslashes(wp_specialchars($_GET['s'], 1)); ?>" size="17" />
		<input type="submit" id="post-query-submit" value="<?php _e('Search Users') ?>" class="button" />
	</form>

	<form id="form-user-list" action='wpmu-edit.php?action=allusers' method='post'>
		<div class="tablenav">
			<?php if ( $user_navigation ) echo "<div class='tablenav-pages'>$user_navigation</div>"; ?>	

			<div class="alignleft">
				<input type="submit" value="<?php _e('Delete') ?>" name="alluser_delete" class="button-secondary delete" />
				<input type="submit" value="<?php _e('Mark as Spammers') ?>" name="alluser_spam" class="button-secondary" />
				<input type="submit" value="<?php _e('Not Spam') ?>" name="alluser_notspam" class="button-secondary" />
				<?php wp_nonce_field( 'allusers' ); ?>
				<br class="clear" />
			</div>
		</div>

		<br class="clear" />
		
		<?php if( isset($_GET['s']) && $_GET['s'] != '' ) : ?>
			<p><a href="wpmu-blogs.php?action=blogs&amp;s=<?php echo stripslashes(wp_specialchars($_GET['s'], 1)); ?>"><?php _e('Search Blogs:') ?> <strong><?php echo stripslashes(wp_specialchars($_GET['s'], 1)) ?></strong></a></p>
		<?php endif; ?>

		<?php
		// define the columns to display, the syntax is 'internal name' => 'display name'
		$posts_columns = array(
			'checkbox'	 => '',
			'id'         => __('ID'),
			'login'      => __('Login'),
			'email'      => __('Email'),
			'name'       => __('Name'),
			'registered' => __('Registered'),
			'blogs'      => ''
		);
		$posts_columns = apply_filters('manage_posts_columns', $posts_columns);
		?>
		<table class="widefat" cellpadding="3" cellspacing="3">
			<thead>
			<tr>
				<?php foreach( (array) $posts_columns as $column_id => $column_display_name) {
					if( $column_id == 'blogs' ) {
						echo '<th scope="col">'.__('Blogs').'</th>';
					} elseif( $column_id == 'checkbox') {
						echo '<th scope="col" class="check-column"><input type="checkbox" onclick="checkAll(document.getElementById(\'form-user-list\'));" /></th>';
					} else { ?>
						<th scope="col"><a href="wpmu-users.php?sortby=<?php echo $column_id ?>&amp;<?php if( $_GET['sortby'] == $column_id ) { if( $_GET['order'] == 'DESC' ) { echo "order=ASC&amp;" ; } else { echo "order=DESC&amp;"; } } ?>apage=<?php echo $apage ?>"><?php echo $column_display_name; ?></a></th>
					<?php } ?>
				<?php } ?>
			</tr>
			</thead>
			<tbody id="users" class="list:user user-list">
			<?php if ($user_list) {
				$bgcolor = '';
				foreach ( (array) $user_list as $user) { 
					$class = ('alternate' == $class) ? '' : 'alternate';
					?>
					
					<tr class="<?php echo $class; ?>">
					<?php
					foreach( (array) $posts_columns as $column_name=>$column_display_name) :
						switch($column_name) {
							case 'checkbox': ?>
								<th scope="row" class="check-column"><input type='checkbox' id='user_<?php echo $user['ID'] ?>' name='allusers[]' value='<?php echo $user['ID'] ?>' /></th>
							<?php 
							break;
							
							case 'id': ?>								
								<td><?php echo $user['ID'] ?></td>
							<?php
							break;

							case 'login':
								$edit = clean_url( add_query_arg( 'wp_http_referer', urlencode( clean_url( stripslashes( $_SERVER['REQUEST_URI'] ) ) ), "user-edit.php?user_id=".$user['ID'] ) );
								?>
								<td><strong><a href="<?php echo $edit; ?>" class="edit"><?php echo stripslashes($user['user_login']); ?></a></strong></td>
							<?php
							break;

							case 'name': ?>
								<td><?php echo $user['display_name'] ?></td>
							<?php
							break;

							case 'email': ?>
								<td><?php echo $user['user_email'] ?></td>
							<?php
							break;

							case 'registered': ?>
								<td><?php echo mysql2date(__('Y-m-d \<\b\r \/\> g:i:s a'), $user['user_registered']); ?></td>
							<?php
							break;

							case 'blogs': 
								$blogs = get_blogs_of_user( $user['ID'], true );
								?>
								<td>
									<?php
									if( is_array( $blogs ) ) {
										foreach ( (array) $blogs as $key => $val ) {
											echo '<a href="wpmu-blogs.php?action=editblog&amp;id=' . $val->userblog_id . '">' . str_replace( '.' . $current_site->domain, '', $val->domain . $val->path ) . '</a> (<a '; 
											if( get_blog_status( $val->userblog_id, 'spam' ) == 1 )
												echo 'style="background-color: #f66" ';
											echo 'target="_new" href="http://'.$val->domain . $val->path.'">' . __('View') . '</a>)<br />'; 
										}
									}
									?>
								</td>
							<?php
							break;

							default: ?>
								<td><?php do_action('manage_users_custom_column', $column_name, $user['ID']); ?></td>
							<?php
							break;
						}
					endforeach
					?>
					</tr> 
					<?php
				}
			} else {
			?>
				<tr style='background-color: <?php echo $bgcolor; ?>'> 
					<td colspan="<?php echo (int) count($posts_columns); ?>"><?php _e('No users found.') ?></td> 
				</tr> 
				<?php
			} // end if ($users)
			?> 
			</tbody>
		</table>
	</form>
</div>

<?php
if( apply_filters('show_adduser_fields', true) ) :
?>
<div class="wrap">
	<h2><?php _e('Add user') ?></h2>
	<form action="wpmu-edit.php?action=adduser" method="post">
	<table class="form-table">
		<tr class="form-field form-required">	
			<th scope='row'><?php _e('Username') ?></th>
			<td><input type="text" name="user[username]" /></td>
		</tr>
		<tr class="form-field form-required">	
			<th scope='row'><?php _e('Email') ?></th>
			<td><input type="text" name="user[email]" /></td>
		</tr>
		<tr class="form-field">
			<td colspan='2'><?php _e('Username and password will be mailed to the above email address.') ?></td>
		</tr>
	</table>
	<p class="submit">
		<?php wp_nonce_field('add-user') ?>
		<input class="button" type="submit" name="Add user" value="<?php _e('Add user') ?>" /></p>
	</form>
</div>
<?php endif; ?>

<?php include('admin-footer.php'); ?>
