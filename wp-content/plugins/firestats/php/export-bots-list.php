<?php 
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-sql.php');

$res = fs_get_bots();
if ($res !== false)
{
	header('Content-disposition: attachment; filename=botlist.txt');
	echo "# FireStats bots list\n";
	echo "# Generated at ".date("D M j G:i:s T Y")."\n";
	if ($res)
	{
		foreach($res as $r)
		{
			echo $r['wildcard']."\n";
		}
	}
}
else
{
	fs_r("Error exporting bots list");
}

?>
