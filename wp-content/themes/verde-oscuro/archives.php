<?php
/*
Template Name: Archives
*/
?>

<?php get_header(); ?>

<div id="wrapper"> <!-- Empieza wrapper -->

<div id="contenido"><!-- Empieza contenido -->

<?php include (TEMPLATEPATH . '/searchform.php'); ?>

<h2>Archivos por mes:</h2>
	<ul>
		<?php wp_get_archives('type=monthly'); ?>
	</ul>

<h2>Archives por tema:</h2>
	<ul>
		 <?php wp_list_categories(); ?>
	</ul>

</div><!-- Termina contenido -->
</div><!-- Termina wrapper -->

<?php get_sidebar(); ?>

<?php get_footer(); ?>
