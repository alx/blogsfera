<?php
require_once(dirname(__FILE__).'/../php/session.php');
$res = fs_resume_existing_session();
if ($res !== true) 
{
	echo 'Session initializaiton failed : '.$res;
	return;
} 
 
require_once(dirname(__FILE__).'/init.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title><?php fs_e('Import bots list')?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<link rel="stylesheet" href="<?php echo 'css/base.css';?>" type='text/css'/>
		<script	type="text/javascript" src='<?php echo fs_url('js/prototype.js')?>'></script>
		<script	type="text/javascript" src='<?php echo fs_url('js/firestats.js.php').fs_get_request_suffix()?>'></script>
	</head>
	<body id="firestats">
	<div class="fs_body width_margin <?php echo fs_lang_dir()?>">
	<h3><?php fs_e('Import bots list')?></h3>
	
<?php
if (!(isset($_FILES['bots_list']['tmp_name']) && is_uploaded_file($_FILES['bots_list']['tmp_name'])))
{
?>
		<!-- The data encoding type, enctype, MUST be specified as below -->
		<form enctype="multipart/form-data" action="bridge.php<?php echo fs_get_request_suffix("file_id=import_bots")?>" method="post">
			<!-- MAX_FILE_SIZE must precede the file input field -->
			<input type="hidden" name="MAX_FILE_SIZE" value="100000" />
			<!-- Name of input element determines name in $_FILES array -->
			<?php fs_e('Select bot-list file')?> <input name="bots_list" type="file" /><br/>
			<?php fs_e('Import type')?> :
			<select name="import_type">
				<option value="append"><?php fs_e('Add to bots list')?></option>
				<option value="replace"><?php fs_e('Replace bots list')?></option>
			</select><br/>
			<input type="submit" value="<?php fs_e("Send File")?>" /><br/>
		</form>	
<?php
}
else
{
	if (!is_uploaded_file($_FILES['bots_list']['tmp_name']))
	{
		echo "<span class='error'>Possible file upload attack</span><br/>";
		return;
	}
	
	$file = $_FILES['bots_list']['tmp_name'];
	$lines = file($file);
	if (trim($lines[0]) != '# FireStats bots list')
	{
		echo "<span class='notice'>".fs_r('Incorrect file format')."</span><br/>";
		echo "<a href='javascript:history.go(-1)'>Back</a>";
	}
	else
	{
		require_once(dirname(__FILE__).'/db-sql.php');
		$ok = true;
		$type = $_POST['import_type'];
		$remove_existing = $type == 'replace';
		$res = fs_botlist_import_array($lines, $remove_existing);
		if ($res != '')
		{
			echo "<span class='notice'>".sprintf(fs_r('Error importing bots list : %s'),$res)."</span><br/>";
			echo "<a href='javascript:history.go(-1)'>Back</a>";
		}
		else
		{
			echo fs_r("bots list imported successfully")."<br/>";
			// update botlist and num excluded
			?>
			<script language="javascript">
				window.close();
			</script>
			<?php
		}
	}
}
?>
	</div>
	</body>
</html>
