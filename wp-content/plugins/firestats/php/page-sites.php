<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
?>
<div id="fs_sites_div">

<!-- Use table for base layout -->
<table><tr><td>

<div id="fs_sites_table_holder" class="fwrap">
	<h2><?php fs_e('Manage sites')?></h2>
	<div id="fs_sites_table">
		<?php echo fs_get_sites_manage_table()?>
	</div>
</div>

</td><td>

<div id="fs_sites_tab_help" class="fwrap">
	<h2><?php fs_e('Help');fs_create_wiki_help_link('MultipleSites', 800,800)?></h2>
	<b><?php fs_e('Warning, you can really mess things up from here, be careful!')?></b><br/>
	<br/>
	<?php fs_e('FireStats can collect statistics from multiple sites (on the same server).')?><br/>
	<ul>
		<li><?php fs_e('The site need to be registered in the sites table')?></li>
		<li><?php fs_e('The site should be configured to use its ID when reporting a hit, click on the help button next to the site you created for more information')?></li>
		<li>
			<?php 
				echo sprintf(fs_r("Click %s for more information"),
				sprintf("<a target='_blank' href='%s'>%s</a>",FS_WIKI."MultipleSites",fs_r('here')))?>
		</li>
	</ul>
</div>

</td></tr></table>

</div>
