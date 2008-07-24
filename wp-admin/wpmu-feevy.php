<?php
require_once('admin.php');

$title = __('WordPress MU &rsaquo; Stats');
$parent_file = 'wpmu-admin.php';
wp_enqueue_script('timeframe');


require_once('admin-header.php');

if( is_site_admin() == false ) {
	wp_die( __('<p>You do not have permission to access this page.</p>') );
}

if (isset($_GET['updated'])) {
	?>
	<div id="message" class="updated fade"><p><?php _e('Options saved.') ?></p></div>
	<?php
}
?>

<div class="wrap">

<h2><?php _e('Feevy parameters'); ?></h2>
<form method="post" action="<?php bloginfo('url'); ?>/wp-admin/wpmu-feevy.php" >
		<div id='extra'>
		<label for="feevy number">feevy number</label>	
		<input type="text" name="feevy number" />
		<label for="api code">api code</label>	
		<input type="text" name="api code" />
		<input class="boton" type="submit" name="submit" value="Enviar" />
		</div>

</div>



<?php include('admin-footer.php'); ?>
