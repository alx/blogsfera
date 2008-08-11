<?php
require( 'wp-config.php' );
require( 'wp-blog-header.php' );

wp_enqueue_script('prototype');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />

	<title>Portal Feevy</title>

	<style type="text/css" media="screen">
		@import url( <?php bloginfo('stylesheet_url'); ?> );
	</style>
	
	<!--[if IE 6]>
	<link rel="stylesheet" href="<?php bloginfo('template_url');?>/style-ie6.css" type="text/css" media="screen" />
	<![endif]-->
	
	<?php wp_print_scripts(); ?>
	
	
</head>
<body>
<div id="contenedor">

	<div id="branding">
		<h1><a href="http://bbvablogs.com">Blogsfera BBVA</a></h1>
		<p class="descripcion">Con toda nuestra gente, algo nuevo nace cada día</p>
	</div>

	<div id="contenido">
		
		<?php feevy_code(); ?>
	</div>
</div>
</body>
</html>
 
