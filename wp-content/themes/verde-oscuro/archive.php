<?php get_header(); ?>

<div id="wrapper"> <!-- Empieza wrapper -->

<div id="contenido"><!-- Empieza contenido -->

<?php is_tag(); ?>
		<?php if (have_posts()) : ?>

 	  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
 	  <?php /* If this is a category archive */ if (is_category()) { ?>
		<h2 class="pagetitle">Archivo para la categoría &#8216;<?php single_cat_title(); ?>&#8217;</h2>
 	  <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
		<h2 class="pagetitle">Post con la etiqueta &#8216;<?php single_tag_title(); ?>&#8217;</h2>
 	  <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
		<h2 class="pagetitle">Archivos del día <?php the_time('F jS, Y'); ?></h2>
 	  <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		<h2 class="pagetitle">Archivos del mes <?php the_time('F, Y'); ?></h2>
 	  <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		<h2 class="pagetitle">Archivos del año <?php the_time('Y'); ?></h2>
	  <?php /* If this is an author archive */ } elseif (is_author()) { ?>
		<h2 class="pagetitle">Archivos del autor</h2>
 	  <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
		<h2 class="pagetitle">Archivos</h2>
 	  <?php } ?>


<?php while (have_posts()) : the_post(); ?>

<li class="post" id="post-<?php the_ID(); ?>">
<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title(); ?></a></h2>
<p class="post-info"><?php the_time('l j \d\e F \d\e Y') ?> por <a href="mailto:<?php echo (get_the_author_email());?>"><?php the_author_firstname(); ?> <?php the_author_lastname(); ?></a></p>

<?php the_content('Leer más &raquo;'); ?>


<p class="postmetadata"><?php the_tags('<span class="tags">Etiquetas: ', ', ', '</span><br />'); ?>
Guardado en <?php the_category(', ') ?> | <?php edit_post_link('Editar', '', ' | '); ?>  <span class="comentarios"><?php comments_popup_link('Sin comentarios ', '1 Comentario ', '% Comentarios '); ?></span></p>

			</li>

		<?php endwhile; ?>

</ol><!-- Terminan entradas -->
  
	<ul class="nav-entradas">
		<li class="alignleft"><?php next_posts_link('&laquo; Anteriores') ?></li>
		<li class="alignright"><?php previous_posts_link('Siguientes &raquo;') ?></li>
	</ul>

	<?php else : ?>

	<h2 class="center">No encontrado</h2>
	<p class="center">Lo sentimos, pero la página buscada no se encuentra aquí.</p>

	<?php endif; ?>

</div><!-- Termina contenido -->
</div><!-- Termina wrapper -->

<?php get_sidebar(); ?>

<?php get_footer(); ?>

