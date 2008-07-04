<?php
if (!isset($wpdb)) {
	require('../../../wp-blog-header.php');
}
define('wordspew', 'wordspew/wordspew');
if(function_exists('load_plugin_textdomain')) load_plugin_textdomain(wordspew);

$id	=  isset($_GET['id']) ? $_GET['id'] : "";
$jal_wp_url = get_bloginfo('wpurl');

function rss_feed() {
global $wpdb, $table_prefix, $jal_wp_url;
@mysql_query("SET CHARACTER SET 'utf8'");
@mysql_query("SET NAMES utf8");

$events = $wpdb->get_results("SELECT * FROM ".$table_prefix."liveshoutbox ORDER BY id DESC");
$jal_first_time = true;
header('Content-Type: text/xml; charset=' . get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?>
'; ?>
<!-- generator="wordpress/<?php bloginfo_rss('version') ?>" -->
<rss version="2.0" 
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/">
	<channel>
		<title><?php _e('Wordspew-RSS-Feed for:', wordspew);?> <?php bloginfo_rss('name'); ?></title>
		<link><?php bloginfo_rss('url') ?></link>
		<description><?php bloginfo_rss("description") ?></description>
		<generator>http://wordpress.org/?v=<?php bloginfo_rss('version'); ?></generator>
<?php foreach ($events as $event) {
		if ($jal_first_time == true) { ?>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', gmdate( 'Y-m-d H:i:s', $event->time ), false); ?></pubDate>
		<language><?php echo get_option('rss_language'); ?></language>
<?php $jal_first_time = false; } ?>
		<item>
			<title><?php echo $event->name.' ('.mysql2date('D, d M Y H:i:s', date('Y-m-d H:i:s',$event->time)).')'; ?></title>
			<link><?php echo $jal_wp_url;?>/wp-content/plugins/wordspew/wordspew-rss.php?id=<?php echo $event->id; ?></link>
			<category>Shoutbox</category>
			<guid isPermaLink="false"><?php echo $jal_wp_url;?>/wp-content/plugins/wordspew/wordspew-rss.php?id=<?php echo $event->id;?></guid>
			<description><![CDATA[IP : <?php echo $event->ipaddr; ?><br/><?php echo $event->text; ?>]]></description>
			<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', gmdate( 'Y-m-d H:i:s', $event->time ), false); ?></pubDate>
		</item>
<?php }	?>
	</channel>
</rss> 
<?php }
function jal_getRSS ($ID) {
global $wpdb, $table_prefix, $jal_wp_url;

	@mysql_query("SET CHARACTER SET 'utf8'");
	@mysql_query("SET NAMES utf8");

	$XHTML=get_option('shoutbox_XHTML');
	$html="";
	$results = $wpdb->get_results("SELECT * FROM ".$table_prefix."liveshoutbox WHERE id = ".intval($ID));

	foreach( $results as $r ) {
		$target="";
		if (strpos($r->text, $jal_wp_url)===false && $XHTML==0) $target=' target="_blank"';
		$theLink=__("link",wordspew); $theMail=__("email",wordspew);
		$r->text = preg_replace("`(http|ftp)+(s)?:(//)((\w|\.|\-|_)+)(/)?(\S+)?`i", "<a href=\"\\0\"$target>&laquo;$theLink&raquo;</a>", $r->text);
		$r->text = preg_replace("`([-_a-z0-9]+(\.[-_a-z0-9]+)*@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6})`i","<a href=\"mailto:\\1\">&laquo;$theMail&raquo;</a>", $r->text); 
		$url = (empty($r->url) && $r->url = "http://") ? $r->name : '<a href="'.$r->url.'"'.$target.'>'.$r->name.'</a>';
		$html.= '<div>'.stripslashes($url).' <small>(' .mysql2date('D, d M Y H:i:s', date( 'Y-m-d H:i:s', $r->time )).') - IP: '.$r->ipaddr.'</small></div>'; 
		$html.='<div>'.convert_smilies(stripslashes($r->text)).'</div>'; 
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head profile="http://gmpg.org/xfn/11">
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php _e('Wordspew-RSS-Feed for:', wordspew);?> <?php bloginfo('name'); ?></title>
	<meta name="generator" content="WordPress <?php bloginfo('version'); ?>" /> <!-- leave this for stats -->
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen"/>
</head>

<body style="margin: 10px; text-align:left; font-size: 12pt; ">

<?php echo $html;?>

</body>
</html>
<?php
}
if($id=="")
	rss_feed();
else
	jal_getRSS ($id);?>