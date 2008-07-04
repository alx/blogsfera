<?php
/// Name: FireStats plugin for Gregarius
/// Author: Omry Yadan
/// Description: Enables FireStats to monitor access to Gregarius
/// Version: 1.0
/// Configuration: __firestats_config

// register the hooks
rss_set_hook('rss.plugins.bodystart','__firestats_hit');
rss_set_hook("rss.plugins.navelements", "__firestats_nav_button");

function __firestats_installed_in_gregarius()
{
	$path = dirname(__FILE__);
	return file_exists($path.'/php/db-hit.php');
}

function __get_firestats_path()
{
	if (!__firestats_installed_in_gregarius())
	{
		$path = rss_plugins_get_option('firestats.path');
		if ($path == null || $path == '')
		{
			echo 'You need to configure the FireStats plugin in the plugins screen<br/>';
			return false;
		}
		else
		if (!file_exists($path.'/php/db-hit.php'))
		{
			echo "FireStats was not detected at <b>$path</b>.<br/>";
			return false;
		}
		else
		{
			return $path;
		}
	}
	else
	{
		return dirname(__FILE__);
	}

}

$__path = __get_firestats_path();
if (!$__path) return;

require_once($__path.'/php/db-hit.php');

function __firestats_hit()
{
	$site_id = rss_plugins_get_option('firestats.id');
	if (empty($site_id)) $site_id = 0;
	fs_add_site_hit($site_id, null, false);
}

function __firestats_nav_button()
{
	if (__firestats_installed_in_gregarius())
	{
		$GLOBALS['rss']->nav->addNavItem('plugins/firestats/','FireStats');
	}
}

function __firestats_config()
{
	if(rss_plugins_is_submit())
	{
		$path = $_REQUEST['firestats_path'];
		if($path != '') 
		{
			if (file_exists($path.'/php/db-hit.php'))
			{
				rss_plugins_add_option('firestats.path',$path);
			}
			else
			{
				echo "<div>FireStats was not found in <b>$path</b></div>";
			}
		}
		else
		{
			echo '<div>You need to enter the path where FireStats is installed</div>';
		}


		$site_id = $_REQUEST['firestats_id'];
		if ($site_id == '') $site_id = "0";
		rss_plugins_add_option('firestats.id',$site_id);
	}
	$path = rss_plugins_get_option('firestats.path');
	$site_id = rss_plugins_get_option('firestats.id');
?>
FireStats path<br/>
example : /var/www/firestats<br/>
<input type="text" id="firestats_path" name="firestats_path" size="15" value="<?php echo $path?>"/><br/>
Site id<br/>
The ID of this Gregarius site in the FireStats sites table<br/>
<input type="text" id="firestats_id" name="firestats_id" size="15" value="<?php echo $site_id?>"/>
<?php
	
}

?>
