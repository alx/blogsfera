<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');

$btyes = fs_r("Yes (Recommended)");
$btno = fs_r("No");
?>
<div class='<?php echo fs_lang_dir()?>'>
	<h3><?php fs_e('Compacting old data')?></h3>
	<?php fs_e("FireStats can tranform old data to a more compact form,")?><br/>
	<?php fs_e("This will reduce storage size and improve performance at the cost of losing some information which is not essential.")?><br/>
	<?php fs_e("it is highly recommended that you allow FireStats to do this automatically.")?><br/>
	<ul>
		<li><?php echo sprintf(fs_r("Click %s if you would like FireStats to automatically compact data older than %s days"),"<b>$btyes</b>",fs_get_archive_older_than())?>.</li>
		<li><?php echo sprintf(fs_r("Click %s if you want to compact data manually or change compacting options (From the Settings tab)"),"<b>$btno</b>")?>.</li>
	</ul>
	<div align='center' style='padding: 10px'>
		<button class='button' id='archive_manually' onclick='saveSystemOptionValue("archive_method","manual","string");closeParentWindow(this)'><?php echo $btno?></button>
		<span style='padding: 10px'/>
		<button class='button' id='archive_automatically' onclick='saveSystemOptionValue("archive_method","auto","string");closeParentWindow(this)'><?php echo $btyes?></button>
	</div>
</div>
