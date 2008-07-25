<?php
require_once('admin.php');

$title = __('Feevy admin');
$parent_file = 'wpmu-admin.php';

require_once('admin-header.php');

if( is_site_admin() == false ) {
	wp_die( __('<p>You do not have permission to access this page.</p>') );
}

if (isset($_POST['updated'])) {
	?>
	<div id="message" class="updated fade"><p><?php _e('Options saved.') ?></p></div>
	<?php
}
?>

<?php 
if (isset($_POST['feevy_number']) && isset($_POST['api_code'])){
	global $wpdb;
	add_site_option("feevy_number", $_POST['feevy_number']);
	add_site_option("api_code", $_POST['api_code']);
}

?>

<div class="wrap">

<h2><?php _e('Feevy parameters'); ?></h2>
<form method="post" action="<?php bloginfo('url'); ?>/wp-admin/wpmu-feevy.php" >
		<div id='extra'>
		<label for="feevy_number">feevy number</label>	
		<input type="text" name="feevy_number" value="<?php echo get_site_option('feevy_number'); ?>"/>
		<label for="api_code">api code</label>	
		<input type="text" name="api_code" value="<?php echo get_site_option('api_code'); ?>"/>
		<input class="boton" type="submit" name="submit" value="Enviar" />
		<input type="hidden" name="updated" value="updated" />
		</div>

</div>



<?php include('admin-footer.php'); ?>
