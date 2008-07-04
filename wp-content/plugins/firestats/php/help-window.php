<?php 
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-sql.php');
require_once(dirname(__FILE__).'/html-utils.php');

?>
<html>
	<head>
		<link rel="stylesheet" href="<?php echo '../css/base.css';?>" type='text/css'/>
	</head>
	<body id="firestats">
    <div class="fs_body width_margin <?php echo fs_lang_dir()?>">

        <?php
   			$type = $_GET['TYPE'];
			$id = $_GET['SITE_ID'];
			if (is_numeric($type) && is_numeric($id))
			{
				$help = fs_get_help($id, $type);
				echo $help;
			}
			else
			{
				echo "Invalid parameters"; // not translated
			}
		?>
	</div>
	</body>
</html>

<?php 
function fs_get_help($id, $type)
{
	$help = fs_r("Unknown site type");
	$site = fs_get_site($id);
	switch($type)
	{
		case FS_SITE_TYPE_GENERIC:
			$help = fs_get_generic_help($site);
			break;
		case FS_SITE_TYPE_WORDPRESS:
			$help = fs_get_wordpress_help($site);
			break;
		case FS_SITE_TYPE_DJANGO:
			$help = fs_get_django_help($site);
			break;
		case FS_SITE_TYPE_DRUPAL:
			$help = fs_get_drupal_help($site);
			break;
		case FS_SITE_TYPE_GREGARIUS:
			$help = fs_get_gregarius_help($site);
			break;
		case FS_SITE_TYPE_JOOMLA:
			$help = fs_get_joomla_help($site);
			break;
		case FS_SITE_TYPE_MEDIAWIKI:
			$help = fs_get_mediawiki_help($site);
			break;
		case FS_SITE_TYPE_TRAC:
			$help = fs_get_trac_help($site);
			break;
		case FS_SITE_TYPE_GALLERY2:
			$help = fs_get_gallery2_help($site);
			break;
	}
	return $help;

}

function fs_get_generic_help($site)
{
	$id = $site->id;
	$firestats_dir = dirname(dirname(__FILE__));
	$hit_me = htmlentities(
"   <?php 
      include('".$firestats_dir."/php/db-hit.php');
      fs_add_site_hit($id);
   ?>");

$res = "<h3>".fs_r('Collect hits from PHP pages')."</h3>";
$res .= fs_r('To allow FireStats to track access to your pages, you need to add the following line inside the head tag of each of the pages you want to monitor (the page must be a php page).');
	$res .= '<br/><br/>';

	$res .= "<div class='ltr'><b>".$hit_me."</b></div>";
	$res .= '<br/><br/>';

	$res .= fs_r('A typical page header will look like this:').'<br/>';
	$res .="<div class='ltr'>";
	$res .= ("<pre>
&lt;html&gt;
&lt;head&gt;
<b>$hit_me</b>
    ...
&lt;/head&gt;
...
&lt;/html&gt;</pre>");

	$res .= "</div>";
	return $res;
}

function fs_get_wordpress_help($site)
{
	$res = fs_help_title(fs_r("WordPress"));
	$res .= fs_r("The WordPress plugin registers itself automatically when it's activated").".<br/>";
	return $res;
}

function fs_get_mediawiki_help($site)
{
	$link = sprintf("<a href='%s'>%s</a>",FS_WIKI."MediaWiki",fs_r("plugin"));
	$file = "<b>firestats-mediawiki.php</b>";
	$conf = fs_get_basic_conf($site);
	return fs_get_basic_help($site,$link, $file,$conf);
}

function fs_get_joomla_help($site)
{
	$link = sprintf("<a href='%s'>%s</a>",FS_WIKI."Joomla",fs_r("plugin"));
	$file = "<b>firestats-joomla.php</b>";
	$conf = fs_get_basic_conf($site);
	return fs_get_basic_help($site,$link, $file,$conf);
}

function fs_get_drupal_help($site)
{
	$link = sprintf("<a href='%s'>%s</a>",FS_WIKI."Drupal",fs_r("module"));
	return fs_get_advanced_help($site, $link);
}

function fs_get_gallery2_help($site)
{
	$link = sprintf("<a href='%s'>%s</a>",FS_WIKI."Gallery2",fs_r("module"));
	return fs_get_advanced_help($site, $link);
}

function fs_get_gregarius_help($site)
{
	$link = sprintf("<a href='%s'>%s</a>",FS_WIKI."Gregarius",fs_r("plugin"));
	return fs_get_advanced_help($site, $link);
}

function fs_get_django_help($site)
{
	$link = sprintf("<a href='%s'>%s</a>",FS_WIKI."Django",fs_r("plugin"));
	$file = "<b>settings.py</b>";
	$id = $site->id;
	$firestats_dir = dirname(dirname(__FILE__));
	$conf = "<br/><br/>
<b>firestats_directory='$firestats_dir'<br/>
firestats_site_id='$id'</b>";
	return fs_get_basic_help($site,$link, $file,$conf);
}

function fs_get_trac_help($site)
{
	$link = sprintf("<a href='%s'>%s</a>",FS_WIKI."trac",fs_r("plugin"));
	$file = "<b>trac.ini</b>";
	$id = $site->id;
	$firestats_dir = dirname(dirname(__FILE__));
	$conf = "<b><br/><br/>[components]<br/>
firestats.* = enabled<br/><br/>
[firestats]<br/>
firestats_directory=$firestats_dir<br/>
firestats_site_id=$id</b>";
	return fs_get_basic_help($site,$link, $file,$conf);
}

function fs_unsupported()
{
	return "Multi site support is currently unavailable for this platform";
}

function fs_help_title($name)
{
	return "<h3>".sprintf(fs_r('Collect hits from a %s site'),$name)."</h3>";

}

function fs_get_basic_conf($site)
{
	$id = $site->id;
	$firestats_dir = dirname(dirname(__FILE__));
	$dirdef = htmlentities("define('FS_PATH','$firestats_dir');");
	$sitedef = htmlentities("define('FS_SITE_ID',$id);");
	return "<br/><br/><b>$dirdef</b><br/><b>$sitedef</b><br/><br/>";
}

function fs_get_advanced_help($site, $link)
{
	$id = $site->id;
	$dir = dirname(dirname(__FILE__));
	$type = $site->type;
	$typeStr = fs_get_site_type_str($type);
	$res = fs_help_title($typeStr);
	$res .= fs_get_help_str($typeStr, $link);
	$res .= ", ".sprintf(fs_r("and then configure it in the administration menu with the path %s and the ID %s"),"<b>$dir</b>","<b>$id</b>");
	return $res;
}

function fs_get_basic_help($site, $link, $file, $conf)
{
	$type = $site->type;
	$typeStr = fs_get_site_type_str($type);
	$res = fs_help_title($typeStr);
	$res .= fs_get_help_str($typeStr, $link);
	$res .= ", ".sprintf(fs_r("and then edit the lines in %s to contain this:"),	$file);
	$res .= "<div class='ltr'>".$conf."</div>";
	return $res;
}

function fs_get_help_str($typeStr,$link)
{
	return sprintf(fs_r("To collect statistics from your %s site your need to install the %s"),$typeStr,$link);
}
?>
