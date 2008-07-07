<?php
/*
Plugin Name: Blog Info
Plugin URI: http://bbvablogs.com
Description: Get a blog information by mixing its users extra fields
Author: Las Indias
Version: 1.0
Author URI: http://lasindias.com
License URI: http://www.gnu.org/licenses/gpl.html
*/

function blog_identity($blog_id){
	$identity = array();
	
	$identity_area = array();
	$identity_unidad = array();
	$identity_pais = array();
	$identity_genero = array();
	
	$blogusers = get_users_of_blog( $blog_id );
	
	if( is_array( $blogusers ) ) {
		foreach ( $blogusers as $user ) {
			$identity_area[get_usermeta($user->user_id, 'area')] += 1;
			$identity_unidad[get_usermeta($user->user_id, 'unidad')] += 1;
			$identity_pais[get_usermeta($user->user_id, 'pais')] += 1;
			$identity_genero[get_usermeta($user->user_id, 'genero')] += 1;
		}
	}
	/*
	arsort($identity_area);
	arsort($identity_unidad);
	arsort($identity_pais);
	arsort($identity_genero);
	*/
	$area_keys = array_keys($identity_area);
	$unidad_keys = array_keys($identity_unidad);
	$pais_keys = array_keys($identity_pais);
	$genero_keys = array_keys($identity_genero);
	
	$identity['mixed']=array();
	
	
	if (count($identity_area) > 1) array_push($identity['mixed'],$area_keys);
	else $identity['area'] = $area_keys[0];
	
	if (count($identity_unidad) > 1) array_push($identity['mixed'],$unidad_keys);
	else $identity['unidad'] = $area_keys[0];
	
	if (count($identity_pais) > 1) array_push($identity['mixed'],$pais_keys);
	else $identity['pais'] = $area_keys[0];
	
	if (count($identity_genero) > 1) array_push($identity['mixed'],$genero_keys);
	else $identity['genero'] = $area_keys[0];
	
	
	
	/*
	$identity['area'] = count($identity_area) > 1 ?  $identity['mixed'] +=1 : $area_keys[0];
	$identity['unidad'] = count($identity_unidad) > 1 ? $identity['mixed'] +=1 : $unidad_keys[0];
	$identity['pais'] = count($identity_pais) > 1 ? $identity['mixed'] +=1 : $pais_keys[0];
	$identity['genero'] = count($identity_genero) > 1 ? $identity['mixed']+=1  : $genero_keys[0];*/
	
	return $identity;
}


function primary_user_of_blog($blog_id){
	$users = get_users_of_blog($blog_id);
	foreach($users as $user){
		if (get_usermeta($user->user_id,'primary_blog')==$blog_id){
			return $user;
		}
		switch_to_blog($blog_id);
		$user_o = new WP_User($user->user_id);
		if ($user_o->has_cap('administrator') && strcmp($user->user_nicename,"admin")!=0){
			$result=$user;
		}
		restore_current_blog();
	}
	return $result;
}

function get_users_of_blog_by_rol($blog_id, $rol){
	$users = get_users_of_blog($blog_id);
	$result=array();
	foreach($users as $user){
		switch_to_blog($blog_id);
		$user_o = new WP_User($user->user_id);
		if ($user_o->has_cap($rol) && strcmp($user->user_nicename,"admin")!=0){
			array_push($result, $user);
		}
		restore_current_blog();
	}
	return $result;
}

function get_blogs_of_user_by_rol($user_id, $rol){
	$blogs = get_blogs_of_user($user_id);
	$result=array();
	foreach($blogs as $blog_id => $blog){
		switch_to_blog($blog_id);
		$user_o = new WP_User($user_id);
		if ($user_o->has_cap($rol)){
			array_push($result, $blog);
		}
		restore_current_blog();
	}
	return $result;
}
?>
