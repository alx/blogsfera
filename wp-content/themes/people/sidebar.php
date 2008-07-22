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

	<h3 class="site-subtitle">Areas</h3>
	
	<?php
                $allFields = get_cimyFields();
                if (count($allFields) > 0) {
                    foreach ($allFields as $field) {
                            echo "ID: ".$field['ID']." \n";
                            echo "F_ORDER: ".$field['F_ORDER']." \n";
                            echo "NAME: ".$field['NAME']." \n";
                            echo "TYPE: ".$field['TYPE']." \n";
                            echo "VALUE: ".$field['VALUE']." \n";
                            echo "LABEL: ".$field['LABEL']." \n";
                            echo "DESCRIPTION: ".$field['DESCRIPTION']." \n";
                            echo "RULES: ";
                            print_r($field['RULES']);
                            echo "\n\n";
                    }
                }
	?>

</div>
