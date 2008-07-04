<?php
header('Content-type: text/html; charset=utf-8');
?>

<?php
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
?>
<?php
	if (!defined('FS_WORDPRESS_PLUGIN_VER') || FS_WORDPRESS_PLUGIN_VER != '1.4.4-stable')
	{
		echo sprintf("Incorrect version of %s detected, you need to update it (did you upgrade FireStats and forgot to copy %s to the WordPress plugins directory?)","firestats-wordpress.php","firestats-wordpress.php");
		return;
	}
?>
<div id="wordpress_config_area" class="configuration_area">
    <table class="config_table">
	<tr>
		<td class="config_cell" colspan="2">
			<h3><?php fs_e('Statistics widget')?></h3>
			<?php
			$href = null;
			global $wp_version;
			$newer_than_2_2 = (version_compare($wp_version,"2.2") == 1);
			if (class_exists('K2SBM')) // K9 Theme comes with its own widgets module.
			{
				$href = sprintf("<a href='themes.php?page=k2-sbm-modules'>%s</a>",fs_r('here'));
			}
			else
			if ($newer_than_2_2)
			{
				// so does WordPress >= 2.2
				if (file_exists(ABSPATH."wp-admin/widgets.php"))
				{
					$href = sprintf("<a href='widgets.php'>%s</a>",fs_r('here'));
				}
				else
				{
					$href = sprintf("<a href='themes.php?page=widgets/widgets.php'>%s</a>",fs_r('here'));
				}
			}

			if ($href != null)
			{			
				echo sprintf(fs_r('You can configure the sidebar widgets from %s'),$href);
			}
			else
			{
				echo  sprintf(fs_r("The statistics widget requires the %s plugin for optimal usage."),
						sprintf("<a href='%s'>%s</a>",
							"http://automattic.com/code/widgets/",
							fs_r("Widgets")));
				echo "<br/>";			 
				echo fs_r("You can also manually add the following code to your theme sidebar:")."<br/>";
				echo "<b>".htmlentities("<?php echo fs_get_stats_box();?>")."</b>";
			}
		?>
		</td>
	</tr>
	<tr>
		<td class="config_cell" width="50%">
			<h3><?php fs_e('Comments icons')?></h3>
			<?php
			$comment_flags = fs_get_local_option('firestats_add_comment_flag') == 'true' ? "checked" : "";
			?>
			<input type="checkbox" 
				onclick="saveLocalOption('enable_comment_flags','firestats_add_comment_flag','boolean')"
				id="enable_comment_flags" <?php echo $comment_flags?>>
			</input>
			<label for='enable_comment_flags'><?php fs_e('Add flag icon to comments')?></label><br/>		
			<?php
			$comment_browser_os = fs_get_local_option('firestats_add_comment_browser_os') == 'true' ? "checked" : "";
			?>
			<input type="checkbox"
			onclick="saveLocalOption('enable_comment_browser_os','firestats_add_comment_browser_os','boolean')"
			id="enable_comment_browser_os" <?php echo $comment_browser_os?>/>
			<label for='enable_comment_browser_os'><?php fs_e('Add browser and operating system icons to comments')?></label><br/>
		</td>
		<td class="config_cell" width="50%">
			<h3><?php fs_e('Blog footer')?></h3>
			<?php
				$add_footer = fs_get_local_option('firestats_show_footer') != 'false' ? "checked" : "";
			?>
			<input type="checkbox"
				onclick="saveLocalOption('show_footer','firestats_show_footer','boolean')"
			id="show_footer" <?php echo $add_footer?>/>
			<label for='show_footer'><?php fs_e('Add FireStats footer to blog')?></label><br/>

			<?php
				$add_footer_stats = fs_get_local_option('firestats_show_footer_stats') == 'true' ? "checked" : "";
			?>
			<input type="checkbox"
			onclick="saveLocalOption('show_footer_stats','firestats_show_footer_stats','boolean')"
			id="show_footer_stats" <?php echo $add_footer_stats?>/>
			<label for='show_footer_stats'><?php fs_e('Show statistics in footer')?></label><br/>
		</td>
	</tr>
	<?php if(fs_is_admin()){?>
	<tr>
		<td class="config_cell" width="300">
			<h3><?php fs_e('Excluded users')?></h3>
			<?php fs_e("WordPress users selected here will not be counted in the statistics")?><br/>
			<div class='scroll' id="exclude_users_placeholder" style='height: 200px'><?php echo fs_get_excluded_users_list()?></div>
		</td>
		<td class="config_cell">
			<h3><?php fs_e('Permissions')?></h3>
			<?php fs_e("Select the minimum user role that can access FireStats (Only administrators can manage FireStats)")?><br/>
			<?php
			$selected = fs_get_local_option('firestats_min_view_security_level',3);
			$arr = array();
			$arr[] = fs_mkPair(5, fs_r('Administrator'));
			$arr[] = fs_mkPair(4, fs_r('Editor'));
			$arr[] = fs_mkPair(3, fs_r('Author'));
			$arr[] = fs_mkPair(2, fs_r('Contributor'));
			$arr[] = fs_mkPair(1, fs_r('Subscriber'));
			$onchange = "saveLocalOption('wordpress_view_security_level','firestats_min_view_security_level','string')";
			echo fs_create_dropbox($arr,$selected,'wordpress_view_security_level',$onchange);
			?>
		</td>
	</tr>
	<tr>
		<td class="config_cell" colspan="2">
			<h3><?php fs_e('Advanced')?></h3>
			<?php fs_e('WordPress site ID, every hit From this blog is recorded with this as the source Site ID')?><br />
			<?php fs_e("This should be the same ID as the Site ID in the sites table. you don't normally need to change this.")?><br />
			<input type="text"
				onkeypress="return trapEnter(event,'saveWpSiteID();');"
				id="wp_site_id" style="width:120px"
				value="<?php echo fs_get_local_option('firestats_site_id','')?>" />
			<button class="button" onclick="saveWpSiteID()"><?php fs_e('Save')?></button>
		</td>
	</tr>
	<tr>
		<td class="config_cell" colspan="2">
			<?php fs_e("This will update the titles of the blog posts inside FireStats, you don't normally need to use it")?>
			<button class="button" onclick="sendRequest('action=update_wordpress_titles')"><?php fs_e('Update')?></button>
		</td>
	</tr>
	<?php }?>
</table>
</div>
