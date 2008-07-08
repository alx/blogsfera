<?php get_header(); ?>

<div id="site">

<div id="content">
	<?php if (is_tag()) { ?>
		<h2>Contacts Tagged with <span class="archived-feature"><?php single_tag_title(); ?></span></h2>
	<?php /* If this is a category archive */ } elseif (is_category()) { ?>
		<h2>Contacts in the <span class="archived-feature"><?php single_cat_title(''); ?></span> category.</h2>
	<?php /* If this is a yearly archive */ } elseif (is_day()) { ?>
		<h2>Contacts added on <span class="archived-feature"><?php the_time('l, F jS, Y'); ?></span></h2>
	<?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		<h2>Contacts added in <span class="archived-feature"><?php the_time('F, Y'); ?></span></h2>
	<?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		<h2>Contacts added in <span class="archived-feature"><?php the_time('Y'); ?></span></h2>
	<?php /* If these are search results */ } elseif (is_search()) { ?>
		<h2>Contacts that match '<span class="archived-feature"><?php the_search_query(); ?></span>'</h2>
	<?php /* Anything else that may get through the above filters */ } else {?>
		<h2>Contacts</h2>
	<?php } ?>
	
	<?php $alt = ""; ?>
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
	<?php if ($alt == "") { $alt = " alt"; } else { $alt = ""; } ?>	
	
	<div class="contact<?php echo $alt; ?>">
		<span class="m-name"><a href="<?php the_permalink(); ?>"><?php echo get_post_meta($post->ID, "First Name", true); ?> <?php echo get_post_meta($post->ID, "Last Name", true); ?></a></span>
		<span class="m-email"><?php $has_email = get_post_meta($post->ID, "Email", true); if ( $has_email == '' ) { echo '&nbsp;'; } else { ?><a href="mailto:<?php echo get_post_meta($post->ID, "Email", true); ?>"><?php echo get_post_meta($post->ID, "Email", true); } ?></a></span>
		<span class="m-mobile">Mobile: <span><?php echo get_post_meta($post->ID, "Mobile", true); ?></span></span>
		<hr/>
	</div>
	
	<?php endwhile; else: ?>
	<p><?php _e('Sorry, no posts matched your criteria.'); ?></p>
	
	<?php endif; ?>
	
</div>

<?php get_sidebar(); ?>
<?php get_footer(); ?>