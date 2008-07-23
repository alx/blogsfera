<div id="sidebar">
	<h3 class="site-subtitle">Mi perfil</h3>
	<?php if(!isset($url)){$url = get_option('home');}?>
	<?php 
		$c_user = wp_get_current_user();
		echo '<p><img src="'.author_image_path($c_user->ID, $display = false).'" /></p><p><a href="'.$url.'/?id='.$c_user->ID.'" >Ver mi perfil</a><p>';

	?>

	<br />
	<h3 class="site-subtitle">Buscar</h3>
	<?php include (TEMPLATEPATH . '/searchform.php'); ?>

</div>
