<?php
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/version-check.php');
require_once(dirname(__FILE__).'/html-utils.php');
require_once(dirname(__FILE__).'/layout.php');
require_once(dirname(__FILE__).'/auth.php');
?>
<div id="firestats">

<div class="fs_body width_margin <?php echo fs_lang_dir()?>">
<h1>
<?php
global $fs_hide_support_button;
if (!isset($fs_hide_support_button))
{
	?>
		<span class='normal_font' style='float:<?php H_END()?>;margin:10px;'>
		<button class="button" onclick="FS.openDonationWindow()">
			<?php fs_e('Support FireStats')?>
		</button>
		<br/>
		<?php
		$user = fs_get_current_user();
		if (isset($user->logged_in) && $user->logged_in)
		{
		?>
		<button class="button" onclick="sendRequest('action=logout')">
			<?php fs_e('Log out')?>
		</button>
		<?php 
		}?>
		</span>
	<?php
}
?>
<?php
$home = FS_HOMEPAGE;
echo "<a style='border-bottom: 0px' href='$home'><img alt='".fs_r('FireStats')."' src='".fs_url("img/firestats-header.png")."'/></a>";
echo '<span class="normal_font" style="padding-left:10px">';
echo sprintf("%s %s\n",FS_VERSION,(fs_is_demo() ? fs_r('Demo') : ''));
if (fs_is_admin())
{
	echo "<!-- Checking if there is a new FireStats version, if FireStats hangs refresh the page -->\n";flush();
	echo fs_get_latest_firestats_version_message()."\n";
	echo "<!-- Checking if there is a new IP2Country database, if FireStats hangs refresh the page -->\n";flush();
	echo '<span id="new_ip2c_db_notification">'.fs_get_latest_ip2c_db_version_message()."</span>";
}
echo '</span>';
?>
</h1>


<div id="feedback_div">
<button class="button" onclick="hideFeedback();"><?php fs_e('Hide');?></button>
<span id="feedback_zone"></span>
</div><!-- feedback_div -->

<div id="network_status"></div>
