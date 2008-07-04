<?php
$db_valid = fs_db_valid();
if (!$db_valid)
{
	require_once(dirname(__FILE__).'/page-database.php');
	return;
}
else
{
	fs_maintain_usage_stats();

	// for demo site, monitor access to self
	if (fs_is_demo())
	{
		require_once(dirname(__FILE__).'/db-hit.php');
		fs_add_hit(null, false);
	}
}
?>
<div class="tabber" id="main_tab_id">
<div id="stats_page_id" class="tabbertab"
	title="<?php fs_e("Statistics")?>"><?php require(dirname(__FILE__).'/page-stats.php')?>
</div> <!-- stats_page_id -->

<div id="settings_page_id" class="tabbertab ajax_tab" title="<?php fs_e("Settings")?>">
	<!-- __js__: <?php echo fs_js_url('js/page-settings.js.php')?>-->
	<!-- __page__: <?php echo fs_js_url('php/page-settings.php')?>-->
</div><!-- settings_page_id -->
<?php
if (fs_in_wordpress())
{
?>
<div id="wordpress_settings_id" class="tabbertab ajax_tab" title="<?php fs_e('WordPress settings')?>">
	<!-- __js__: <?php echo fs_js_url('js/page-wordpress-settings.js.php')?>-->
	<!-- __page__: <?php echo fs_js_url('php/page-wordpress-settings.php')?>-->
</div><!-- wordpress_settings_id -->
<?php 
}
?>

<?php
if (fs_is_admin() || fs_is_demo())
{?>
<div id="database_page_id" class="tabbertab ajax_tab" title="<?php fs_e("Database")?>">
	<!-- __js__: <?php echo fs_js_url('js/page-database.js.php')?>-->
	<!-- __page__: <?php echo fs_js_url('php/page-database.php')?>-->
</div><!-- database_page_id -->

<?php if (!fs_is_hosted()){?>
<div id="users_id" class="tabbertab ajax_tab" title="<?php fs_e('Users management')?>">
	<!-- __js__: <?php echo fs_js_url('js/page-users.js.php')?>-->
	<!-- __css__: <?php echo fs_js_url('css/page-users.css.php')?>-->
	<!-- __page__: <?php echo fs_js_url('php/page-users.php')?>-->
</div>
<?php }?>

<div id="sites_id" class="tabbertab ajax_tab" title="<?php fs_e('Sites management')?>">
	<!-- __js__: <?php echo fs_js_url('js/page-sites.js.php')?>-->
	<!-- __css__: <?php echo fs_js_url('css/page-sites.css.php')?>-->
	<!-- __page__: <?php echo fs_js_url('php/page-sites.php')?>-->
</div>

<div id="tools_id" class="tabbertab ajax_tab" title="<?php fs_e('Tools')?>">
	<!-- __page__: <?php echo fs_js_url('php/page-tools.php')?>-->
</div>
<?php 
}
?>
</div><!-- tabber -->

<script type="text/javascript">
//<![CDATA[

var tabberOptions =
{
	'onClick': function(argsObj) 
	{	
		var t = argsObj.tabber; /* Tabber object */
		var i = argsObj.index; /* Which tab was clicked (0..n) */
		var div = this.tabs[i].div; /* The tab content div */
		
		if (div.className.indexOf('ajax_tab') != -1)
		{
			var children = $(div).childNodes;
			for (var i = 0; i < children.length; i++) 
			{
				if (children[i].nodeName == "#comment")
				{
					var command = children[i].nodeValue.trim();
					if (command.indexOf("__page__:") == 0)
					{
						var page = command.substring("__page__:".length);
						/* Display a loading message */
						div.innerHTML = "<p><?php fs_e('Loading...')?><\/p>";
						/* Fetch some html depending on which tab was clicked */
						var url = page;
						var pars = '';
						var myAjax = new Ajax.Updater(div, url, {method:'get',parameters:pars});
					}
					else
					if (command.indexOf("__css__:") == 0)
					{
						var css = command.substring("__css__:".length);
						FS.loadCSS(css);
					}
					else
					if (command.indexOf("__js__:") == 0)
					{
						var js = command.substring("__js__:".length);
						FS.loadJavaScript(js);
					}
				}
			}
		}
	},
	'onLoad': function(argsObj) 
	{
//		/* Load the first tab */
//		argsObj.index = 0;
//		this.onClick(argsObj);
	}
}

//]]>
</script>

<script type="text/javascript" src="<?php echo fs_url('lib/tabber/tabber-minimized.js')?>">
</script>

<?php 
if (fs_db_valid())
{
	fs_output_send_info_form();
?>

<script type='text/javascript'>
//<![CDATA[
	// this is done here instead of sending an updated page in the first place
	// to improve startup time.
	updateAllStats();
	toggleAutoRefresh();
	sendSilentRequest('action=getNextUserMessage');

	<?php 
	if (fs_is_admin())
	{
		if(fs_get_auto_bots_list_update() == 'true')
		{
			?>
			sendSilentRequest('action=updateBotsList&update=botlist_placeholder,num_excluded&user_initiated=false');
			<?php
		}
		if (fs_get_system_option('archive_method') == 'auto')
		{
			?>
			FS.archiveOldData();
			<?php
		}
		
		?>
		sendSilentRequest('action=handle_pending_maintanence');
		<?php
	}
	?>
//]]>
</script>
<?php }?>
