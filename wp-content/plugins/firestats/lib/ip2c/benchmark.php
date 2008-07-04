<?php

require_once('ip2c.php');
set_time_limit(0);
$ip2c = new ip2country();
$ips = array();
$len = 100000;
echo "Generating $len random IP addresses....";
flush();
for ($i = 0;$i<$len;$i++)
{
	$ips[$i] = mt_rand(0,255) . "." . mt_rand(0,255) . "." . mt_rand(0,255) . "." . mt_rand(0,255);
}

echo "Done<br/>Resolving addresses:<br/>";
$now = microtime_float();
$progress = $len / 20;   
for ($i = 0; $i < $len; $i++)
{
	if ($i % $progress == 0) 
	{
		echo ($i)." done<br/>";
		flush();	
	}
	$ip2c->get_country($ips[$i]);
}
echo "now " . microtime_float()."<br/>";
echo "before " . $now ."<br/>";
$t = microtime_float() - $now;
echo "Took " . $t . " for $len searches (".($len / $t) ." searches/sec)";


function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

?>