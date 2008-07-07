<?php
/*
Template Name: Tag_results
*/
?>
<?php get_header(); ?>

<ul class="columna primera">
	<?php blogsfera_widget_my_blog(); ?>
	<?php blogsfera_widget_chat(); ?>
	<?php blogsfera_widget_most_read(); ?>
	<?php blogsfera_widget_news(); ?>
</ul>

<ul class="columna">
	<?php blogsfera_widget_tag_results(); ?>
	<?php blogsfera_widget_recent_users(); ?>
	<?php blogsfera_widget_recent_articles(); ?>
</ul>

<ul class="columna">
	<?php blogsfera_widget_search(); ?>
	<?php blogsfera_widget_avisos(array('blog_id' => 2)); ?>
	<?php blogsfera_widget_tag_cloud(); ?>
</ul>

<?php get_footer(); ?>

