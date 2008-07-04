<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
require_once(FS_ABS_PATH.'/php/version-check.php');
?>
<div id="configuration_area" class="configuration_area">
	<table class="config_table">
		<tr>
			<td class="config_cell" colspan="2">
				<h3><?php fs_e('Options')?></h3>
				<?php fs_e('Select language');
					$langs = fs_get_languages_list();
				?>:
				<select id="language_code">
					<?php echo $langs?>
				</select>
				<button class="button" onclick="changeLanguage()"><?php fs_e('Save');?></button>
				<?php echo sprintf("<a href=\"%s\" target='_blank'>%s</a>",
									FS_HOMEPAGE_TRANSLATE, fs_r('How to translate to a new language'))?><br/>
				<?php
				if (fs_mysql_newer_than("4.1.13"))
				{
				?>
				<?php fs_e('Select your time zone')?>
				<select id='firestats_user_timezone'>
					<?php echo fs_get_timezone_list()?>
				</select>
				<button class="button" onclick="changeTimeZone()"><?php fs_e('Save');?></button><br/>
				<?php
				}
				else
				{
					echo "<br/>";
					echo "<b>".sprintf(fs_r('Time zone selection requires %s or newer'), "Mysql 4.1.13")."</b>";
				}
				?>
				<br/>
				<?php fs_e('Select WHOIS provider')?>
				<select id="whois_providers">
					<?php echo fs_get_whois_options()?>
				</select>
				<button class="button" 
					onclick="saveOption('whois_providers','whois_provider','string','records_table')">
					<?php fs_e('Save');?>
				</button>
				<?php fs_create_wiki_help_link('WhoisProviders',800,600);?>
		<br/>
			</td>
		</tr>
	<?php if (fs_is_admin() || fs_is_demo()){?>
	<tr>
		<td class="config_cell" colspan="2">
		
		<h3><?php fs_e('Compact old data')?>
			<?php fs_create_wiki_help_link('ArchiveOldData')?>
		</h3>
		<?php if (fs_mysql_newer_than("4.1.14")) {?>
		<?php
			$method_dropbox= fs_get_archive_method_dropbox();
			$num_dropbox= fs_get_archive_dropbox();
			
		?>
		<div style="padding-left:10px;padding-right:10px">
			<?php 
			echo sprintf(fs_r('%s compact data older than %s'),$method_dropbox,$num_dropbox)?>
		&nbsp;&nbsp;&nbsp;
		<button class="button" id="fs_archive_button" onclick="toggleArchiveOldData()">
			<?php fs_e('Compact now')?></button>
		<div style="padding-top:10px;padding-left:10px;padding-right:10px;">
		<span id="fs_archive_status"><?php echo sprintf(fs_r("%s days can be compacted, database size %s"),fs_get_num_old_days(), sprintf("%.1f MB",fs_get_database_size()/(1024*1024)))?></span>
		</div>
		<?php 
		}else
		{
		echo "<b>".fs_r('MySQL 4.1.14 or newer is required for data compacting support')."</b>";
		}?>
		</div>
		</td>
	</tr>
	<tr>
		<td class="config_cell" colspan="2">
			<h3><?php fs_e('Automatic version check')?></h3>
			<?php
				$msg = fs_r('Automatically check if there is a new version of FireStats (recommended)');
				fs_create_checkbox('firestats_version_check_enabled',$msg,'true',true);
			?>
		</td>
	</tr>
		<tr>
			<td class="config_cell" colspan="2">
				<h3><?php fs_e('IP-to-country database')?></h3>
				<ul>
				<li><?php echo sprintf(fs_r('IP-to-country database version : %s'),'<b id="ip2c_database_version">'.fs_get_current_ip2c_db_version().'</b>')?></li>
				<li>
				<?php
					$msg = fs_r('Automatically check if there is a new version of IP-to-country database');
					fs_create_checkbox('ip-to-country-db_version_check_enabled',$msg,'true',true);
				?>
				</li>
				<li><?php fs_e('Update IP-to-country database now (only if needed)')?>
					<button class="button" onclick="sendRequest('action=updateIP2CountryDB')">
						<?php fs_e('Update');?>
					</button>
				</li>
				</ul>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<h3><?php fs_e('Exclude hits')?></h3>
			</td>
		</tr>
		<tr>
			<td colspan="2" class="config_cell">
				<ul>
					<li>
					<?php fs_create_checkbox('save_excluded_records',fs_r('Save excluded records (not recommended)'))?>
					</li>
					<li>
						<?php fs_e('Purge excluded records stored in the database')?>
						(<b id="num_excluded"><?php echo fs_get_num_excluded()?></b>)
						<button class="button" onclick="sendRequest('action=purgeExcludedHits')">
							<?php fs_e('Purge');?>
						</button>
					</li>
				</ul>
			</td>
		</tr>
		<tr> <!-- TODO : move style stuff to CSS -->
			<td class="config_cell" width="300">
				<table>
					<thead>
						<tr><td><h3><?php fs_e('Bots list')?></h3></td></tr>
					</thead>
					<tr>
						<td>
							<input type="text" onkeypress="return trapEnter(event,'addBot();');" 
								id="bot_wildcard" style="width:110px"/>
							<button class="button" onclick="addBot()"><?php fs_e('Add')?></button>
							<button class="button" onclick="removeBot()"><?php fs_e('Remove')?></button>
							<?php fs_cfg_button('more_bots_options')?>
						</td>
					</tr>
					<tr>
						<td>
							<span id="more_bots_options" class="normal_font hidden">
								<?php
									$auto_bots_list_update = fs_get_auto_bots_list_update();
									$auto_bots_list_update = $auto_bots_list_update == 'true' ? "checked=\"checked\"" : "";
								?>
								<input type="checkbox" 
									onclick="saveOption('auto_bots_list_update','auto_bots_list_update','boolean')"
									id="auto_bots_list_update" <?php echo $auto_bots_list_update?>/>
								<?php fs_e('Automatic update')?><br/>
								<button class="button" 
									onclick="sendRequest('action=updateBotsList&amp;update=botlist_placeholder,num_excluded')">
									<?php fs_e('Update now')?>
								</button>
								<button class="button" onclick="openImportBots()"> <?php fs_e('Import')?> </button>
								<button class="button" 
									onclick="window.location.href='<?php echo fs_url('php/export-bots-list.php')?>'">
									<?php fs_e('Export')?>
								</button>
							</span>
						</td>

					</tr>
					<tr>
						<td>
							<div id="botlist_placeholder"><?php echo fs_get_bot_list()?></div>
						</td>
					</tr>
				</table>
			</td>
			<td class="config_cell" width="300">
				<table>
					<thead>
						<tr><td><h3><?php fs_e('Excluded IPs')?></h3></td></tr>
					</thead>
					<tr>
						<td>
							<input type="text" onkeypress="return trapEnter(event,'addExcludedIP();');" id="excluded_ip_text" style="width:120px"/>
							<button class="button" onclick="addExcludedIP()"><?php fs_e('Add')?></button>
							<button class="button" onclick="removeExcludedIP()"><?php fs_e('Remove')?></button>
						</td>
					</tr>
					<tr>
						<td>
							<div id="exclude_ip_placeholder"><?php echo fs_get_excluded_ips_list()?></div>
						</td>
					</tr>
				</table>
			</td>
	</tr>
	<?php }?>
	</table> <!-- config_table -->
</div> <!-- configuration area -->
<?php
function fs_get_archive_method_dropbox()
{
	$automatically = fs_r('Automatically');
	$manually = fs_r('Manually');
	$select = fs_r('Please select');
	$n = fs_get_system_option('archive_method');
	$res = "<select id='archive_method' onchange=\"saveSystemOption('archive_method','archive_method','string')\">";
	if ($n == null)
	{
		$res .= "<option value='ask' selected='selected'>$select</option>";
	}
	$res .= "<option value='auto'".('auto' == $n ? " selected='selected'" : "").">$automatically</option>";
	$res .= "<option value='manual'".('manual' == $n ? " selected='selected'" : "").">$manually</option>";
	$res .= "</select>";
    return $res;			
}
	
function fs_get_archive_dropbox()
{
	$selected = fs_get_archive_older_than();
	$arr = array();
	$arr[] = fs_mkPair(30, fs_r('One month'));
	$arr[] = fs_mkPair(60, fs_r('Two months'));
	$arr[] = fs_mkPair(90, fs_r('Three months'));
	$arr[] = fs_mkPair(180, fs_r('Half a year'));
	$arr[] = fs_mkPair(365, fs_r('One year'));
	$arr[] = fs_mkPair(365*2, fs_r('Two years'));
	$onchange = "saveSystemOption('archive_older_than','archive_older_than','positive_num','fs_archive_status')";
	return fs_create_dropbox($arr,$selected,'archive_older_than',$onchange);
}
?>
