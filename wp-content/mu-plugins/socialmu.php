<?php
/*
Plugin Name: SocialMu
Plugin URI: http://bbvablogs.com
Description: A social networking plugin
Author: Las Indias
Version: 0.1
Author URI: http://lasindias.com
License URI: http://www.gnu.org/licenses/gpl.html
*/


add_action('admin_menu', 'add_menu_comunidad_admin');

function add_menu_comunidad_admin(){
	if (is_site_admin()){
		add_submenu_page('wpmu-admin.php', 'Comunidad admin', 'Comunidad admin', 8, 'wpmu-socialmu.php');
	}
}

//add xfn rel metada for each user and each friend
function add_friend() {
	global $userdata;
	get_currentuserinfo();
	$rel = $_POST['friendship'].' '.$_POST['physical'].' '.$_POST['professional'].' '.$_POST['geographical'].' '.$_POST['family'].' '.$_POST['identity'].' '.$_POST['romantic'];
	update_usermeta($userdata->ID, "SocialMu_friend_".$_POST['user_id'], $rel);
	$content_mail = $userdata->display_name.' te ha agregado como contacto en la comunidad de la blogsfera, puedes ver su ficha pinchando en el siguiente enlace: http://comunidad.bbvablogs.com/?id='.$userdata->ID; 
	$user_friend = get_userdata($_POST['user_id']);
	wp_mail( $user_friend->user_email,  sprintf(__('Comunidad blogsfera - Nuevo contacto')), $content_mail, 'From: "Comunidad blogsfera bbva" <' . get_site_option( 'admin_email' ) . '>' );
}

//return an asociative array with friends ($friend_user_id => $rel)
function get_friends($user_id){
	global $wpdb;
	$metadata = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE user_id=$user_id", OBJECT);
	$friends = array();
	foreach ($metadata as $metadata){
		if (ereg('SocialMu_friend_',$metadata->meta_key)){
			$friends[str_replace("SocialMu_friend_", "", $metadata->meta_key)] = $metadata->meta_value;
		}
	}
	return $friends;
}

function del_friend($user_id, $friend_user_id){
	delete_usermeta($user_id, 'SocialMu_friend_'.$friend_user_id);
}

function get_last_blog_posts($blog_id, $count=1) {
	global $wpdb;

	$key = $blog_id."-".$post_id."-last_".$count."_blog_post";
	$posts = wp_cache_get( $key, "site-options" );
	if( $posts == false ) {
		$posts = $wpdb->get_results( "SELECT * FROM {$wpdb->base_prefix}{$blog_id}_posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date_gmt DESC LIMIT {$count}", ARRAY_A );
		wp_cache_add( $key, $post, "site-options", 120 );
	}

	return $posts;
}

//show basic form to add xfn rel
function show_xfn_form($user_id){

echo '
<form action="'; echo bloginfo('url'); echo '?id='.$user_id.'" method="post">

Amistad:<br />
	<select name="friendship" id="friendship">
		<option value="" id="none">Ninguna</option>
		<option value="Conocido" id="contact">Conocido</option>
		<option value="Amigo" id="acquaintance">Amigo</option>
		<option value="De mis mejores amigos" id="friend">De mis mejores amigos</option>
	</select>
Físico:<br />
	<input class="valinp" type="checkbox" name="physical" value="Le conozco en persona" id="met"/> Le conozco en persona<br />
	<input class="valinp" type="checkbox" name="professional" value="Compañero de trabajo" id="co-worker" /> Compañero de trabajo<br />
Geográfico:<br />
	<select name="geographical" id="geographical">
		<option value="" id="none">Ninguno</option>
		<option value="Vivo con él/ella" id="co-resident">Vivo con él/ella</option>
		<option value="Vecino/a" id="neighbor">Vecino/a</option>
	</select>			
<br />Familia:<br />
	<select name="family" id="family">
		<option value="" id="none">Ninguno</option>
		<option value=">Hijo/a" id="child">Hijo/a</option>
		<option value="Pariente Lejano" id="kin">Pariente Lejano</option>
		<option value="Padre/madre" id="parent">Padre/madre</option>
		<option value="Pariente cercano" id="sibling">Pariente cercano</option>
		<option value="Marido/mujer" id="spouse">Marido/mujer</option>
	</select>
<input type="hidden" value="true" name="xfn_post" /> <br />
<input type="hidden" name="user_id" value="'.$user_id.'" />

<input type="submit" value="Enviar">

</form>';


}


//functions to easy group blog creation

function add_friends_to_blog($blog_id){
	if (isset($_POST['count_friends'])){
		for ($i=1;$i<=(int)$_POST['count_friends'];$i++){
			if (isset($_POST[$i])){
				add_user_to_blog($blog_id, $_POST[$i], 'editor');
				setup_userdata($_POST[$i]);
				$content_mail = 'Has sido invitado a participar en un nuevo blog por un amigo. Puedes acceder a el desde el siguiente enlace: ' . get_blog_option( $blog_id, "siteurl" );
				wp_mail( $user_email,  sprintf(__('%s - Nuevo blog'), get_blog_option( $blog_id, "blogname" )), $content_mail, 'From: "Site Admin" <' . get_site_option( 'admin_email' ) . '>' );
			}
		}
	}
}

function show_group_blog_form_options(){
	global $current_user;
	$result = '';
	$friends = get_friends($current_user->ID);
	if (count($friends)!=0){
			$i=0;
			$results .= _e('Agrega si lo deseas a tus contactos como editores del nuevo blog: <br />');
			foreach($friends as $id => $rel){
				$i++;
				$friend = get_userdata((int)$id);
				$result .= '<img src="'.author_image_path($id, $display = false).'" width=25 height=25 /> '.$friend->display_name.' <input type="checkbox" name="'.$i.'" value="'.$id.'" /> <br />';
			}
			$result .= '<input type="hidden" name="count_friends" value="'.$i.'">';
	}
	echo $result;
}



?>
