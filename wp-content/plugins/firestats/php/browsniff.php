<?php
/*
Browser Sniff
based on a wordpress plugin by Iman Nurchyo (http://priyadi.net/)
which is available at http://priyadi.net/archives/2005/03/29/wordpress-browser-detection-plugin/
*/

require_once(dirname(__FILE__).'/utils.php');

### specify width and height for icons here
$GLOBALS['fs_pri_width_height'] = "14";
$GLOBALS['fs_pri_image_url'] = fs_url('img/browsers');
$GLOBALS['fs_pri_image_path'] = dirname(__FILE__) . "/../img/browsers";

function fs_pri_print_images () {
	global $user_ID, $post, $comment;
	get_currentuserinfo();
	if (!$comment->comment_agent) {
		return;
	}
	echo fs_pri_browser_images($comment->comment_agent);
}

function fs_pri_print_browser ($before = '', $after = '', $image = false, $between = 'on') {
	global $user_ID, $post, $comment;
	get_currentuserinfo();
	if (!$comment->comment_agent) {
		return;
	}
	if (user_can_edit_post_comments($user_ID, $post->ID)) {
		$uastring = "<a href=\"#\" title=\"" .htmlspecialchars($comment->comment_agent). "\">*</a>";
	}
	$string = fs_pri_browser_string($comment->comment_agent, $image, $between);
	echo $before . $string . $uastring . $after;
}

function fs_pri_browser_images($ua)
{
	$browser_name= $browser_code= $browser_ver= $os_name= $os_code= $os_ver=$pda_name= $pda_code= $pda_ver= $image= $between=null;
	list(	$browser_name, 
			$browser_code, 
			$browser_ver, 
			$os_name, 
			$os_code, 
			$os_ver,
			$pda_name, 
			$pda_code, 
			$pda_ver ) = fs_pri_detect_browser($ua);

	$string = fs_pri_images_string($browser_name, $browser_code, $browser_ver, $os_name, $os_code, $os_ver,
		$pda_name, $pda_code, $pda_ver, $image, $between);
	if (!$string) {
		$string = "";
	}
	return $string;

}

function fs_pri_browser_string($ua, $image = false, $between = 'on') {
	list(  $browser_name, $browser_code, $browser_ver, $os_name, $os_code, $os_ver,
		$pda_name, $pda_code, $pda_ver ) = fs_pri_detect_browser($ua);
	$string = fs_pri_friendly_string($browser_name, $browser_code, $browser_ver, $os_name, $os_code, $os_ver,
		$pda_name, $pda_code, $pda_ver, $image, $between);
	if (!$string) {
		$string = "Unknown browser";
	}
	return $string;
}

function fs_pri_detect_browser ($ua) {
	$browser_name= $browser_code= $browser_ver= $os_name= $os_code= $os_ver= $pda_name= $pda_code= $pda_ver = null;
	$ua = preg_replace("/FunWebProducts/i", "", $ua);
	if (preg_match('#MovableType[ /]([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'MovableType';
		$browser_code = 'mt';
		$browser_ver = $matches[1];
	} elseif (preg_match('#WordPress[ /]([a-zA-Z0-9.]*)#i', $ua, $matches)) {
		$browser_name = 'WordPress';
		$browser_code = 'wp';
		$browser_ver = $matches[1];
	} elseif (preg_match('#typepad[ /]([a-zA-Z0-9.]*)#i', $ua, $matches)) {
		$browser_name = 'TypePad';
		$browser_code = 'typepad';
		$browser_ver = $matches[1];
	} elseif (preg_match('#drupal#i', $ua)) {
		$browser_name = 'Drupal';
		$browser_code = 'drupal';
		$browser_ver = count($matches) > 0 ? $matches[1] : "";
	} elseif (preg_match('#symbianos/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$os_name = "SymbianOS";
		$os_ver = $matches[1];
		$os_code = 'symbian';
	} elseif (preg_match('#avantbrowser.com#i', $ua)) {
		$browser_name = 'Avant Browser';
		$browser_code = 'avantbrowser';
	} elseif (preg_match('#(Camino|Chimera)[ /]([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Camino';
		$browser_code = 'camino';
		$browser_ver = $matches[2];
		$os_name = "Mac OS";
		$os_code = "macos";
		$os_ver = "X";
	} elseif (preg_match('#anonymouse#i', $ua, $matches)) {
		$browser_name = 'Anonymouse';
		$browser_code = 'anonymouse';
	} elseif (preg_match('#PHP#', $ua, $matches)) {
		$browser_name = 'PHP';
		$browser_code = 'php';
	} elseif (preg_match('#danger hiptop#i', $ua, $matches)) {
		$browser_name = 'Danger HipTop';
		$browser_code = 'danger';
	} elseif (preg_match('#w3m/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'W3M';
		$browser_ver = $matches[1];
	} elseif (preg_match('#Shiira[/]([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Shiira';
		$browser_code = 'shiira';
		$browser_ver = $matches[1];
		$os_name = "Mac OS";
		$os_code = "macos";
		$os_ver = "X";
	} elseif (preg_match('#Dillo[ /]([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Dillo';
		$browser_code = 'dillo';
		$browser_ver = $matches[1];
	} elseif (preg_match('#Epiphany/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Epiphany';
		$browser_code = 'epiphany';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
	} elseif (preg_match('#UP.Browser/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Openwave UP.Browser';
		$browser_code = 'openwave';
		$browser_ver = $matches[1];
	} elseif (preg_match('#DoCoMo/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'DoCoMo';
		$browser_code = 'docomo';
		$browser_ver = $matches[1];
		if ($browser_ver == '1.0') {
			preg_match('#DoCoMo/([a-zA-Z0-9.]+)/([a-zA-Z0-9.]+)#i', $ua, $matches);
			$browser_ver = $matches[2];
		} elseif ($browser_ver == '2.0') {
			preg_match('#DoCoMo/([a-zA-Z0-9.]+) ([a-zA-Z0-9.]+)#i', $ua, $matches);
			$browser_ver = $matches[2];
		}
	} elseif (preg_match('#(SeaMonkey)/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Mozilla SeaMonkey';
		$browser_code = 'seamonkey';
		$browser_ver = $matches[2];
		if (preg_match('/Windows/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
	} elseif (preg_match('#Kazehakase/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Kazehakase';
		$browser_code = 'kazehakase';
		$browser_ver = $matches[1];
		if (preg_match('/Windows/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
	} elseif (preg_match('#Flock/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Flock';
		$browser_code = 'flock';
		$browser_ver = $matches[1];
		if (preg_match('/Windows/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
	} elseif (preg_match('#(Firefox|Phoenix|Firebird|BonEcho|GranParadiso|Minefield|Iceweasel)/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Mozilla Firefox';
		$browser_code = 'firefox';
		$browser_ver = $matches[2];
		if (preg_match('/Windows/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
	} elseif (preg_match('#Minimo/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Minimo';
		$browser_code = 'mozilla';
		$browser_ver = $matches[1];
		if (preg_match('/Windows/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
	} elseif (preg_match('#MultiZilla/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'MultiZilla';
		$browser_code = 'mozilla';
		$browser_ver = $matches[1];
		if (preg_match('/Windows/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
	} elseif (preg_match('/PSP \(PlayStation Portable\)\; ([a-zA-Z0-9.]+)/', $ua, $matches)) {
		$pda_name = "Sony PSP";
		$pda_code = "sony-psp";
		$pda_ver = $matches[1];
	} elseif (preg_match('#Galeon/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Galeon';
		$browser_code = 'galeon';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
	} elseif (preg_match('#iCab/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'iCab';
		$browser_code = 'icab';
		$browser_ver = $matches[1];
		$os_name = "Mac OS";
		$os_code = "macos";
		if (preg_match('#Mac OS X#i', $ua)) {
			$os_ver = "X";
		}
	} elseif (preg_match('#K-Meleon/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'K-Meleon';
		$browser_code = 'kmeleon';
		$browser_ver = $matches[1];
		if (preg_match('/Windows/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
	} elseif (preg_match('#Lynx/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Lynx';
		$browser_code = 'lynx';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
	} elseif (preg_match('#Links \\(([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Links';
		$browser_code = 'lynx';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
	} elseif (preg_match('#ELinks[/ ]([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'ELinks';
		$browser_code = 'lynx';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
	} elseif (preg_match('#ELinks \\(([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'ELinks';
		$browser_code = 'lynx';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
	} elseif (preg_match('#Konqueror/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Konqueror';
		$browser_code = 'konqueror';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		if (!$os_name) {
			list($os_name, $os_code, $os_ver) = fs_pri_pda_detect_os($ua);
		}
	} elseif (preg_match('#NetPositive/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'NetPositive';
		$browser_code = 'netpositive';
		$browser_ver = $matches[1];
		$os_name = "BeOS";
		$os_code = "beos";
	} elseif (preg_match('#OmniWeb#i', $ua)) {
		$browser_name = 'OmniWeb';
		$browser_code = 'omniweb';
		$os_name = "Mac OS";
		$os_code = "macos";
		$os_ver = "X";
	} elseif (preg_match('#Safari/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Safari';
		$browser_code = 'safari';
		$browser_ver = $matches[1];
		if (preg_match('/Windows/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}		
	} elseif (preg_match('#opera mini#i', $ua)) {
		$browser_name = 'Opera Mini';
		$browser_code = 'opera';
		preg_match('#Opera/([a-zA-Z0-9.]+)#i', $ua, $matches);
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver) = fs_pri_pda_detect_os($ua);
	} elseif (preg_match('#Opera[ /]([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Opera';
		$browser_code = 'opera';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		if (!$os_name) {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
		if (!$os_name) {
			list($os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver) = fs_pri_pda_detect_os($ua);
		}
		if (!$os_name) {
			if (preg_match('/Wii/i', $ua)) {
				$os_name = "Nintendo Wii";
				$os_code = "nintendo-wii";
			}
		}
	} elseif (preg_match('#WebPro/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'WebPro';
		$browser_code = 'webpro';
		$browser_ver = $matches[1];
		$os_name = "PalmOS";
		$os_code = "palmos";
	} elseif (preg_match('#WebPro#i', $ua, $matches)) {
		$browser_name = 'WebPro';
		$browser_code = 'webpro';
		$os_name = "PalmOS";
		$os_code = "palmos";
	} elseif (preg_match('#Netfront/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Netfront';
		$browser_code = 'netfront';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver) = fs_pri_pda_detect_os($ua);
	} elseif (preg_match('#Xiino/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Xiino';
		$browser_code = 'xiino';
		$browser_ver = $matches[1];
	} elseif (preg_match('#Blackberry([0-9]+)#i', $ua, $matches)) {
		$pda_name = "Blackberry";
		$pda_code = "blackberry";
		$pda_ver = $matches[1];
	} elseif (preg_match('#Blackberry#i', $ua)) {
		$pda_name = "Blackberry";
		$pda_code = "blackberry";
	} elseif (preg_match('#SPV ([0-9a-zA-Z.]+)#i', $ua, $matches)) {
		$pda_name = "Orange SPV";
		$pda_code = "orange";
		$pda_ver = $matches[1];
	} elseif (preg_match('#LGE-([a-zA-Z0-9]+)#i', $ua, $matches)) {
		$pda_name = "LG";
		$pda_code = 'lg';
		$pda_ver = $matches[1];
	} elseif (preg_match('#MOT-([a-zA-Z0-9]+)#i', $ua, $matches)) {
		$pda_name = "Motorola";
		$pda_code = 'motorola';
		$pda_ver = $matches[1];
	} elseif (preg_match('#Nokia ?([0-9]+)#i', $ua, $matches)) {
		$pda_name = "Nokia";
		$pda_code = "nokia";
		$pda_ver = $matches[1];
	} elseif (preg_match('#NokiaN-Gage#i', $ua)) {
		$pda_name = "Nokia";
		$pda_code = "nokia";
		$pda_ver = "N-Gage";
	} elseif (preg_match('#Blazer[ /]?([a-zA-Z0-9.]*)#i', $ua, $matches)) {
		$browser_name = "Blazer";
		$browser_code = "blazer";
		$browser_ver = $matches[1];
		$os_name = "Palm OS";
		$os_code = "palm";
	} elseif (preg_match('#SIE-([a-zA-Z0-9]+)#i', $ua, $matches)) {
		$pda_name = "Siemens";
		$pda_code = "siemens";
		$pda_ver = $matches[1];
	} elseif (preg_match('#SEC-([a-zA-Z0-9]+)#i', $ua, $matches)) {
		$pda_name = "Samsung";
		$pda_code = "samsung";
		$pda_ver = $matches[1];
	} elseif (preg_match('#SAMSUNG-(S.H-[a-zA-Z0-9]+)#i', $ua, $matches)) {
		$pda_name = "Samsung";
		$pda_code = "samsung";
		$pda_ver = $matches[1];
	} elseif (preg_match('#SonyEricsson ?([a-zA-Z0-9]+)#i', $ua, $matches)) {
		$pda_name = "SonyEricsson";
		$pda_code = "sonyericsson";
		$pda_ver = $matches[1];
	} elseif (preg_match('#(j2me|midp)#i', $ua)) {
		$browser_name = "J2ME/MIDP Browser";
		$browser_code = "j2me";
	} elseif (preg_match('#MSIE ([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Internet Explorer';
		$browser_code = 'ie';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver) = fs_pri_windows_detect_os($ua);
	} elseif (preg_match('#Universe/([0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Universe';
		$browser_code = 'universe';
		$browser_ver = $matches[1];
		list($os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver) = fs_pri_pda_detect_os($ua);
	}elseif (preg_match('#Netscape[0-9]?/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Netscape';
		$browser_code = 'netscape';
		$browser_ver = $matches[1];
		if (preg_match('/Windows/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
	} elseif (preg_match('#^Mozilla/5.0#i', $ua) && preg_match('#rv:([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Mozilla';
		$browser_code = 'mozilla';
		$browser_ver = $matches[1];
		if (preg_match('/Windows/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
	} elseif (preg_match('#^Mozilla/([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$browser_name = 'Netscape Navigator';
		$browser_code = 'netscape';
		$browser_ver = $matches[1];
		if (preg_match('/Win/i', $ua)) {
			list($os_name, $os_code, $os_ver) = fs_pri_windows_detect_os($ua);
		} else {
			list($os_name, $os_code, $os_ver) = fs_pri_unix_detect_os($ua);
		}
	}
	/* vars:
		$browser_name
		$browser_code
		$browser_ver
		$os_name
		$os_code
		$os_ver
		$pda_name
		$pda_code
		$pda_ver
	*/
	return array(  $browser_name, $browser_code, $browser_ver, $os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver );
									
}

function fs_pri_get_image_url ($code, $alt) {
	global $fs_pri_image_url, $fs_pri_image_path, $fs_pri_width_height;
	$alt = htmlspecialchars($alt);
	$code = htmlspecialchars($code);
	if (file_exists("$fs_pri_image_path/$code.png")) {
		return "<img src='$fs_pri_image_url/$code.png' alt='$alt' title='$alt' width='$fs_pri_width_height' height='$fs_pri_width_height' class='fs_browsericon' /> ";
	} else {
		return "";
	}
}

function fs_pri_images_string (	$browser_name = '', $browser_code = '', $browser_ver = '',
				$os_name = '', $os_code = '', $os_ver = '',
				$pda_name= '', $pda_code = '', $pda_ver = '', $image = false, $between = 'on' )
{
	$out = '';
	$browser_name = htmlspecialchars($browser_name);
	$browser_code = htmlspecialchars($browser_code);
	$browser_ver = htmlspecialchars($browser_ver);
	$os_name = htmlspecialchars($os_name);
	$os_code = htmlspecialchars($os_code);
	$os_ver = htmlspecialchars($os_ver);
	$pda_name = htmlspecialchars($pda_name);
	$pda_code = htmlspecialchars($pda_code);
	$pda_ver = htmlspecialchars($pda_ver);
	$out .= fs_pri_get_image_url($pda_code, $pda_name." ".$pda_ver);
	$out .= fs_pri_get_image_url($os_code, $os_name." ".$os_ver);
	$out .= fs_pri_get_image_url($browser_code, $browser_name." ".$browser_ver);
	return $out;
}



function fs_pri_friendly_string (	$browser_name = '', $browser_code = '', $browser_ver = '',
				$os_name = '', $os_code = '', $os_ver = '',
				$pda_name= '', $pda_code = '', $pda_ver = '', $image = false, $between = 'on' )
{
	$out = '';
	$browser_name = htmlspecialchars($browser_name);
	$browser_code = htmlspecialchars($browser_code);
	$browser_ver = htmlspecialchars($browser_ver);
	$os_name = htmlspecialchars($os_name);
	$os_code = htmlspecialchars($os_code);
	$os_ver = htmlspecialchars($os_ver);
	$pda_name = htmlspecialchars($pda_name);
	$pda_code = htmlspecialchars($pda_code);
	$pda_ver = htmlspecialchars($pda_ver);
	$between = htmlspecialchars($between);
	if ($browser_name && $pda_name) {
		if ($image) $out .= fs_pri_get_image_url($browser_code, $browser_name);
		$out .= "$browser_name";
		if ($browser_ver) {
			$out .= " $browser_ver";
		}
		$out .= " $between ";
		if ($image) $out .= fs_pri_get_image_url($pda_code, $pda_name);
		$out .= " $pda_name";
		if ($pda_ver) {
			$out .= " $pda_ver";
		}
	} elseif ($browser_name && $os_name) {
		if ($image) $out .= fs_pri_get_image_url($browser_code, $browser_name);
		$out .= "$browser_name";
		if ($browser_ver) {
			$out .= " $browser_ver";
		}
		$out .= " $between ";
		if ($image) $out .= fs_pri_get_image_url($os_code, $os_name);
		$out .= " $os_name";
		if ($os_ver) {
			$out .= " $os_ver";
		}
	} elseif ($browser_name) {
		if ($image) $out .= fs_pri_get_image_url($browser_code, $browser_name);
		$out .= "$browser_name";
		if ($browser_ver) {
			$out .= " $browser_ver";
		}
	} elseif ($os_name) {
		if ($image) $out .= fs_pri_get_image_url($os_code, $os_name);
		$out .= "$os_name";
		if ($os_ver) {
			$out .= " $os_ver";
		}
	} elseif ($pda_name) {
		if ($image) $out .= fs_pri_get_image_url($pda_code, $pda_name);
		$out .= "$pda_name";
		if ($pda_ver) {
			$out .= " $pda_ver";
		}
	}
	return $out;
}

function fs_pri_windows_detect_os ($ua) {
	$os_name= $os_code= $os_ver= $pda_name= $pda_code= $pda_ver=null;

	if (preg_match('/Windows 95/i', $ua) || preg_match('/Win95/', $ua)) {
		$os_name = "Windows";
		$os_code = "windows";
		$os_ver = "95";
	} elseif (preg_match('/Windows NT 5.0/i', $ua) || preg_match('/Windows 2000/i', $ua)) {
		$os_name = "Windows";
		$os_code = "windows";
		$os_ver = "2000";
	#} elseif (preg_match('/Win 9x 4.90/i', $ua) && preg_match('/Windows 98/i', $ua)) {
	} elseif (preg_match('/Win 9x 4.90/i', $ua) || preg_match('/Windows ME/i', $ua)) {
		$os_name = "Windows";
		$os_code = "windows";
		$os_ver = "ME";
	} elseif (preg_match('/Windows.98/i', $ua) || preg_match('/Win98/i', $ua)) {
		$os_name = "Windows";
		$os_code = "windows";
		$os_ver = "98";
	} elseif (preg_match('/Windows NT 6.0/i', $ua)) {
		$os_name = "Windows";
		$os_code = "windows";
		$os_ver = "Vista";
	} elseif (preg_match('/Windows NT 5.1/i', $ua)) {
		$os_name = "Windows";
		$os_code = "windows";
		$os_ver = "XP";
	} elseif (preg_match('/Windows NT 5.2/i', $ua)) {
		$os_name = "Windows";
		$os_code = "windows";
		if (preg_match('/Win64/i', $ua)) {
			$os_ver = "XP 64 bit";
		} else {
			$os_ver = "Server 2003";
		}
	} elseif (preg_match('/Mac_PowerPC/i', $ua)) {
		$os_name = "Mac OS";
		$os_code = "macos";
	} elseif (preg_match('/Windows NT 4.0/i', $ua) || preg_match('/WinNT4.0/i', $ua)) {
		$os_name = "Windows";
		$os_code = "windows";
		$os_ver = "NT 4.0";
	} elseif (preg_match('/Windows NT/i', $ua) || preg_match('/WinNT/i', $ua)) {
		$os_name = "Windows";
		$os_code = "windows";
		$os_ver = "NT";
	} elseif (preg_match('/Windows CE/i', $ua)) {
		list($os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver) = fs_pri_pda_detect_os($ua);
		$os_name = "Windows";
		$os_code = "windows";
		$os_ver = "CE";
		if (preg_match('/PPC/i', $ua)) {
			$os_name = "Microsoft PocketPC";
			$os_code = "windows";
			$os_ver = '';
		}
		if (preg_match('/smartphone/i', $ua)) {
			$os_name = "Microsoft Smartphone";
			$os_code = "windows";
			$os_ver = '';
		}
	}
	return array($os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver);
}

function fs_pri_unix_detect_os ($ua) {
	$os_name = $os_ver = $os_code = null;
	if (preg_match('/Linux/i', $ua)) {
		$os_name = "Linux";
		$os_code = "linux";
		if (preg_match('#Debian#i', $ua)) {
			$os_code = "debian";
			$os_name = "Debian GNU/Linux";
		} elseif (preg_match('#Mandrake#i', $ua)) {
			$os_code = "mandrake";
			$os_name = "Mandrake Linux";
		} elseif (preg_match('#SuSE#i', $ua)) {
			$os_code = "suse";
			$os_name = "SuSE Linux";
		} elseif (preg_match('#Novell#i', $ua)) {
			$os_code = "novell";
			$os_name = "Novell Linux";
		} elseif (preg_match('#Ubuntu#i', $ua)) {
			$os_code = "ubuntu";
			$os_name = "Ubuntu Linux";
		} elseif (preg_match('#Red ?Hat#i', $ua)) {
			$os_code = "redhat";
			$os_name = "RedHat Linux";
		} elseif (preg_match('#Gentoo#i', $ua)) {
			$os_code = "gentoo";
			$os_name = "Gentoo Linux";
		} elseif (preg_match('#Fedora#i', $ua)) {
			$os_code = "fedora";
			$os_name = "Fedora Linux";
		} elseif (preg_match('#MEPIS#i', $ua)) {
			$os_name = "MEPIS Linux";
		} elseif (preg_match('#Knoppix#i', $ua)) {
			$os_name = "Knoppix Linux";
		} elseif (preg_match('#Slackware#i', $ua)) {
			$os_code = "slackware";
			$os_name = "Slackware Linux";
		} elseif (preg_match('#Xandros#i', $ua)) {
			$os_name = "Xandros Linux";
		} elseif (preg_match('#Kanotix#i', $ua)) {
			$os_name = "Kanotix Linux";
		} 
	} elseif (preg_match('/FreeBSD/i', $ua)) {
		$os_name = "FreeBSD";
		$os_code = "freebsd";
	} elseif (preg_match('/NetBSD/i', $ua)) {
		$os_name = "NetBSD";
		$os_code = "netbsd";
	} elseif (preg_match('/OpenBSD/i', $ua)) {
		$os_name = "OpenBSD";
		$os_code = "openbsd";
	} elseif (preg_match('/IRIX/i', $ua)) {
		$os_name = "SGI IRIX";
		$os_code = "sgi";
	} elseif (preg_match('/SunOS/i', $ua)) {
		$os_name = "Solaris";
		$os_code = "sun";
	} elseif (preg_match('/Mac OS X/i', $ua)) {
		$os_name = "Mac OS";
		$os_code = "macos";
		$os_ver = "X";
	} elseif (preg_match('/Macintosh/i', $ua)) {
		$os_name = "Mac OS";
		$os_code = "macos";
	} elseif (preg_match('/Unix/i', $ua)) {
		$os_name = "UNIX";
		$os_code = "unix";
	} 
	return array($os_name, $os_code, $os_ver);
}

function fs_pri_pda_detect_os ($ua) {
	$os_name=$os_code=$os_ver=$pda_name=$pda_code=$pda_ver=null;
	if (preg_match('#PalmOS#i', $ua)) {
		$os_name = "Palm OS";
		$os_code = "palm";
	} elseif (preg_match('#Windows CE#i', $ua)) {
		$os_name = "Windows CE";
		$os_code = "windows";
	} elseif (preg_match('#QtEmbedded#i', $ua)) {
		$os_name = "Qtopia";
		$os_code = "linux";
	} elseif (preg_match('#Zaurus#i', $ua)) {
		$os_name = "Linux";
		$os_code = "linux";
	} elseif (preg_match('#Symbian#i', $ua)) {
		$os_name = "Symbian OS";
		$os_code = "symbian";
	}

	if (preg_match('#PalmOS/sony/model#i', $ua)) {
		$pda_name = "Sony Clie";
		$pda_code = "sony";
	} elseif (preg_match('#Zaurus ([a-zA-Z0-9.]+)#i', $ua, $matches)) {
		$pda_name = "Sharp Zaurus " . $matches[1];
		$pda_code = "zaurus";
		$pda_ver = $matches[1];
	} elseif (preg_match('#Series ([0-9]+)#i', $ua, $matches)) {
		$pda_name = "Series";
		$pda_code = "nokia";
		$pda_ver = $matches[1];
	} elseif (preg_match('#Nokia ([0-9]+)#i', $ua, $matches)) {
		$pda_name = "Nokia";
		$pda_code = "nokia";
		$pda_ver = $matches[1];
	} elseif (preg_match('#SIE-([a-zA-Z0-9]+)#i', $ua, $matches)) {
		$pda_name = "Siemens";
		$pda_code = "siemens";
		$pda_ver = $matches[1];
	} elseif (preg_match('#dopod([a-zA-Z0-9]+)#i', $ua, $matches)) {
		$pda_name = "Dopod";
		$pda_code = "dopod";
		$pda_ver = $matches[1];
	} elseif (preg_match('#o2 xda ([a-zA-Z0-9 ]+);#i', $ua, $matches)) {
		$pda_name = "O2 XDA";
		$pda_code = "o2";
		$pda_ver = $matches[1];
	} elseif (preg_match('#SEC-([a-zA-Z0-9]+)#i', $ua, $matches)) {
		$pda_name = "Samsung";
		$pda_code = "samsung";
		$pda_ver = $matches[1];
	} elseif (preg_match('#SonyEricsson ?([a-zA-Z0-9]+)#i', $ua, $matches)) {
		$pda_name = "SonyEricsson";
		$pda_code = "sonyericsson";
		$pda_ver = $matches[1];
	}
	return array($os_name, $os_code, $os_ver, $pda_name, $pda_code, $pda_ver);
}


