<?php
error_reporting(E_ALL | (defined('E_STRICT')? E_STRICT : 0));
require_once('ip2c.php');

$ip = isset($_GET['ip']) ? $_GET['ip'] : $_SERVER['REMOTE_ADDR'];
$ip2c = new fs_ip2country();
$res = $ip2c->get_country($ip);
if ($res == false)
  echo "$ip => not found";
else
{
  $o2c = $res['id2'];
  $o3c = $res['id3'];
  $oname = $res['name'];
  echo "$ip => $o2c $o3c $oname";
}

?>
