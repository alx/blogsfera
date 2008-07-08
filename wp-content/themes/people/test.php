<?php get_header(); ?>
<div id="site">
<div id="content">
	<h2>Ult&iacute;mos Contactos</h2>
	
	<?php
	global $wpdb;
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
	error_log($query);
	$user_list = $wpdb->get_results( $query, ARRAY_A );
	
	?>
	
	<?php $alt = ""; ?>
	<?php if ($user_list): foreach ( $user_list as $user ) : ?>
	<?php if ($alt == "") { $alt = " alt"; } else { $alt = ""; } ?>
	
	<div class="contact<?php echo $alt; ?>">
		<span class="m-name"><a href="http://people.bbvablogs.com/user/<?php echo $user['ID'] ?>"><?php echo $user['display_name'] ?></a></span>
		<hr/>
	</div>
	<?php endforeach; else: ?>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
	<?php endif; ?>
	
</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>