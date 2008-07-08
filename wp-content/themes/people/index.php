<?php get_header(); ?>
<?php if ($_GET[id]){include('single.php');}else{ ?>
<div id="content">
	<h2>Todos los usuarios:</h2>
	<?php 
	global $wpdb;
	$url = get_option('home');
	if ($_GET['area']){
		$users = $wpdb->get_col("SELECT user_id FROM $wpdb->usermeta WHERE meta_key='area' and meta_value='".$_GET['area']."' ORDER BY meta_value ASC");
	}elseif ($_GET['unidad']){
		$users = $wpdb->get_col("SELECT user_id FROM $wpdb->usermeta WHERE meta_key='unidad' and meta_value='".$_GET['unidad']."' ORDER BY meta_value ASC");
	}elseif ($result = search_results()){
		$list = "";
		$last = count($result) - 1;
		$i = 0;
		reset($result);
		$users = array();
		foreach ($result as $user){
			array_push($users, $user['ID']);
		}
	}else{
		$inc = 30;
		if (!$_GET['pag']){
			$i = 0;
		}else{
			$i = $inc*(int)$_GET['pag'];
		}
		$users = $wpdb->get_col("SELECT ID FROM $wpdb->users ORDER BY user_login ASC LIMIT $i,$inc");
	}
	$alt = "";
	$extra_data = new User_Extra_Data;
	foreach($users as $user_id){
		setup_userdata($user_id); ?>
		<?php if ($alt == "") { $alt = " alt"; } else { $alt = ""; } ?>
		<div class="contact<?php echo $alt; ?>">
			<span class="m-name"><a href="<?php echo $url.'/?id='.$user_id;  ?>"><?php echo $user_identity; ?></a></span>
			<span class="m-email"><?php echo $user_email; ?></span>
			<span class="m-mobile">
				<?php
					$area = get_usermeta($user_id, 'area');
					$unidad = 'unidad_'.$area;
					$unidad_extra = $extra_data->$unidad;
					echo '<a href="'.$url.'/?area='.$area.'" >'.$extra_data->area[$area].'</a> - '.'<a href="'.$url.'/?unidad='.get_usermeta($user_id, 'unidad').'" >'.$unidad_extra[get_usermeta($user_id, 'unidad')].'</a>';					
					
				?>
			</span>
			<hr/>
		</div>
	<?php	
	}
	?>
	<p>
	<?php if ($_GET['pag']){ ?>
		<a href="<?php echo $url.'/.?pag='; echo $_GET['pag']-1;?>">Página anterior</a>
	<?php }?>
	<?php if (isset($inc) && !(count($users)<$inc)) {?>
		<a href="<?php echo $url.'/.?pag='; echo $_GET['pag']+1;?>">Siguiente página</a>
	<?php }?>
	</p>
</div>
<?php } get_sidebar(); get_footer(); ?>
