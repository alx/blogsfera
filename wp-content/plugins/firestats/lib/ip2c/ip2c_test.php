<?php
require_once('ip2c.php');
set_time_limit(0);

$total = 0;
$num = 0;
$csvFile = "ip-to-country.csv";
$binFile = "ip-to-country.bin";
$ip2c = new fs_ip2country();

// this one caused problems before
$ip2c->get_country('10.0.0.1');
$ip2c->get_country('192.116.192.9');

$csv = fopen(dirname(__FILE__)."/$csvFile", "r");
$row = 0;
$count = 0;
while (($expected = fgetcsv($csv, 1000, ",")) !== FALSE) 
{
	$row++;
	if ($row % 10 != 0) continue; // only test every 10th row.
	$count++;
	$start = $expected[0];
	$end  = $expected[1];
	
	test($ip2c, $expected, $start);
	if ($end - $start > 1)
		test($ip2c, $expected, $start+1);
	if ($end < IP2C_MAX_INT * 2)
		test($ip2c, $expected, $end);
	if ($end - $start > 1 && ($end-1) < IP2C_MAX_INT * 2)
		test($ip2c, $expected, $end-1);
	test($ip2c, $expected, ($start+$end)/2);
	if ($count % 1000 == 0) echo ("Tested $count ranges<br/>");
	flush();
}
$t2 = $total * 1000;
echo "Tested $count ranges in $t2 ms <br/>";
echo "test passed, avg = " . ($total / $count) * 1000 . " ms";

fclose($csv);

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}


function test($ip2c, $expected, $ip)
{
	global $num;
	global $total;
	
	$ips = long2ip($ip);
//	echo "$ip => $ips <br/>";
	$now = microtime_float();
	$country = $ip2c->get_country($ips);
	$t = microtime_float() - $now;
	$total += $t;
	
	$num++;	

	if ($expected == false && $country == false) return;
	if ($expected == false && $country != false) die("Expected " . var_export($expected, true) . ", got " . var_export($country, true) . " ||| $ip $ips");
	if ($expected != false && $country == false) die("IP ($ip $ips) Not found, Expected :<br/>" . var_export($expected, true));
	
	$id2c = $expected[2];
	$id3c = $expected[3];
	$name = $expected[4];
	
	$o2c = $country['id2'];
	$o3c = $country['id3'];
	$oname = $country['name'];
	
	if ($id2c != $country['id2'] || $id3c != $country['id3'] || $name != $country['name'])
	{
		die("Expected :<br/>2c = $id2c, 3c = $id3c , name = $name<br/>got:<br/>2c = $o2c, 3c = $o3c , name = $oname :<br/> ||| $ip $ips");
	}
}

//$c = $ip2c->get_country("240.1.2.3");
//$passed = microtime() - $now;
//var_dump($c);
//echo "passed: $passed";

?>
