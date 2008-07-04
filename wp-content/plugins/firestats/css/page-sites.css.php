<?php 
header('Content-Type: text/css');
require_once(dirname(__FILE__).'/../php/layout.php');

?>

#fs_sites_div
{
    position: relative;
    top: 0; left: 0;
	width: 100%;
}

#fs_sites_table_holder
{
    width:550px;
}

#fs_sites_tab_help
{
    width:300px;
}


#fs_sites_table th
{
	font: bold 11px "Trebuchet MS", Verdana, Arial, Helvetica,sans-serif;
	color: #6D929B;
	border-left: 1px solid #C1DAD7;
	border-right: 1px solid #C1DAD7;
	border-bottom: 1px solid #C1DAD7;
	border-top: 1px solid #C1DAD7;
	letter-spacing: 2px;
	background: #CAE8EA;
}

#fs_sites_table td 
{
	border-right: 1px solid #C1DAD7;
	border-bottom: 1px solid #C1DAD7;
	background: #fff;
	/*padding: 6px 6px 6px 12px;*/
	color: #6D929B;
}