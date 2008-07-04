<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
?>
<div class='<?php echo fs_lang_dir()?>'>
<h3><?php fs_e('Support FireStats')?></h3>
<?php fs_e("Even though FireStats is free, it takes a lot of time and hard work to develop and maintain.")?><br/>
<?php fs_e("If you like FireStats and would like to show your support for the hard work I put into it, You can make a small Donation. Even a $5 donation would be greatly appreciated.")?><br/>
<ul>
	<li><button class='button' onclick='openWindow("<?php echo FS_WIKI."Donate"?>",700,700);closeParentWindow(this);'><?php fs_e("Yeah, I want to help")?></button></li>
	<li><button class='button' onclick='saveOptionValue("donation_status","no" ,"string");closeParentWindow(this);'><?php fs_e("Nah, go away")?></button></li>
	<li><button class='button' onclick='saveOptionValue("donation_status","later" ,"string");closeParentWindow(this);'><?php fs_e("Maybe later")?></button></li>
	<li><button class='button' onclick='saveOptionValue("donation_status","donated" ,"string");closeParentWindow(this);'><?php fs_e("Already donated")?></button></li>
</ul>
</div>
