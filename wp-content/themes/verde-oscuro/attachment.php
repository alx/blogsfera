<?php get_header(); ?>

<div id="wrapper"> <!-- Empieza wrapper -->

<div id="contenido"><!-- Empieza contenido -->

  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>

<?php $attachment_link = get_the_attachment_link($post->ID, true, array(450, 800)); // This also populates the iconsize for the next line ?>
<?php $_post = &get_post($post->ID); $classname = ($_post->iconsize[0] <= 128 ? 'small' : '') . 'attachment'; // This lets us style narrow icons specially ?>
		<div class="post" id="post-<?php the_ID(); ?>">
			<h2><a href="<?php echo get_permalink($post->post_parent); ?>" rev="attachment"><?php echo get_the_title($post->post_parent); ?></a> &raquo; <a href="<?php echo get_permalink() ?>" rel="bookmark" title="Permanent Link: <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
			<div class="entry">
				<p class="<?php echo $classname; ?>"><?php echo $attachment_link; ?><br /><?php echo basename($post->guid); ?></p>

				<?php the_content('<p class="serif">Read the rest of this entry &raquo;</p>'); ?>

				<?php wp_link_pages(array('before' => '<p><strong>Páginas:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>

<p class="singlemetadata">
Esta entrada fue guardada
<?php /* This is commented, because it requires a little adjusting sometimes.
You'll need to download this plugin, and follow the instructions:
http://binarybonsai.com/archives/2004/08/17/time-since-plugin/ */
/* $entry_datetime = abs(strtotime($post->post_date) - (60*120)); echo time_since($entry_datetime); echo ' ago'; */ ?>
el <?php the_date() ?> a las <?php the_time() ?> y fue guardada en <?php the_category(', ') ?>.
Puede seguir los comentarios a este artículo a través de la sindicación <?php comments_rss_link('RSS 2.0'); ?>.

<?php if (('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Both Comments and Pings are open ?>
							Puede <a href="#respond">dejar un comentario</a>, o hacer un <a href="<?php trackback_url(); ?>" rel="trackback">trackback</a> desde su sitio.

<?php } elseif (!('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
							// Only Pings are Open ?>
							Los comentarios están cerrados por el momento, pero puede hacer un <a href="<?php trackback_url(); ?> " rel="trackback">trackback</a> desde su sitio.

<?php } elseif (('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Comments are open, Pings are not ?>
							Puede dejar un comentario. Los trackbacks no están permitidos por el momento.

<?php } elseif (!('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
							// Neither Comments, nor Pings are open ?>
							Tanto comentarios como trackbacks están cerrados.

<?php } edit_post_link('Editar este artículo.','',''); ?></p>

			</div>
		</div>

	<?php comments_template(); ?>

	<?php endwhile; else: ?>

		<p>No se ha encontrado ningún artículo.</p>

<?php endif; ?>

</div><!-- Termina contenido -->
</div><!-- Termina wrapper -->

<?php get_sidebar(); ?>

<?php get_footer(); ?>

