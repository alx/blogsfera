<?php
/*
Plugin Name: Plugin Commander 
Plugin URI: http://firestats.cc/wiki/WPMUPluginCommander
Description: Plugin Commander is a plugin management plugin for WPMU
Version: 1.0.1
Author: Omry Yadan
Author URI: http://firefang.net/blog
License: GPL (see http://www.gnu.org/copyleft/gpl.html)

Instructions: copy into mu-plugins  
*/

add_action('admin_menu', 'pc_add_menu');
add_action('wpmu_new_blog','pc_new_blog');

define('PC_HOME','wpmu-admin.php');
define('PC_CMD_BASE',PC_HOME."?page=Plugin%20Commander");

define('PC_PLUGINS_HOME','edit.php');
define('PC_PLUGINS_CMD_BASE',PC_PLUGINS_HOME."?page=Plugins");

function pc_add_menu()
{
	if (is_site_admin())
	{
		add_submenu_page(PC_HOME, 'Plugin Commander', 'Plugin Commander', 8, 'Plugin Commander', 'pc_page');
	}

	if (strlen(get_site_option('pc_user_control_list')) > 0)
	{
		add_submenu_page(PC_PLUGINS_HOME,
						 'Plugins', 
						 'Plugins', 1, 
						 'Plugins', 
						 'pc_user_plugins_page');
	}
	
	//$file = substr(__FILE__,strlen(ABSPATH));
	//add_menu_page( "Plugin Commander","Plugin Commander", 8, $file);
}

function pc_user_plugins_page()
{
	pc_handle_plugins_cmd();
?>
<div class='wrap'>
<h2>Manage plugins</h2>
<table>
	<tr>
		<th>Name</th>
		<th>Description</th>
		<th>Action</th>
	</tr>
<?php
$plugins = get_plugins();
$user_control = explode(',',get_site_option('pc_user_control_list'));
$active_plugins = get_option('active_plugins');
foreach($plugins as $file=>$p)
{
	if(!in_array($file, $user_control)) continue;
?>
	<tr>
		<td><?php echo $p['Name']." ".$p['Version']?></td>
		<td><?php echo $p['Description']. " by ". $p['Author']?></td>
		<td>
		<?php 
			$checked = in_array($file, $active_plugins);
			if ($checked)
			{
				$cmd = "deactivate=$file";
				$text = "<span style='background-color:#00BFFF'>Deactivate</span>";
			}
			else
			{
				$cmd = "activate=$file";
				$text = "<span class='pc_off'>Activate</span>";
			}
			echo "<a href='".PC_PLUGINS_CMD_BASE."&$cmd'>$text</a>";
		?>
		</td>
	</tr>
<?php
}
echo "</table></div>";
}


function pc_page()
{
	pc_handle_command();
?>
<div class='wrap'>
<h2>Plugin Commander</h2>
<table>
	<tr>
		<th>Name</th>
		<th>Version</th>
		<th>Author</th>
		<th title='Automatically activate for new blogs'>Auto-activate</th>
		<th title='Users may activate/deactivate'>User control</th>
		<th>Mass activate</th>
		<th>Mass deactivate</th>
	</tr>
<?php

$plugins = get_plugins();
$auto_activate = explode(',',get_site_option('pc_auto_activate_list'));
$user_control = explode(',',get_site_option('pc_user_control_list'));
foreach($plugins as $file=>$p)
{
?>
	<tr>
		<td><?php echo $p['Name']?></td>
		<td><?php echo $p['Version']?></td>
		<td><?php echo $p['Author']?></td>
		<td>
		<?php 
			$checked = in_array($file, $auto_activate);
			if ($checked)
			{
				$cmd = "auto_activate_off=$file";
				$text = "<span style='background-color:#00BFFF'>Turn off</span>";
			}
			else
			{
				$cmd = "auto_activate_on=$file";
				$text = "<span class='pc_off'>Turn on</span>";
			}
			echo "<a href='".PC_CMD_BASE."&$cmd'>$text</a>";
		?>
		</td>
		<td>
		<?php 
			$checked = in_array($file, $user_control);
			if ($checked)
			{
				$cmd = "user_control_off=$file";
				$text = "<span style='background-color:#00BFFF'>Disallow</span>";
			}
			else
			{
				$cmd = "user_control_on=$file";
				$text = "<span class='pc_off'>Allow</span>";
			}
			echo "<a href='".PC_CMD_BASE."&$cmd'>$text</a>";
		?>
		</td>
		<td><?php echo "<a href='".PC_CMD_BASE."&mass_activate=$file'>Activate all</a>"?></td>
		<td><?php echo "<a href='".PC_CMD_BASE."&mass_deactivate=$file'>Deactivate all</a>"?></td>
	</tr>
<?php
}
?>
</table>
</div>
<?php
}

function pc_new_blog($new_blog_id)
{
	// a work around wpmu bug (http://trac.mu.wordpress.org/ticket/497)
	global $wpdb;
	if (!isset($wpdb->siteid)) $wpdb->siteid = 1;
	$auto_activate_list = get_site_option('pc_auto_activate_list');
	$auto_activate = explode(',',$auto_activate_list);
	foreach($auto_activate as $plugin)
	{
		pc_activate_plugin($new_blog_id, $plugin);
	}
}

function pc_activate_plugin($blog_id, $plugin)
{
	if (empty($plugin)) return;
	if (validate_file($plugin)) return;
	if (!file_exists(ABSPATH . PLUGINDIR . '/' . $plugin)) return;
	switch_to_blog($blog_id);
	$current = get_option('active_plugins');
	ob_start();
	include_once(ABSPATH . PLUGINDIR . '/' . $plugin);
	$current[] = $plugin;
	sort($current);
	update_option('active_plugins', $current);
	do_action('activate_' . $plugin);
	$res = ob_get_clean();
	if (!empty($res)) echo "Error activating $plugin for blog id=$blog_id: $res<br/>";
	restore_current_blog();
}

function pc_deactivate_plugin($blog_id, $plugin)
{
	if (empty($plugin)) return;
	if (validate_file($plugin)) return;
	if (!file_exists(ABSPATH . PLUGINDIR . '/' . $plugin)) return;

	switch_to_blog($blog_id);
	$current = get_option('active_plugins');
	array_splice($current, array_search($plugin, $current), 1 ); // Array-fu!
	update_option('active_plugins', $current);
	ob_start();
	do_action('deactivate_'.$plugin);
	$res = ob_get_clean();
	if (!empty($res)) echo "Error deactivating $plugin for blog id=$blog_id: $res<br/>";
	restore_current_blog();
}

function pc_mass_activate($plugin)
{
	global $wpdb;
	$res = $wpdb->get_results("select blog_id from wp_blogs");
	if ($res === false) 
	{
		echo "Failed to mass activate $plugin : error selecting blogs";
		return;
	}

	foreach($res as $r)
	{
		pc_activate_plugin($r->blog_id, $plugin);
	}
}

function pc_mass_deactivate($plugin)
{
	global $wpdb;
	$res = $wpdb->get_results("select blog_id from wp_blogs");
	if ($res === false) 
	{
		echo "Failed to mass deactivate $plugin : error selecting blogs";
		return;
	}

	foreach($res as $r)
	{
		pc_deactivate_plugin($r->blog_id, $plugin);
	}
}


function pc_handle_plugins_cmd()
{
	if (isset($_GET['activate']))
	{
		$plugin = $_GET['activate'];
		global $blog_id;
		pc_activate_plugin($blog_id, $plugin);
	}

	if (isset($_GET['deactivate']))
	{
		$plugin = $_GET['deactivate'];
		global $blog_id;
		pc_deactivate_plugin($blog_id, $plugin);
	}
}

function pc_handle_command()
{
	if (isset($_GET['auto_activate_on']))
	{
		$plugins = get_plugins();
		$auto_activate = pc_get_auto_activate_array();
		$plugin = $_GET['auto_activate_on'];
		$auto_activate[] = $plugin;
		update_site_option('pc_auto_activate_list',implode(',',array_unique($auto_activate)));
	}
	if (isset($_GET['auto_activate_off']))
	{
		$plugins = get_plugins();
		$auto_activate = pc_get_auto_activate_array();
		$plugin = $_GET['auto_activate_off'];
		array_splice($auto_activate, array_search($plugin, $auto_activate), 1);
		update_site_option('pc_auto_activate_list',implode(',',array_unique($auto_activate)));
	}
	if (isset($_GET['user_control_on']))
	{
		$plugins = get_plugins();
		$user_control = pc_user_control_array(); 
		$plugin = $_GET['user_control_on'];
		$user_control[] = $plugin;
		update_site_option('pc_user_control_list',implode(',',array_unique($user_control)));
	}
	if (isset($_GET['user_control_off']))
	{
		$plugins = get_plugins();
		$user_control = pc_user_control_array();
		$plugin = $_GET['user_control_off'];
		array_splice($user_control, array_search($plugin, $user_control), 1);
		update_site_option('pc_user_control_list',implode(',',array_unique($user_control)));
	}
	if (isset($_GET['mass_activate']))
	{
		$plugins = get_plugins();
		$plugin = $_GET['mass_activate'];
		pc_mass_activate($plugin);
	}
	if (isset($_GET['mass_deactivate']))
	{
		$plugins = get_plugins();
		$plugin = $_GET['mass_deactivate'];
		pc_mass_deactivate($plugin);
	}
}

function pc_get_auto_activate_array()
{
	$auto_activate = explode(',',get_site_option('pc_auto_activate_list'));
	if (empty($auto_activate)) $auto_activate = array();
	return $auto_activate;
}

function pc_user_control_array()
{
	$user_control = explode(',',get_site_option('pc_user_control_list'));
	if (empty($user_control)) $user_control = array();
	return $user_control;
}
?>
