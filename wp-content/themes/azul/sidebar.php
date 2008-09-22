
<div id="navegacion">

<ul>
			<?php 	/* Widgetized sidebar, if you have the plugin installed. */
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar(1) ) : ?>
			<li>
				<?php include (TEMPLATEPATH . '/searchform.php'); ?>
			</li>

			<!-- Author information is disabled per default. Uncomment and fill in your details if you want to use it.
			<li><h2>Author</h2>
			<p>A little something about you, the author. Nothing lengthy, just an overview.</p>
			</li>
			-->

			<?php if ( is_404() || is_category() || is_day() || is_month() ||
						is_year() || is_search() || is_paged() ) {
			?> <li>

			<?php /* If this is a 404 page */ if (is_404()) { ?>
			<?php /* If this is a category archive */ } elseif (is_category()) { ?>
			<p>Estás navegando los artículos de la categoría <?php single_cat_title(''); ?>.</p>

			<?php /* If this is a yearly archive */ } elseif (is_day()) { ?>
			<p>Estás navegando los artículos de <a href="<?php bloginfo('url'); ?>/"><?php echo bloginfo('name'); ?></a>
			del día <?php the_time('l, F jS, Y'); ?>.</p>

			<?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
			<p>Estás navegando los artículos <a href="<?php bloginfo('url'); ?>/"><?php echo bloginfo('name'); ?></a>
			de <?php the_time('F, Y'); ?>.</p>

			<?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
			<p>Estás navegando los artículos de <a href="<?php bloginfo('url'); ?>/"><?php echo bloginfo('name'); ?></a>
			del año <?php the_time('Y'); ?>.</p>

			<?php /* If this is a monthly archive */ } elseif (is_search()) { ?>
			<p>Hiciste una búsqueda en <a href="<?php echo bloginfo('url'); ?>/"><?php echo bloginfo('name'); ?></a>
			de <strong>'<?php the_search_query(); ?>'</strong>. Si no has podido encontrar nada relacionado, intenta con los links debajo.</p>

			<?php /* If this is a monthly archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
			<p>Estás navegando los artículos de <a href="<?php echo bloginfo('url'); ?>/"><?php echo bloginfo('name'); ?></a>.</p>

			<?php } ?>

			</li> <?php }?>

			<?php wp_list_pages('title_li=<h2>Páginas</h2>' ); ?>

			<li><h2>Archivos</h2>
				<ul>
				<?php wp_get_archives('type=monthly'); ?>
				</ul>
			</li>

			<?php wp_list_categories('show_count=1&title_li=<h2>Categorías</h2>'); ?>

			<?php /* If this is the frontpage */ if ( is_home() || is_page() ) { ?>
				<?php wp_list_bookmarks(); ?>

				<li><h2>Meta</h2>
				<ul>
					<?php wp_register(); ?>
					<li><?php wp_loginout(); ?></li>
					<?php wp_meta(); ?>
				</ul>
				</li>
			<?php } ?>

			<?php endif; ?>

</ul>

</div><!-- Termina navegacion -->

<div id="extra"><!-- Contenido extra (última columna) -->

<ul><!-- Empieza lista principal de contenido extra -->


<?php 	/* Widgetized sidebar, if you have the plugin installed. */
					if ( !function_exists('dynamic_sidebar') || !dynamic_sidebar(2) ) : ?>

<?php widget_suscribe(); ?>

<? if get_site_option('feevy_number') : ?>
<li id="feevy">
<h2>Feevy</h2>
<script type="text/javascript" src="http://www.feevy.com/code/<?php echo get_site_option('feevy_number'); ?>/open-css"></script>
</li>
<? endif; ?>

<?php endif; ?>

</ul><!-- Termina lista principal de contenido extra -->

</div><!-- Termina contenido extra -->

