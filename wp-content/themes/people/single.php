<div id="content">


	<div class="vitals">
		<img class="contact-picture" src="<?php $avatar = author_image_path($_GET['id'], $display = false); if ($avatar == '') { echo '/wp-content/themes/contact-manager/images/contact-default.png'; }else {echo $avatar;} ?>" alt="" />
		<?php $user_data = get_userdata($_GET['id']); ?>
		
		<p>
			<span class="name"><h2><?php echo $user_data->display_name; ?><h2></span>
			
			<ul class="phone-numbers">
			<li>e-mail <span class="phone-number"><a href="mailto:<?php echo $user_data->user_email; ?>"><?php echo $user_data->user_email; ?></a></span></li>
			<li><?php 
					echo get_usermeta($_GET['id'], 'pais')
			?></li>
			<li><?php
					$url = get_option('home');
					$area = get_usermeta($_GET['id'], 'area');
					$unidad = 'unidad_'.$area;
					$unidad_extra = $extra_data->$unidad;
					echo '<a href="'.$url.'/?area='.$area.'" >'.$extra_data->area[$area].'</a> - '.'<a href="'.$url.'/?unidad='.get_usermeta($_GET['id'], 'unidad').'" >'.$unidad_extra[get_usermeta($_GET['id'], 'unidad')].'</a>';					
					
				?></li>
			</ul>
			
			


		</p>
		<span class="clearer"></span>
	</div>
	
	<div class="phone">

			<?php
			global $wpdb;
			$blogs = get_blogs_of_user($_GET['id']);
			$current_blog_id = $wpdb->blogid;
			$links_to_blogs = '';
			$sw = 0;
			foreach ($blogs as $blog_id => $blog){ 
				switch_to_blog($blog_id);
				$user = new WP_User($_GET['id']);
				if ($user->has_cap('edit_posts')){
					$links_to_blogs .= '<span class="organization"><a href="'.$blog->siteurl.'">'.$blog->blogname.'</a></span>';
					$sw += 1;
				}
				
			}
			if ($sw > 1){
				echo '<h3 class="site-subtitle">Mis blogs</h3>';
				echo $links_to_blogs;
			}elseif ($sw == 1){
				echo '<h3 class="site-subtitle">Mi Blog</h3>';
				echo $links_to_blogs;
			}else{
				echo '<h3 class="site-subtitle">Mi Blog</h3><span style="font-size:11px;"><p>Todavia no tengo blog</p></span>';
			}
			switch_to_blog($current_blog_id);	
			?>
		
	</div>

	<div class="additional-info">
		<!-- You can easily add extra information here using the h3.site-subtitle and <p> tags for display consistency -->
		<div class="address">
			<h3 class="site-subtitle">Información sobre mi</h3>
			<?php  global $userdata;
			if ($userdata->ID == $_GET['id'] && !$_GET['edit']){
				echo '<p><span class="elim"><a href="?id='.$_GET['id'].'&edit=true">Editar Esta información</a></span></p>';
			}				
			if ($_GET['edited'] && $_GET['id']==$userdata->ID){
				update_usermeta($_GET['id'], 'user_info', $_POST['info']);
			}
			if ($_GET['edit'] && $_GET['id']==$userdata->ID){
				echo '<form action="?edited=true&id='.$_GET['id'].'" method="post"><textarea name="info" rows="15" cols="30">'.get_usermeta($_GET['id'],'user_info').'</textarea><br /><span style="text-align: right;"><p><input type="submit" value="enviar cambios" /></p></span></form>';
			}elseif (strlen(get_usermeta($_GET['id'],'user_info'))==0){
				echo '<p>Todavía no he escrito nada, pronto lo actualizaré ;).</p>';
				
			}else{
				echo '<p>'.get_usermeta($_GET['id'],'user_info').'</p>';
			}
			?>

		</div>
		<!-- This is an optional area for any extra data fields you may want to add. It shows up beneath the mobile/phone/fax numbers. -->
		<div class="optional-fields">
			<h3 class="site-subtitle">Agregame a tu red de contactos</h3>
			<?php show_xfn_form($_GET['id']); ?>
			<?php if ($_POST['xfn_post']=='true'){add_friend();}?>
			<?php if ($_GET['elim']){del_friend($userdata->ID,$_GET['elim']);} ?>
		</div>
	</div>

	<div class="extra">
	
		<div class="notes">
			<?php 
				$primary_blog = get_usermeta($_GET['id'], "primary_blog");
				if (strlen($primary_blog) > 0 && strcmp($blog->siteurl,'http://bbvablogs.com')!=0){
					  $posts=get_last_blog_posts($primary_blog, 5);
				}else{
					  foreach ($blogs as $blog_id => $blog){
						$details = get_blog_details($blog_id);
						if( is_object( $details ) && $details->archived == 0 && $details->spam == 0 && $details->deleted == 0 && strcmp($blog->siteurl,'http://bbvablogs.com')!=0) {
							$ret = $blog_id;
							break;
						}
					  }
					  $posts=get_last_blog_posts($ret, 5);
				}
			 ?>
			<h3 class="site-subtitle">Último<?php if (count($posts)>2){echo 's posts';}else{echo ' post';}?> en el blog</h3>
			<?php	if (count($posts)>1){
					foreach($posts as $post){
						if ($post['guid']){
			?>	
							<h2><a href="<?php echo $post['guid']; ?>" rel="bookmark" title="Permanent Link: <?php echo $post['guid']; ?>"><?php echo $post['post_title']; ?></a></h2>
			<?php 		}	}
				}else{echo '<p>No hay ninguna entrada en el blog todavía.</p>';}
			?>
		</div>
		
		<div class="related-contacts">
			<h3 class="site-subtitle">Mis Contactos</h3>
			<?php 
				$contacts = get_friends($_GET['id']);
				if (count($contacts)==0){echo '<p>Todavía no tengo ningún contacto</p>';}else{
				$result = "<ul>";
				foreach ($contacts as $contact_id => $rel){
					$contact_data = get_userdata((int)$contact_id);
					$result .= '<li><img src="'.author_image_path($contact_id, $display = false).'" width=25 height=25 /> <a href="?id='.$contact_id.'">'.$contact_data->display_name.'</a>. -> '.$rel.'</li>';
					if ($userdata->ID == $_GET['id']){
					$result .= '<span class="elim"><a href="?id='.$_GET['id'].'&elim='.$contact_id.'">Eliminar este contacto</a></span>';
					}
				}
				$result .= "</ul>";
				echo $result;}
			?>
			
		</div>
		
		<div class="contact-tags"><?php the_tags('<span>TAGS: </span>',', ',''); ?></div>
		
	</div>

	<span class="clearer"></span>
</div>
