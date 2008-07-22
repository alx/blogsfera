<?php get_header(); ?>

<div id="wrapper"> <!-- Empieza wrapper -->

<div id="contenido"><!-- Empieza contenido -->

<?php if (have_posts()) : ?>

 <h2 class="pagetitle">Resultados de la búsqueda</h2>
 <ol id="entradas">

<?php while (have_posts()) : the_post(); ?>

<li class="post" id="post-<?php the_ID(); ?>">
<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title(); ?>"><?php the_title(); ?></a></h2>
<p class="post-info"><?php the_time('l j \d\e F \d\e Y') ?> por <a href="mailto:<?php echo (get_the_author_email());?>"><?php the_author_firstname(); ?> <?php the_author_lastname(); ?></a></p>


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
