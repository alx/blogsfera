<?php
/*
Plugin Name: Blogsfera Portal Widgets
Plugin URI: http://github.com/alx/blogsfera/tree
Description: Various widgets to be displayed on Blogsfera portal
Author: Alexandre Girard, Las Indias
Version: 1.0
Author URI: http://blog.alexgirard.com
*/

// Widget: Recent articles
// Description: Show last articles from the blogsfera
function blogsfera_widget_recent_articles($args) {
	extract($args);
	$title = __('Art&iacute;culos recientes');
	?>
	
	<li id="art-recientes" class="clearfix">
		<h2><?php echo $title; ?></h2>
		<?php get_last_updated($num = 20, $display=true); ?>
	</li>
	<?php
}

// Widget: Avisos
// Description: Display last news from another blog. Blog_id is 2 by default.
function blogsfera_widget_avisos($args) {
	$blog_id = 2;
	extract($args);
	$title = __('Avisos');
	?>
	<li id="avisos-blogsfericos" class="clearfix">
	<h2><?php echo $title; ?></h2>

	<div class="post">
	<?php 
		switch_to_blog($blog_id);

		// Get latest avisos post
		$lastposts = get_posts('numberposts=1');

		// the Loop
		foreach($lastposts as $post) :
			setup_postdata($post); ?>
			<h3><?php the_title(); ?></h3>
			<p><?php the_content(); ?></p>
		<?php 
		endforeach; 

		// Return to portal blog
		restore_current_blog(); ?>
	</div>
	</li>
	<?php
}

// Widget: Search
// Description: Display search boxes
function blogsfera_widget_search($args) {
	extract($args);
	$title = __('Buscador de blogs');
	?>
	
	<li id="busqueda" class="clearfix">
		<h2><?php echo $title; ?></h2>
		<ul>

			<li id="por-nombre">
				<p>Busca por nombre</p>
				<form action="<?php bloginfo('url'); ?>/search/" method="post">
					<input name="type" value="blog" type="hidden" />
					<input value="" name="params" type="text" />
					<input value="Buscar" type="submit" />
				</form>
			</li>

			<li id="por-autor">
				<p>Busca por autor</p>
				<form action="<?php bloginfo('url'); ?>/search/" method="post">
					<input name="type" value="name" type="hidden" />
					<input value="" name="params" type="text" />
					<input value="Buscar" type="submit" />
				</form>
			</li>
		</ul>
	</li>
	<?php
}

// Widget: Chat
// Description: Display a chat box
function blogsfera_widget_chat($args) {
	extract($args);
	$title = __('Graffitis');
	?>
	
	<li id="chat" class="clearfix">
		<h2><?php echo $title; ?></h2>
		<?php jal_get_shoutbox(); ?>
	</li>
	<?php
}

// Widget: Mas Activos
// Description: Display most active blogs
function blogsfera_widget_most_active($args) {
	extract($args);
	$title = __('Blogs m&aacute;s activos en la &uacute;ltima semana');
	?>
	
	<li id="mas-activos" class="clearfix">
		<h2><?php echo $title; ?></h2>
		<?php get_most_active_blogs(); ?>
	</li>
	<?php
}

// Widget: Mas Leidos
// Description: Display most read blogs
function blogsfera_widget_most_read($args) {
	$numberposts = 6;
	$days_back = 7;
	extract($args);
	$title = __('Contenidos m&aacute;s le&iacute;dos de la semana');
	?>
	
	<li id="mas-leido" class="clearfix">
		<h2><?php echo $title; ?></h2>
	 	<?php
	 	$myposts = zap_get_top_posts($numberposts, $days_back);
		$list = "";
		$last = count($myposts) - 1;
		$count = 0;
	 	foreach($myposts as $post) :

			$post_title = $post->post_title;
			$post_link = $post->guid;

			if(!empty($post_title) && !empty($post_link)){
				$list .= "<li";
				
				// Special class for last element
				if($count == $last) { $list .= " class='last clearfix'"; }

			    $list .= "><a href='$post_link'>$post_title</a></li>";
			}
			$count++;
		endforeach; 
		echo "<ul>$list</ul>";
		?>
	</li>
	<?php
}

// Widget: My Blogs
// Description: Display list of owner blog and administration link
function blogsfera_widget_my_blog($args) {
	extract($args);
	$title = __('Mi Blog');
	?>
	
	<li id="mi-blog" class="clearfix">
		<h2><?php echo $title; ?></h2>
		<ul>
		<?php 
		global $current_user, $wpdb;
		$id = $current_user->ID;
		$current_blog_id = $wpdb->blogid;
		$blogs = get_blogs_of_user($id);
		$primary_blog = $blogs[1];
		if ( !empty($blogs) ){
		?>
				<?php
				if(count($blogs) == 1){
					foreach ( $blogs as $blog ) {
						echo '<li><a href="http://' . $blog->domain . $blog->path . '">Ir a mi blog</a> o ';
						echo '<a href="http://' . $blog->domain . $blog->path . 'wp-admin/">gestionarlo</a></li>';
						$primary_blog = $blog;
					}
				} else {
					foreach ( $blogs as $blog_id => $blog ) {
						switch_to_blog($blog_id);
						$user = new WP_User($id);
						if ($user->has_cap('edit_posts')){
							echo '<li><a href="http://' . $blog->domain . $blog->path . '">Ir a ' . addslashes( $blog->blogname ) . '</a> o ';
							echo '<a href="http://' . $blog->domain . $blog->path . 'wp-admin/">gestionarlo</a></li>';
						}
					}
					switch_to_blog($current_blog_id);

				}

				echo "<li><a href=\"wp-signup.php\">Crear otro blog</a></li>";


		} else { ?>
			<li><a href="wp-signup.php">Crear mi primer blog</a></li>
		<?php } ?>
		<li class="last clearfix"><a href="wp-login.php?action=logout">Salir</a></li>
		</ul>
	</li>
	<?php
}

// Widget: News
// Description: Display last post from portal
function blogsfera_widget_news($args) {
	extract($args);
	?>
	
	<li id="news" class="clearfix">
		<?php 
		$lastposts = get_posts('numberposts=1');

		// the Loop
		foreach($lastposts as $post) :
			setup_postdata($post);
		    ?>
			<h2>&iexcl;&iexcl;Bienvenidos!!</h2>
			<ul>
			<li class="last clearfix">
			<?php the_content(); ?>
			</li>
			</ul>
		<?php endforeach; ?>
	</li>
	<?php
}

// Widget: Search Results
// Description: Display search resut. Need "Search_results" page slug
function blogsfera_widget_search_result($args) {
	extract($args);
	$title = __('Resultados de la b&uacute;squeda');
	?>
	
	<li id="search-results" class="clearfix">
		<h2><?php echo $title; ?></h2>
		<p class="query">Estos son los resultados de su b&uacute;squeda de "<?php current_search();?>":</p>
		<ul>
			<?php 
			if ($result = search_results()):
				$list = "";
				$last = count($result) - 1;
				$i = 0;
				reset($result);

				// Display blog info
				if (search_type() == 'blog'):

					foreach ($result as $blog_result):
						$blog = get_blog_details($blog_result);

						$users = get_users_of_blog_by_rol($blog_result, 'edit_posts');

						$list .= "<li";

						// Display last element class
						if($i == $last)
							$list .= " class='last clearfix'";

						$list .= "><h3><a href='$blog->siteurl'>$blog->blogname</a></h3><ul>";

						foreach($users as $user){
							$list .= "<li><a class='avatar' href='$blog->siteurl'>";
							$list .= "<img src='".author_image_path($user->user_id, $display = false)."' alt='avatar' width='48px' height='48px'/></a>";
							$list .= "<p style='font-size:15px;'>$user->display_name</p></li>";
						}
						$list.="</ul></li>";

						$i++;
					endforeach;

				// Display user info
				else:
					global $wpdb;
					$current_blog_id = $wpdb->blogid;
					foreach ($result as $user):
						$blogs = get_blogs_of_user($user['ID'], true);
						foreach($blogs as $blog_id => $blog){
						//$blog = get_active_blog_for_user($user['ID']);
							switch_to_blog($blog_id);
							$user_o = new WP_User($user['ID']);
							if ($user_o->has_cap('edit_posts')){
								$list .= "<li";

								// Display last element class
								if($i == $last)
									$list .= " class='last clearfix'";

								$list .= "><a class='avatar' href='$blog->siteurl'>";
								$list .= "<img src='".author_image_path($user['ID'], $display = false)."' alt='avatar' width='48px' height='48px'/>";
								$list .= "</a><h3><a href='$blog->siteurl'>$blog->blogname</a></h3>";
								$list .= "<p>by ".$user['display_name']."</p></li>";

							}
						}
						$i++;
					endforeach;
					switch_to_blog($current_blog_id);
				endif;

				echo $list;
			else :
			    echo "<li>No se hallaron resultados</li>";
			 endif; ?>
		</ul>
	</li>
	<?php
}

// Widget: Tags Results
// Description: Display tag resut. Need "Tag_results" page slug
function blogsfera_widget_tag_results($args) {
	extract($args);
	$title = __('Resultados de la tag');
	?>
	
	<li id="search-results" class="clearfix">
		<h2><?php echo $title; ?></h2>
		<p class="query">Estos son los resultados con el tag "<?php current_tag();?>":</p>
		<ul>
		<?php if ($result = tag_results()):
		global $post;
		$result = array_slice($result, 0, 12);
		$list = "";
		$last = count($result) - 1;
		$i = 0;
		foreach ($result as $post):
			$list .= "<li";
			if($i == $last)
				$list .= " class='last clearfix'";
			$list .= "><a class='avatar' href='$post->guid'><img src='".author_image_path($post->post_author, $display = false)."' alt='avatar' width='48px' height='48px'/></a><h3><a href='$post->guid'>$post->post_title</a></h3><p>hace ".get_time_difference($post->post_date_gmt, $display=true)."</p></li>";
			$i++;
		endforeach;
		echo $list;
		else : ?>
		    <li>Not Found</li>
		 <?php endif; ?>
		</ul>
	</li>
	<?php
}

// Widget: Tags
// Description: Display a tag cloud
function blogsfera_widget_tag_cloud($args) {
	extract($args);
	$title = __('Etiquetas de la comunidad');
	?>
	
	<li id="tags" class="clearfix">
		<h2><?php echo $title; ?></h2>
		<div id="searchtag">
		<ul>
			<li class="last clearfix">
				<script type="text/javascript">
					function BuscarTag(){
						if($('searchtaginput').value != ' '){	document.location.href='<?php echo str_replace("/", "\/", get_option('home')); ?>\/tag\/'+$('searchtaginput').value;
						}
					}
					function DeleteInputBox(){
						if($('searchtaginput').value == "Inserte una etiqueta"){
							$('searchtaginput').value='';
						}
					}
				</script>
				<input type="text" id="searchtaginput" value="Inserte una etiqueta" onClick="javascript:DeleteInputBox();"/>
				<input type="button" id="searchtagbutton" value="Buscar" onclick="javascript:BuscarTag();"/>
			</li>
		</ul>
		</div>
		<div id="tagcloud">
			<?php mu_tag_cloud(); ?>
		</div>
	</li>
	<?php
}

// Widget: User recientes
// Description: Display avatars of recent users
function blogsfera_widget_recent_users($args) {
	extract($args);
	$title = __('Usuarios m&aacute;s recientes de la comunidad');
	?>
	
	<li id="users-recientes" class="clearfix">
		<h2><?php echo $title; ?></h2>
	  	<?php get_avatar_list(); ?>
	</li>
	<?php
}

// Register widgets

$widget_ops = array('classname' => 'widget_recent_articles', 'description' => __("Show last articles from the blogsfera") );
wp_register_sidebar_widget('widget_recent_articles', __('Art&iacute;culos recientes'), 'blogsfera_widget_recent_articles', $widget_ops);

$widget_ops = array('classname' => 'widget_avisos', 'description' => __("Display last news from another blog. Blog_id is 2 by default.") );
wp_register_sidebar_widget('widget_avisos', __('Avisos'), 'blogsfera_widget_avisos', $widget_ops);

$widget_ops = array('classname' => 'widget_search', 'description' => __("Display search boxes") );
wp_register_sidebar_widget('widget_search', __('Buscador de blogs'), 'blogsfera_widget_search', $widget_ops);

$widget_ops = array('classname' => 'widget_chat', 'description' => __("Display a chat box") );
wp_register_sidebar_widget('widget_chat', __('Graffitis'), 'blogsfera_widget_chat', $widget_ops);

$widget_ops = array('classname' => 'widget_most_active', 'description' => __("Display most active blogs") );
wp_register_sidebar_widget('widget_most_active', __('Blogs m&aacute;s activos en la &uacute;ltima semana'), 'blogsfera_widget_most_active', $widget_ops);

$widget_ops = array('classname' => 'widget_most_read', 'description' => __("Display most read blogs") );
wp_register_sidebar_widget('widget_most_read', __('Contenidos m&aacute;s le&iacute;dos de la semana'), 'blogsfera_widget_most_read', $widget_ops);

$widget_ops = array('classname' => 'widget_my_blog', 'description' => __("Display list of owner blog and administration link") );
wp_register_sidebar_widget('widget_my_blog', __('Mi Blog'), 'blogsfera_widget_my_blog', $widget_ops);

$widget_ops = array('classname' => 'widget_news', 'description' => __("Display last post from portal") );
wp_register_sidebar_widget('widget_news', __('News'), 'blogsfera_widget_news', $widget_ops);

$widget_ops = array('classname' => 'widget_search_result', 'description' => __("Display search resut. Need 'Search_results' page slug") );
wp_register_sidebar_widget('widget_search_result', __('Resultados de la b&uacute;squeda'), 'blogsfera_widget_search_result', $widget_ops);

$widget_ops = array('classname' => 'widget_tag_results', 'description' => __("Display tag resut. Need 'Tag_results' page slug") );
wp_register_sidebar_widget('widget_tag_results', __('Resultados de la tag'), 'blogsfera_widget_tag_results', $widget_ops);

$widget_ops = array('classname' => 'widget_tag_cloud', 'description' => __("Display a tag cloud") );
wp_register_sidebar_widget('widget_tag_cloud', __('Etiquetas de la comunidad'), 'blogsfera_widget_tag_cloud', $widget_ops);

$widget_ops = array('classname' => 'widget_recent_users', 'description' => __( "Display avatars of recent users") );
wp_register_sidebar_widget('recent_users', __('Usuarios m&aacute;s recientes de la comunidad'), 'blogsfera_widget_recent_users', $widget_ops);

?>