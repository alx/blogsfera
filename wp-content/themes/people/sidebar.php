<div id="sidebar">
	<h3 class="site-subtitle">Mi perfil</h3>
	<?php if(!isset($url)){$url = get_option('home');}?>
	<?php 
		$c_user = wp_get_current_user();
		echo '<p><img src="'.author_image_path($c_user->ID, $display = false).'" /></p><p><a href="'.$url.'/?id='.$c_user->ID.'" >Ver mi perfil</a><p>';

	?>
	<ul>
		<li><a href="http://bbvablogs.com" class='node'>PORTAL</a></li>
		<li><a href="http://chat.bbvablogs.com" class='node'>SALAS DE CHAT</a></li>
	</ul>

	<br />
	<h3 class="site-subtitle">Buscar</h3>
	<?php include (TEMPLATEPATH . '/searchform.php'); ?>

	<h3 class="site-subtitle">Areas</h3>
	<?php if(!isset($extra_data)){$extra_data = new User_Extra_Data;}?>
	
	<?php
		$areas = $extra_data->area;
		echo '<ul>';
		foreach ($areas as $key => $area){
			echo '<li><a href="'.$url.'/?area='.$key.'" >'.$area.'</a></li>';

		}
		echo '</ul>';

	?>

</div>
