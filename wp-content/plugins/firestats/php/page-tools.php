<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
?>
<br/>
<h2><?php fs_e('Tools') ?></h2>
<?php fs_e('This section contains various tools')?><br/>

<?php
fs_output_sysinfo_information();
echo "<br/>";
fs_create_checkbox("user_agreed_to_send_system_information", "<b>".fs_r("I agree to send system information")."</b>", false, true)
?>


<h3><?php fs_e('Diagnostics') ?></h3>
<?php fs_e('System diagnostics page') ?>: <button class="button" onclick="openWindow('<?php echo fs_url("bridge.php?file_id=system_test")?>',800,800)"><?php fs_e('Open')?></button>
<br/>
<br/>
<table border="1">
  <tr>
    <td><?php fs_e('PHP Version')?></td><td><?php echo phpversion()?></td>
  </tr>
  <tr>
    <td><?php fs_e('MySQL Version')?></td><td><?php echo fs_mysql_version()?></td>
  </tr>
  <tr>
    <td><?php fs_e('Memory limit')?></td><td><?php echo ini_get('memory_limit')?></td>
  </tr>
</table>



<h3><?php fs_e('Database cache') ?></h3>
<?php fs_e("The following section can recalculate different aspects of the database. you don't normally need to use it.")?><br/>
<table>
  <tr>
    <td><?php fs_e('Recalculate country codes')." : "?></td>
    <td><button class="button" id="rebuild_countries_button" onclick="FS.executeProcess('rebuild_countries')"><?php fs_e("Start")?></button></td>
    <td><span id="rebuild_countries_process_progress"></span></td>
  </tr>
  <tr>
    <td><?php fs_e('Rebuild database cache')." : "?></td>
    <td><button class="button" id="rebuild_cache_button" onclick="FS.executeProcess('rebuild_cache')"><?php echo fs_r("Start")?></button></td>
    <td><span id="rebuild_cache_process_progress"></span></td>
  </tr>
  <tr>
    <td><?php fs_e('Recalculate search engine terms')." : "?></td>
    <td><button class="button" id="recalculate_search_engine_terms_button" onclick="FS.executeProcess('recalculate_search_engine_terms')"><?php echo fs_r("Start")?></button></td>
    <td><span id="recalculate_search_engine_terms_process_progress"></span></td>
  </tr>
</table>
