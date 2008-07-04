<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
?>
<div class='<?php echo fs_lang_dir()?>'>
	
	<h3><?php fs_e('System information')?></h3>
	<?php fs_e('You can help by sending anonymous system information that will help make better decisions about new features.')?>
	<ul>
		<li><?php fs_e('The information will be sent anonymously, but a unique identifier will be sent to prevent duplicate entries from the same FireStats')?></li>
		<?php if (!defined('FS_SYSINFO_HIDE_CANCEL_INFO')){?>
		<li><?php fs_e('You can change this later from the Tools tab')?></li>
		<?php }?>
	</ul>
		
	<?php fs_e("Do you agree to send anonymous system information?")?>
	<div class='fs_bottom_panel' style='margin-top:25px'>
		<button id='fs_send_sysinfo_yes' class='button' onclick="saveSystemOptionValue('user_agreed_to_send_system_information','true' ,'boolean');closeParentWindow(this);">
			<?php fs_e("Sure, send system information")?>
		</button>
		<button id='fs_send_sysinfo_no'  class='button' onclick="saveSystemOptionValue('user_agreed_to_send_system_information','false','boolean');closeParentWindow(this);">
			<?php fs_e("Nope, don't send")?>
		</button>
	</div>
</div>
