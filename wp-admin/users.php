<?php
require_once('admin.php');
require_once( ABSPATH . WPINC . '/registration.php');

if ( !current_user_can('edit_users') )
	wp_die(__('Cheatin&#8217; uh?'));

$title = __('Users');
$parent_file = 'users.php';

$action = $_REQUEST['action'];
$update = '';

if ( empty($action) ) {
	if ( isset($_GET['removeit']) )
		$action = 'removeuser';
	elseif ( isset($_GET['changeit']) && !empty($_GET['new_role']) )
		$action = 'promote';
}

if ( empty($_REQUEST) ) {
	$referer = '<input type="hidden" name="wp_http_referer" value="'. attribute_escape(stripslashes($_SERVER['REQUEST_URI'])) . '" />';
} elseif ( isset($_REQUEST['wp_http_referer']) ) {
	$redirect = remove_query_arg(array('wp_http_referer', 'updated', 'delete_count'), stripslashes($_REQUEST['wp_http_referer']));
	$referer = '<input type="hidden" name="wp_http_referer" value="' . attribute_escape($redirect) . '" />';
} else {
	$redirect = 'users.php';
}

switch ($action) {

case 'promote':
	check_admin_referer('bulk-users');

	if (empty($_REQUEST['users'])) {
		wp_redirect($redirect);
		exit();
	}

	if ( !current_user_can('edit_users') )
		wp_die(__('You can&#8217;t edit users.'));

	$userids = $_REQUEST['users'];
	$update = 'promote';
	foreach($userids as $id) {
		if ( ! current_user_can('edit_user', $id) )
			wp_die(__('You can&#8217;t edit that user.'));
		// The new role of the current user must also have edit_users caps
		if($id == $current_user->ID && !$wp_roles->role_objects[$_REQUEST['new_role']]->has_cap('edit_users')) {
			$update = 'err_admin_role';
			continue;
		}

		$user = new WP_User($id);
		$user->set_role($_REQUEST['new_role']);
	}

	wp_redirect(add_query_arg('update', $update, $redirect));
	exit();

break;

case 'dodelete':
	wp_die(__('This function is disabled.'));
	check_admin_referer('delete-users');

	if ( empty($_REQUEST['users']) ) {
		wp_redirect($redirect);
		exit();
	}

	if ( !current_user_can('delete_users') )
		wp_die(__('You can&#8217;t delete users.'));

	$userids = $_REQUEST['users'];
	$update = 'del';
	$delete_count = 0;

	foreach ( (array) $userids as $id) {
		if ( ! current_user_can('delete_user', $id) )
			wp_die(__('You can&#8217;t delete that user.'));

		if($id == $current_user->ID) {
			$update = 'err_admin_del';
			continue;
		}
		switch($_REQUEST['delete_option']) {
		case 'delete':
			wp_delete_user($id);
			break;
		case 'reassign':
			wp_delete_user($id, $_REQUEST['reassign_user']);
			break;
		}
		++$delete_count;
	}

	$redirect = add_query_arg( array('delete_count' => $delete_count, 'update' => $update), $redirect);
	wp_redirect($redirect);
	exit();

break;

case 'delete':
	wp_die(__('This function is disabled.'));
	check_admin_referer('bulk-users');

	if ( empty($_REQUEST['users']) ) {
		wp_redirect($redirect);
		exit();
	}

	if ( !current_user_can('delete_users') )
		$errors = new WP_Error('edit_users', __('You can&#8217;t delete users.'));

	$userids = $_REQUEST['users'];

	include ('admin-header.php');
?>
<form action="" method="post" name="updateusers" id="updateusers">
<?php wp_nonce_field('delete-users') ?>
<?php echo $referer; ?>

<div class="wrap">
<h2><?php _e('Delete Users'); ?></h2>
<p><?php _e('You have specified these users for deletion:'); ?></p>
<ul>
<?php
	$go_delete = false;
	foreach ( (array) $userids as $id ) {
		$user = new WP_User($id);
		if ( $id == $current_user->ID ) {
			echo "<li>" . sprintf(__('ID #%1s: %2s <strong>The current user will not be deleted.</strong>'), $id, $user->user_login) . "</li>\n";
		} else {
			echo "<li><input type=\"hidden\" name=\"users[]\" value=\"{$id}\" />" . sprintf(__('ID #%1s: %2s'), $id, $user->user_login) . "</li>\n";
			$go_delete = true;
		}
	}
	$all_logins = $wpdb->get_results("SELECT ID, user_login FROM $wpdb->users, $wpdb->usermeta WHERE $wpdb->users.ID = $wpdb->usermeta.user_id AND meta_key = '".$wpdb->prefix."capabilities' ORDER BY user_login");
	$user_dropdown = '<select name="reassign_user">';
	foreach ( (array) $all_logins as $login )
		if ( $login->ID == $current_user->ID || !in_array($login->ID, $userids) )
			$user_dropdown .= "<option value=\"{$login->ID}\">{$login->user_login}</option>";
	$user_dropdown .= '</select>';
	?>
	</ul>
<?php if ( $go_delete ) : ?>
	<p><?php _e('What should be done with posts and links owned by this user?'); ?></p>
	<ul style="list-style:none;">
		<li><label><input type="radio" id="delete_option0" name="delete_option" value="delete" checked="checked" />
		<?php _e('Delete all posts and links.'); ?></label></li>
		<li><input type="radio" id="delete_option1" name="delete_option" value="reassign" />
		<?php echo '<label for="delete_option1">'.__('Attribute all posts and links to:')."</label> $user_dropdown"; ?></li>
	</ul>
	<input type="hidden" name="action" value="dodelete" />
	<p class="submit"><input type="submit" name="submit" value="<?php _e('Confirm Deletion'); ?>" class="button-secondary" /></p>
<?php else : ?>
	<p><?php _e('There are no valid users selected for deletion.'); ?></p>
<?php endif; ?>
</div>
</form>
<?php

break;

case 'doremove':
	check_admin_referer('remove-users');

	if ( empty($_REQUEST['users']) ) {
		wp_redirect('users.php');
	}

	if ( !current_user_can('edit_users') )
		die(__('You can&#8217;t remove users.'));

	$userids = $_REQUEST['users'];

	$update = 'remove';
 	foreach ($userids as $id) {
		if ($id == $current_user->id) {
			$update = 'err_admin_remove';
			continue;
		}
		remove_user_from_blog($id);
	}

	wp_redirect('users.php?update=' . $update);

break;

case 'removeuser':

	check_admin_referer('bulk-users');

	if (empty($_REQUEST['users'])) {
		wp_redirect('users.php');
	}

	if ( !current_user_can('edit_users') )
		$error = new WP_Error('edit_users', __('You can&#8217;t remove users.'));

	$userids = $_REQUEST['users'];

	include ('admin-header.php');
?>
<form action="" method="post" name="updateusers" id="updateusers">
<?php wp_nonce_field('remove-users') ?>
<div class="wrap">
<h2><?php _e('Remove Users from Blog'); ?></h2>
<p><?php _e('You have specified these users for removal:'); ?></p>
<ul>
<?php
	$go_remove = false;
 	foreach ($userids as $id) {
 		$user = new WP_User($id);
		if ($id == $current_user->id) {
			echo "<li>" . sprintf(__('ID #%1s: %2s <strong>The current user will not be removed.</strong>'), $id, $user->user_login) . "</li>\n";
		} else {
			echo "<li><input type=\"hidden\" name=\"users[]\" value=\"{$id}\" />" . sprintf(__('ID #%1s: %2s'), $id, $user->user_login) . "</li>\n";
			$go_remove = true;
		}
 	}
 	?>
<?php if($go_remove) : ?>
		<input type="hidden" name="action" value="doremove" />
		<p class="submit"><input type="submit" name="submit" value="<?php _e('Confirm Removal'); ?>" /></p>
<?php else : ?>
	<p><?php _e('There are no valid users selected for removal.'); ?></p>
<?php endif; ?>
</div>
</form>
<?php

break;

case 'adduser':
	wp_die(__('This function is disabled. Add a user from your community.'));
	check_admin_referer('add-user');

	if ( ! current_user_can('create_users') )
		wp_die(__('You can&#8217;t create users.'));

	$user_id = add_user();
	$update = 'add';
	if ( is_wp_error( $user_id ) )
		$add_user_errors = $user_id;
	else {
		$new_user_login = apply_filters('pre_user_login', sanitize_user(stripslashes($_REQUEST['user_login']), true));
		$redirect = add_query_arg( array('usersearch' => urlencode($new_user_login), 'update' => $update), $redirect );
		wp_redirect( $redirect . '#user-' . $user_id );
		die();
	}

case 'addexistinguser':
	check_admin_referer('add-user');
	if ( !current_user_can('edit_users') )
		die(__('You can&#8217;t edit users.'));

	$new_user_email = wp_specialchars(trim($_REQUEST['newuser']));
	/* checking that username has been typed */
	if ( !empty($new_user_email) ) {
		if ( $user_id = email_exists( $new_user_email ) ) {
			$username = $wpdb->get_var( "SELECT user_login FROM {$wpdb->users} WHERE ID='$user_id'" );
			if( ($username != null && is_site_admin( $username ) == false ) && ( array_key_exists($blog_id, get_blogs_of_user($user_id)) ) ) {
				$location = 'users.php?update=add_existing';
			} else {
				add_user_to_blog('', $user_id, $_REQUEST[ 'new_role' ]);
				do_action( "added_existing_user", $user_id );
				$location = 'users.php?update=add';
			}
			wp_redirect("$location");
			die();
		} else {
			wp_redirect('users.php?update=notfound' );
			die();
		}
	}
	wp_redirect('users.php');
	die();
break;

default:

	if ( !empty($_GET['_wp_http_referer']) ) {
		wp_redirect(remove_query_arg(array('_wp_http_referer', '_wpnonce'), stripslashes($_SERVER['REQUEST_URI'])));
		exit;
	}

	wp_enqueue_script('admin-users');
	wp_enqueue_script('admin-forms');

	include('admin-header.php');

	// Query the users
	$wp_user_search = new WP_User_Search($_GET['usersearch'], $_GET['userspage'], $_GET['role']);

	if ( isset($_GET['update']) ) :
		switch($_GET['update']) {
		case 'del':
		case 'del_many':
		?>
			<?php $delete_count = (int) $_GET['delete_count']; ?>
			<div id="message" class="updated fade"><p><?php printf(__ngettext('%s user deleted', '%s users deleted', $delete_count), $delete_count); ?></p></div>
		<?php
			break;
		case 'remove':
		?>
			<div id="message" class="updated fade"><p><?php _e('User removed from this blog.'); ?></p></div>
		<?php
			break;
		case 'add':
		?>
			<div id="message" class="updated fade"><p><?php _e('New user created.'); ?></p></div>
		<?php
			break;
		case 'promote':
		?>
			<div id="message" class="updated fade"><p><?php _e('Changed roles.'); ?></p></div>
		<?php
			break;
		case 'err_admin_role':
		?>
			<div id="message" class="error"><p><?php _e("The current user's role must have user editing capabilities."); ?></p></div>
			<div id="message" class="updated fade"><p><?php _e('Other user roles have been changed.'); ?></p></div>
		<?php
			break;
		case 'err_admin_del':
		?>
			<div id="message" class="error"><p><?php _e("You can't delete the current user."); ?></p></div>
			<div id="message" class="updated fade"><p><?php _e('Other users have been deleted.'); ?></p></div>
		<?php
			break;
		case 'err_admin_remove':
		?>
			<div id="message" class="error"><p><?php _e("You can't remove the current user."); ?></p></div>
			<div id="message" class="updated fade"><p><?php _e('Other users have been removed.'); ?></p></div>
		<?php
			break;
		case 'notactive':
		?>
			<div id="message" class="updated fade"><p><?php _e('User not added. User is deleted or not active.'); ?></p></div>
		<?php
			break;
		case 'add_existing':
		?>
			<div id="message" class="updated fade"><p><?php _e('User not added. User is already registered.'); ?></p></div>
		<?php
			break;
		case 'notfound':
		?>
			<div id="message" class="updated fade"><p><?php _e('User not found. Please ask them to signup here first.'); ?></p></div>
		<?php
			break;
		}
	endif; ?>

<?php if ( is_wp_error( $errors ) ) : ?>
	<div class="error">
		<ul>
		<?php
			foreach ( $errors->get_error_messages() as $message )
				echo "<li>$message</li>";
		?>
		</ul>
	</div>
<?php endif; ?>

<div class="wrap">
<form id="posts-filter" action="" method="get">
	<?php if ( $wp_user_search->is_search() ) : ?>
		<h2><?php printf(__('Users Matching "%s"'), wp_specialchars($wp_user_search->search_term)); ?></h2>
	<?php else : ?>
		<h2><?php _e('Manage Users'); ?></h2>
	<?php endif; ?>

<ul class="subsubsub">
<?php
$role_links = array();
$avail_roles = array();
$users_of_blog = get_users_of_blog();
//var_dump($users_of_blog);
foreach ( (array) $users_of_blog as $b_user ) {
	$b_roles = unserialize($b_user->meta_value);
	foreach ( (array) $b_roles as $b_role => $val ) {
		if ( !isset($avail_roles[$b_role]) )
			$avail_roles[$b_role] = 0;
		$avail_roles[$b_role]++;
	}
}
unset($users_of_blog);

$current_role = false;
$class = empty($_GET['role']) ? ' class="current"' : '';
$role_links[] = "<li><a href=\"users.php\"$class>" . __('All Users') . "</a>";
foreach ( $wp_roles->get_names() as $role => $name ) {
	if ( !isset($avail_roles[$role]) )
		continue;

	$class = '';

	if ( $role == $_GET['role'] ) {
		$current_role = $_GET['role'];
		$class = ' class="current"';
	}

	$name = translate_with_context($name);
	$name = sprintf(_c('%1$s (%2$s)|user role with count'), $name, $avail_roles[$role]);
	$role_links[] = "<li><a href=\"users.php?role=$role\"$class>" . $name . '</a>';
}
echo implode(' |</li>', $role_links) . '</li>';
unset($role_links);
?>
</ul>
	<p id="post-search">
	<input type="text" id="post-search-input" name="usersearch" value="<?php echo attribute_escape($wp_user_search->search_term); ?>" />
	<input type="submit" value="<?php _e( 'Search Users' ); ?>" class="button" />
	</p>

<div class="tablenav">

<?php if ( $wp_user_search->results_are_paged() ) : ?>
	<div class="tablenav-pages"><?php $wp_user_search->page_links(); ?></div>
<?php endif; ?>

<div class="alignleft">
<input type="submit" value="<?php _e('Remove'); ?>" name="removeit" class="button-secondary delete" />
<select name="new_role"><option value=''><?php _e('Change role to&hellip;') ?></option>"<?php wp_dropdown_roles(); ?></select>
<input type="submit" value="<?php _e('Change'); ?>" name="changeit" class="button-secondary" />
<?php wp_nonce_field('bulk-users'); ?>
</div>

<br class="clear" />
</div>

<br class="clear" />

	<?php if ( is_wp_error( $wp_user_search->search_errors ) ) : ?>
		<div class="error">
			<ul>
			<?php
				foreach ( $wp_user_search->search_errors->get_error_messages() as $message )
					echo "<li>$message</li>";
			?>
			</ul>
		</div>
	<?php endif; ?>


<?php if ( $wp_user_search->get_results() ) : ?>

	<?php if ( $wp_user_search->is_search() ) : ?>
		<p><a href="users.php"><?php _e('&laquo; Back to All Users'); ?></a></p>
	<?php endif; ?>

<table class="widefat">
<thead>
<tr class="thead">
	<th scope="col" class="check-column"><input type="checkbox" onclick="checkAll(document.getElementById('posts-filter'));" /> </th>
	<th><?php _e('Username') ?></th>
	<th><?php _e('Name') ?></th>
	<th><?php _e('E-mail') ?></th>
	<th><?php _e('Role') ?></th>
	<th class="num"><?php _e('Posts') ?></th>
</tr>
</thead>
<tbody id="users" class="list:user user-list">
<?php
$style = '';
foreach ( $wp_user_search->get_results() as $userid ) {
	$user_object = new WP_User($userid);
	$roles = $user_object->roles;
	$role = array_shift($roles);

	$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
	echo "\n\t" . user_row($user_object, $style, $role);
}
?>
</tbody>
</table>

<div class="tablenav">

<?php if ( $wp_user_search->results_are_paged() ) : ?>
	<div class="tablenav-pages"><?php $wp_user_search->page_links(); ?></div>
<?php endif; ?>

<br class="clear" />
</div>

</form>
<?php endif; ?>

</div>

<?php
	if ( is_wp_error($add_user_errors) ) {
		foreach ( array('user_login' => 'user_login', 'first_name' => 'user_firstname', 'last_name' => 'user_lastname', 'email' => 'user_email', 'url' => 'user_uri', 'role' => 'user_role') as $formpost => $var ) {
			$var = 'new_' . $var;
			$$var = attribute_escape(stripslashes($_REQUEST[$formpost]));
		}
		unset($name);
	}
?>

<br class="clear" />

<?php if( apply_filters('show_adduser_fields', true) ) {?>
<div class="wrap">
<h2 id="add-new-user"><?php _e('Add user from community') ?></h2>
<div class="narrow">
<form action="" method="post" name="adduser" id="adduser">
<?php wp_nonce_field('add-user') ?>
<input type='hidden' name='action' value='addexistinguser'>
<p><?php _e('Type the e-mail address of another user to add them to your blog.')?></p>

<table class="form-table">
	<tr class="form-field form-required">
		<th scope="row"><?php _e('User&nbsp;E-Mail')?></th>
		<td><input type="text" name="newuser" id="newuser" /></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><?php _e('Role:') ?></th>
			<td>
			<select name="new_role" id="new_role">
				<?php wp_dropdown_roles('subscriber'); ?>
			</select>
		</td>
	</tr>
</table>
<p class="submit">
	<?php echo $referer; ?>
	<input name="adduser" type="submit" id="addusersub" value="<?php _e('Add User &raquo;') ?>" />
</p>
</form>
</div>
<?php } ?>

<?php if ( is_wp_error( $add_user_errors ) ) : ?>
	<div class="error">
		<?php
			foreach ( $add_user_errors->get_error_messages() as $message )
				echo "<p>$message</p>";
		?>
	</div>
<?php endif; ?>
<div id="ajax-response"></div>
</div>

<?php
break;

} // end of the $action switch

include('admin-footer.php');
?>
