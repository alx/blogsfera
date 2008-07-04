<?php
/*
Plugin Name: Pierre's Wordspew
Plugin URI: http://pierre.sudarovich.free.fr/
Description: A plugin that creates a live shoutbox, using AJAX as a backend. Users can chat freely from your blog without refreshing the page! It uses the Fade Anything Technique for extra glamour
Author: Andrew Sutherland, Modified by Pierre
Version: 3.73
Author URI: http://blog.jalenack.com
*/
define('wordspew', 'wordspew/wordspew');
define('split',16);
// Version of this plugin. Not very useful for you, but for the dev
$jal_version = "3.73";
// The required user level needed to access the admin page for this plugin
$jal_admin_user_level = 8;

if (!isset($table_prefix)) {
	$html = implode('', file("../../../wp-config.php"));
	$html = str_replace ("require_once", "// ", $html);
	$html = str_replace ("<?php", "", $html);
	eval($html);
}
$portal_id = 1;
$jal_table_prefix = $table_prefix . $portal_id . "_";

// Register globals - Thanks Karan et Etienne
$jal_lastID    = isset($_GET['jal_lastID']) ? $_GET['jal_lastID'] : "";
$jal_user_name = isset($_POST['n']) ? $_POST['n'] : ""; 
$jal_user_url  = isset($_POST['u']) ? $_POST['u'] : "";
$jal_user_text = isset($_POST['c']) ? $_POST['c'] : "";
$jal_user_calc = isset($_POST['shoutboxOp']) ? $_POST['shoutboxOp'] : "-2";
$jal_user_Control=isset($_POST['shoutboxControl']) ? $_POST['shoutboxControl'] : "-3";
$jalGetChat    = isset($_GET['jalGetChat']) ? $_GET['jalGetChat'] : "";
$jalSendChat   = isset($_GET['jalSendChat']) ? $_GET['jalSendChat'] : "";

@session_start();

if ( !function_exists('current_user_can') ) :
	function current_user_can() { return 0; }
endif;

function jal_install_shout () {
global $jal_table_prefix, $wpdb, $user_level, $jal_admin_user_level, $wp_version;

    get_currentuserinfo();
	$current=current_user_can('level_'.$jal_admin_user_level);

    if ($user_level < $jal_admin_user_level && $current!=1) return;

  	$result = mysql_list_tables(DB_NAME);
  	$tables = array();

  	while ($row = mysql_fetch_row($result)) { $tables[] = $row[0]; }

    if (!in_array($jal_table_prefix."liveshoutbox", $tables)) {
    	$first_install = "yes";
    }

	$qry="CREATE TABLE ".$jal_table_prefix."liveshoutbox (
		    id mediumint(7) NOT NULL AUTO_INCREMENT,
		    time bigint(11) DEFAULT '0' NOT NULL,
		    name tinytext NOT NULL,
		    text text NOT NULL,
		    url text NOT NULL,
			ipaddr varchar(16),
			UNIQUE KEY id (id)
		    ) CHARACTER SET utf8;

		CREATE TABLE ".$jal_table_prefix."liveshoutbox_useronline (
  		    timestamp int(15) NOT NULL default '0',
		    username varchar(50) NOT NULL default '',
		    ip varchar(40) NOT NULL default '',
		    location varchar(255) NOT NULL default '',
		    url varchar(255) NOT NULL default '',
		    PRIMARY KEY  (timestamp),
		    KEY username (username),
		    KEY ip (ip),
		    KEY file (location)
		    ) CHARACTER SET utf8;
	";
$pathtoFunction = (floatval($wp_version) >= '2.3') ? "wp-admin/includes/upgrade.php" : "wp-admin/upgrade-functions.php";
	require_once(ABSPATH . $pathtoFunction);
	dbDelta($qry);

	if ($first_install == "yes") {
		$welcome_name = "Pierre";
		$welcome_text = __('Congratulations, you just completed the installation of this shoutbox.',wordspew);
		@mysql_query("SET CHARACTER SET 'utf8'");
		@mysql_query("SET NAMES utf8");
		$wpdb->query("INSERT INTO ".$jal_table_prefix."liveshoutbox (time,name,text) VALUES ('".time()."','".$welcome_name."','".$welcome_text."')");
		// Default shoutbox config
		add_option('shoutbox_fade_from', "666666",'','yes');
		add_option('shoutbox_fade_to', "FFFFFF",'','yes');
		add_option('shoutbox_update_seconds', 4000,'','yes');
		add_option('shoutbox_fade_length', 1500,'','yes');
		add_option('shoutbox_text_color', "333333",'','yes');
		add_option('shoutbox_name_color', "0066CC",'','yes');
		add_option('shoutbox_registered_only', '0','','yes');
	}
	else {
	$wpdb->query("ALTER TABLE ".$jal_table_prefix."liveshoutbox CHARACTER SET utf8");
	$wpdb->query("ALTER TABLE ".$jal_table_prefix."liveshoutbox MODIFY `text` TEXT NOT NULL, CHARACTER SET utf8");
	$wpdb->query("ALTER TABLE ".$jal_table_prefix."liveshoutbox MODIFY `name` TINYTEXT NOT NULL, CHARACTER SET utf8");
	$wpdb->query("ALTER TABLE ".$jal_table_prefix."liveshoutbox_useronline CHARACTER SET utf8");
	$wpdb->query("ALTER TABLE ".$jal_table_prefix."liveshoutbox_useronline MODIFY `username` VARCHAR(50) NOT NULL, CHARACTER SET utf8");
	}
	add_option('shoutbox_sound', '0','','yes');
	add_option('shoutbox_spam', '0','','yes');
	add_option('shoutbox_XHTML', '0','','yes');
	add_option('shoutbox_online', '0','','yes');
	add_option('shoutbox_Smiley', '0','','yes');
	add_option('shoutbox_Show_Spam', '0','','yes');
	add_option('shoutbox_nb_comment', '35','','yes');
	add_option('shoutbox_Captcha','0','','yes');
	add_option('shoutbox_HideUsers','0','','yes');
}

if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
	add_action('init', 'jal_install_shout');
}

// function to print the external javascript and css links
function jal_add_to_head () {
global $jal_version, $jal_table_prefix, $user_ID;

	$jal_wp_url = get_bloginfo('wpurl') . "/";

    echo '
    <!-- Added By Wordspew Plugin, modified by Pierre, version '.$jal_version.' -->
	<link rel="alternate" type="application/rss+xml" title="'. __('Wordspew-RSS-Feed for:', wordspew). ' '. get_bloginfo('name').'" href="'.$jal_wp_url.'wp-content/plugins/wordspew/wordspew-rss.php"/>
    <link rel="stylesheet" href="'.$jal_wp_url.'wp-content/plugins/wordspew/css.php" type="text/css" />
	<link rel="stylesheet" href="'.$jal_wp_url.'wp-content/plugins/wordspew/users.css" type="text/css" />
    <script type="text/javascript" src="'.$jal_wp_url.'wp-content/plugins/wordspew/fatAjax.php"></script>
    <script type="text/javascript">
	//<![CDATA[
	function trim(s) {
	return s.replace(/^( | )+/, \'\').replace(/( | )+$/, \'\');
	}
    function CheckSpam(theText,theURL) {
	theMsg=document.getElementById(\'chatbarText\').value;
	theMsg=theMsg.toLowerCase();
	count_http=theMsg.split("http").length;
	var limit=2;
	if((document.getElementById(\'shoutboxU\').value).length>7) {
		if(document.getElementById(\'shoutboxU\').style.display!="none") {
			limit++;
			count_http++;
		}
	}
	if(count_http>limit) {
		alert("'. __('Sorry, but you can post only one url by message...',wordspew) .'");
		return false;
	}
	theText+=\' \'+theURL;';
	$spam=get_option('moderation_keys');

	if($spam!="") {
		$spam = str_replace("'", "\'", $spam);
		$spam = str_replace("\r\n", "','", $spam);
		$spam="'".strtolower($spam)."'";
		}
	echo '
	    var spam = ['. str_replace(",''", "", $spam) .'];
	    TextToScan=theText.toLowerCase();
		for (var i = 0; i < spam.length; i++) {
			if(TextToScan.indexOf(spam[i])!=-1) {
				alert("'. __('No, sorry you used a banned word!',wordspew) .'\n-> "+spam[i].toUpperCase());
				return false;
				break;
			}
	    }
		';
		$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME, $conn);
		if (defined("DB_CHARSET")) {
			@mysql_query("SET CHARACTER SET 'utf8'", $conn);
			@mysql_query("SET NAMES utf8", $conn);
		}
		$column = (floatval(get_bloginfo('version')) > '1.5') ? "display_name" : "user_nickname";
		$SQL="SELECT ".$column." FROM ".$jal_table_prefix."users";
		$result=mysql_query($SQL,$conn);

		while ($row = mysql_fetch_assoc($result)) {
			$users.= "'".str_replace("'","\'",$row[$column])."',";
			$LoggedUsers.=$row[$column].",";
			}
		$users=strtolower(substr($users, 0, -1));
		$_SESSION['LoggedUsers']=strtolower(substr($LoggedUsers, 0, -1));
		$_SESSION['LoggedMsg']=__('No, sorry you used the name of a registered user! You have to change it please.',wordspew);
		if(get_option('shoutbox_HideUsers')==0 && !$user_ID) {
		echo '
		var users=['.$users.'];
		for (var i = 0; i < users.length; i++) {
			if(trim(document.getElementById(\'shoutboxname\').value.toLowerCase())==users[i]) {
				msg="'.$_SESSION['LoggedMsg'].'";
				msg+="\n-> "+trim(document.getElementById(\'shoutboxname\').value);
				alert(msg);
				return false;
				break;
			}
		}
		';
		}
		if (!defined("DB_CHARSET")) {
			@mysql_query("SET CHARACTER SET 'latin1'");
			@mysql_query("SET NAMES latin1");
		}
	echo '
	    return true;
	    }
	//]]>
	</script>
    ';		
}

// In the administration page, add some style and script...
function jal_add_to_admin_head () { ?>
<style type="text/css">
input[name=jal_delete]:hover, #jal_truncate_all:hover, input[name=jal_ban]:hover { background: #c22; color: #fff; cursor: pointer; }
input[name=jal_edit]:hover { background: #2c2; color: #fff; cursor: pointer; }
#shoutbox_options p { text-indent: 15px; padding: 5px 0; color: #555; }
#shoutbox_options span { border: 1px dotted #ccc; padding: 4px 14px; }
#outputList { list-style-type:none; }
</style>
<?php
}

// HTML printed to the admin panel
function jal_shoutbox_admin () { 
global $jal_admin_user_level, $wpdb, $user_level, $jal_table_prefix, $nb, $jal_version;
	get_currentuserinfo(); // Gets logged in user.
	
	$jal_number_of_comments=get_option('shoutbox_nb_comment');
	if ($jal_number_of_comments=="")
		$jal_number_of_comments=35;
	$nb =get_option('shoutbox_spam');
	if($nb=="") {
		jal_install_shout();
		$nb=0;
	}
	$current=current_user_can('level_'.$jal_admin_user_level);

	// If user is not allowed to use the admin page
	if ($user_level < $jal_admin_user_level &&  $current!=1) {
		echo '<div class="wrap"><h2>' . __("No Access for you!",wordspew) .'</h2></div>';
	} 
	else { ?>
 		<?php if (isset($_GET['jal_delete'])) { ?>
            <div class="updated"><p><?php _e('The comment was deleted successfully.',wordspew);?></p></div>
		<?php } if (isset($_GET['jal_edit'])) { ?>
            <div class="updated"><p><?php _e('The comment was edited successfully.',wordspew);?></p></div>
		<?php } if (isset($_GET['jal_truncate'])) { ?>
            <div class="updated"><p><?php _e('The shoutbox database has been wiped. You now have a fresh slate!',wordspew);?></p></div>
		<?php } if (isset($_GET['jal_ban'])) { ?>
			<div class="updated"><p><?php _e('The source ip of this comment was marked as spammer.',wordspew);?></p></div>
		<?php } ?>

	<div class="wrap">
	<h2><?php printf(__('Jalenack\'s Live Shoutbox (Actually <font color="red">%s</font> spams blocked)',wordspew),$nb);?> v. <?php 
	echo $jal_version; ?></h2>
	<p><?php _e('When you update the Times and Colors, you may need to refresh/empty cache before you see the changes take effect',wordspew);?></p>
	<p><?php 
	$results = $wpdb->get_var("SELECT id FROM ".$jal_table_prefix."liveshoutbox ORDER BY id DESC LIMIT 1");
	printf(__('There have been <b>%s</b> messages in this shoutbox',wordspew),$results);?></p>
	<form name="shoutbox_options" action="edit.php?page=wordspew" method="get" id="shoutbox_options"> 
	<fieldset> 
	<legend><b><?php _e('Colors (Must be 6 digit hex)',wordspew);?></b></legend>
	<input type="hidden" name="page" value="wordspew" />
	<?php _e('Fade from',wordspew);?>: #<input type="text" maxlength="6" name="fade_from" 
	value="<?php echo get_option('shoutbox_fade_from'); ?>" size="6" /> <span 
	style="background: #<?php echo get_option('shoutbox_fade_from'); ?>;">&nbsp;</span>
	<p><?php _e('The color that new messages fade in from. Default',wordspew);?>: <span style="color: #666">666666</span></p>
	<?php _e('Fade to',wordspew);?>: #<input type="text" maxlength="6" name="fade_to" value="<?php echo get_option('shoutbox_fade_to'); ?>" size="6" /> <span style="background: #<?php echo get_option('shoutbox_fade_to'); ?>;">&nbsp;</span>
	<p><?php _e('Also used as the background color of the box. Default: FFFFFF (white)',wordspew);?></p>
	<?php _e('Text Color',wordspew);?>: #<input type="text" maxlength="6" name="text_color" 
	value="<?php echo get_option('shoutbox_text_color'); ?>" size="6" /> <span 
	style="background: #<?php echo get_option('shoutbox_text_color'); ?>;">&nbsp;</span>
	<p><?php _e('The color of text within the box. Default',wordspew);?>: <span style="color: #333">333333</span></p>
	<?php _e('Name Color',wordspew);?>: #<input type="text" maxlength="6" name="name_color" 
	value="<?php echo get_option('shoutbox_name_color'); ?>" size="6" /> <span 
	style="background: #<?php echo get_option('shoutbox_name_color'); ?>;">&nbsp;</span>
	<p><?php _e('The color of peoples\' names. Default',wordspew);?>: <span style="color: #06c">0066CC</span></p>
	</fieldset>
	<br />
	<fieldset> 
	<legend><b><?php _e('Other',wordspew);?></b></legend>

	<?php _e('Show',wordspew);?>:<input type="text" maxlength="3" name="nb_comment" value="<?php echo $jal_number_of_comments; ?>" 
	size="2" /> <?php _e('comments in the shoutbox',wordspew);?><br />
	<p><?php _e('Enter, here, the number of shouts you want to show in your shoutbox', wordspew);?></p>

	<?php _e('Update Every',wordspew);?>: <input type="text" maxlength="3" name="update_seconds" 
	value="<?php echo get_option('shoutbox_update_seconds') / 1000; ?>" size="2" /> <?php _e('Seconds',wordspew);?><br />
	<p><?php _e('This determines how "live" the shoutbox is. With a bigger number, it will take more time for messages to show up, but also decrease the server load. You may use decimals. This number is used as the base for the first 8 javascript loads. After that, the number gets successively bigger. Adding a new comment or mousing over the shoutbox will reset the interval to the number suplied above. Default: 4 Seconds',wordspew);?></p>
	<?php _e('Fade Length',wordspew);?>: <input type="text" maxlength="3" name="fade_length" 
	value="<?php echo get_option('shoutbox_fade_length') / 1000; ?>" size="2" /> <?php _e('Seconds',wordspew);?><br />
	<p><?php _e('The amount of time it takes for the fader to completely blend with the background color. You may use decimals. Default 1.5 seconds',wordspew);?></p>
	<?php _e('Use textarea',wordspew);?>: <input type="checkbox" name="use_textarea" <?php if(get_option('shoutbox_use_textarea') == 'true') { echo 'checked="checked" '; } ?>/>
	<p><?php _e('A textarea is a bigger type of input box. Users will have more room to type their comments, but it will take up more space.',wordspew);?></p>
	<?php _e('Use URL field',wordspew);?>: <input type="checkbox" name="use_url" <?php if(get_option('shoutbox_use_url') == 'true') echo 'checked="checked" '; ?>/>
	<p><?php _e('Check this if you want users to have an option to add their URL when submitting a message.',wordspew);?></p>
	<?php _e('Use sound alert',wordspew);?>: <input type="checkbox" name="use_sound" <?php if(get_option('shoutbox_sound') == '1') echo 'checked="checked" '; ?>/>
	<p><?php _e('Check this if you want to hear a sound alert when someone post message',wordspew);?></p>
	<?php _e('XHTML strict',wordspew);?>: <input type="checkbox" name="XHTML" <?php if(get_option('shoutbox_XHTML') == '1') echo 'checked="checked" '; ?>/>
	<p><?php _e('Check this if you want to use XHTML strict',wordspew);?></p>
	<?php _e('Show users online',wordspew);?>: <input type="checkbox" name="Show_Users" <?php if(get_option('shoutbox_online') == '1') echo 'checked="checked" '; ?>/>
	<p><?php _e('Check this if you want to show, in real time, users online',wordspew);?></p>
	<?php _e('Show smileys list',wordspew);?>: <input type="checkbox" name="Show_Smiley" <?php if(get_option('shoutbox_Smiley') == '1') echo 'checked="checked" '; ?>/>
	<p><?php _e('Check this if you want to show the smileys list',wordspew);?></p>	
	<?php _e('Show blocked spams',wordspew);?>: <input type="checkbox" name="Show_Spam" <?php if(get_option('shoutbox_Show_Spam') == '1') echo 'checked="checked" '; ?>/>
	<p><?php _e('Check this if you want to show blocked spams',wordspew);?></p>
	<?php _e('Use a captcha',wordspew);?>: <input type="checkbox" name="Captcha" <?php if(get_option('shoutbox_Captcha') == '1') echo 'checked="checked" '; ?>/> <input type="text" name="hash" 
	value="<?php echo get_option('shoutbox_hash'); ?>" size="30" /> <?php _e('Enter here your secret sentence',wordspew);?>
	<p><?php _e('Check this if you want to use a captcha (in fact it\'s a simple addition that users have to resolve before post any new message in the shoutbox).',wordspew);?></p>
	<?php _e('Hide users list',wordspew);?>: <input type="checkbox" name="Hide_Users" <?php if(get_option('shoutbox_HideUsers') == '1') echo 'checked="checked" '; ?>/>
	<p><?php _e('Check this if you want to hide users list from document header in the javascript function. It permit to not expose your users list from a "view source".',wordspew);?></p>
	<?php _e('Only allow registered users',wordspew);?>: <input type="checkbox" name="registered_only" <?php if(get_option('shoutbox_registered_only') == '1') echo 'checked="checked" '; ?>/>
	<p><?php _e('This will only let your registered users use the form that allows one to type messages. Users who are NOT logged in will be able to watch the chat and a message saying they must be logged in to comment. <b>Note:</b> this is not completely "secure"... If someone REALLY wanted to, they could write a script that interacts directly with the message receiving file. They\'d have to know what they\'re doing and it would be quite pointless.',wordspew);?></p>
	</fieldset><br /> 
	<input type="submit" name="jal_admin_options" value="<?php _e('Save',wordspew);?>" class="button" style="font-size: 140%"  /><br /><br />
	<input type="submit" name="jal_truncate" id="jal_truncate_all" onclick="return confirm('<?php _e("You are about to delete ALL messages in the shoutbox. It will completely erase all messages.\\nAre you sure you want to do this?\\n\'Cancel\' to stop, \'OK\' to delete.",wordspew); ?>');" value="<?php _e('Delete ALL messages',wordspew);?>" /><br /><br />
	</form>
	<fieldset>
	<legend><b><?php _e('Data',wordspew);?></b> <?php printf(__('(showing the last <b>%s</b> messages)',wordspew),$jal_number_of_comments);?></legend>
	<p><?php _e('Reminder: You MUST have at LEAST one comment in your shoutbox at all times. This is not live. New comments made while viewing this page will not magically appear like they do in the real thing.',wordspew);?></p>
	<p><?php printf(__('<a href="%s"><b>Click here</b></a> to manage your banned words list and IP addresses.',wordspew),get_bloginfo('wpurl')."/wp-admin/options-discussion.php#moderation_keys");?></p>
	<p><?php _e('<b><font color="red">Important !</font></b> To ban a single IP address just click on "Ban this IP" button. If you want to ban a range of IP, use this syntax (for this example i can say good bye to Vsevolod Stetsinsky) : 195.225.176/179.* where slash means from 176 to 179 and * from 0 to 255.<br/>BTW i ban IP addresses from 195.225.176.0 to 195.225.179.255. You can mix the two options...',wordspew);?></p>
	<?php
	@mysql_query("SET CHARACTER SET 'utf8'");
	@mysql_query("SET NAMES utf8");
	$results = $wpdb->get_results("SELECT * FROM ".$jal_table_prefix."liveshoutbox ORDER BY id DESC LIMIT ". $jal_number_of_comments);
	$jal_first_time = "yes"; // Will only add the last message div if it is looping for the first time

	foreach( $results as $r ) { // Loops the messages into a list
		if($r->url!="") if (strpos($r->url, $Actual_URL)===false && $XHTML==0) $target=' target="_blank"';
		$url = (empty($r->url) && $r->url = "http://") ? '<span title="'.jal_time_since( $r->time ).'">'.$r->name.'</span>' : '<a href="'.$r->url.'"'.$target.' title="'.jal_time_since( $r->time ).'">'.$r->name.'</a>';
		if ($jal_first_time == "yes") {
			printf(__('<div id="lastMessage"><span>Last Message</span> <em id="responseTime">%s ago</em></div>',wordspew),jal_time_since($r->time));
			echo '<hr/><div align="right">
		<ul id="outputList">'; }
	echo '<li><form action="edit.php?page=wordspew" method="get"><span>'.stripslashes($url).' : </span>
	<a href="http://whois.domaintools.com/'.$r->ipaddr.'" target="_blank" title="Whois">*</a>
	<input type="text" name="jal_text" value="'.htmlspecialchars(stripslashes($r->text),ENT_QUOTES).'" size="60"/>
	<input type="hidden" name="page" value="wordspew"/>
	<input type="hidden" name="jal_comment_id" value="'.$r->id.'"/>
	<input type="text" name="ip" value="'.$r->ipaddr.'" size="16"/>
	<input type="submit" name="jal_ban" value="'.__("Ban this IP",wordspew).'"/>
	<input type="submit" name="jal_delete" value="'.__("Delete",wordspew).'"/>
	<input type="submit" name="jal_edit" value="'.__("Edit",wordspew).'"/></form></li>
	'; 
	$jal_first_time = "0"; }
	?>
	</ul></div>
	</fieldset>
	</div>
<?php } }

// To add administration page under Management Section
function shoutbox_admin_page() {
global $jal_admin_user_level;
	add_management_page('Shoutbox Management', 'Live Shoutbox', $jal_admin_user_level, "wordspew", 'jal_shoutbox_admin');
}

// Time Since function courtesy 
// http://blog.natbat.co.uk/archive/2003/Jun/14/jal_time_since
// Works out the time since the entry post, takes a an argument in unix time (seconds)
function jal_time_since($original) {
    // array of time period chunks
    $chunks = array(
        array(60 * 60 * 24 * 365 , __('year',wordspew),__('years',wordspew)),
        array(60 * 60 * 24 * 30 , __('month',wordspew),__('months',wordspew)),
        array(60 * 60 * 24 * 7, __('week',wordspew),__('weeks',wordspew)),
        array(60 * 60 * 24 , __('day',wordspew),__('days',wordspew)),
        array(60 * 60 , __('hour',wordspew),__('hours',wordspew)),
        array(60 , __('minute',wordspew),__('minutes',wordspew)),
    );
    $original = $original - 10; // Shaves a second, eliminates a bug where $time and $original match.
    $today = time(); /* Current unix time  */
    $since = $today - $original;
    
    // $j saves performing the count function each time around the loop
    for ($i = 0, $j = count($chunks); $i < $j; $i++) {
        $seconds = $chunks[$i][0];
        $name = $chunks[$i][1];
		$name_s = $chunks[$i][2];
        // finding the biggest chunk (if the chunk fits, break)
        if (($count = floor($since / $seconds)) != 0) {
            break;
        }
    }

	$print = $count ." ".pluralize($count,$name,$name_s);

    if ($i + 1 < $j) {
        // now getting the second item
        $seconds2 = $chunks[$i + 1][0];
        $name2 = $chunks[$i + 1][1];
		$name2_s= $chunks[$i + 1][2];

        // add second item if it's greater than 0
        if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
			$print .= ", " .$count2." ".pluralize($count2,$name2,$name2_s);
        }
    }
    return $print;
}

if(!function_exists('pluralize')) :
	function pluralize($count, $singular, $plural = false) {
	if (!$plural) $plural = $singular . 's';
	return ($count < 2 ? $singular : $plural) ;
	}
endif;

////////////////////////////////////////////////////////////
// Functions Below are for getting comments from the database
////////////////////////////////////////////////////////////
// Never cache this page
if ($jalGetChat == "yes" || $jalSendChat == "yes") {
	header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
	header( "Last-Modified: ".gmdate( "D, d M Y H:i:s" )."GMT" ); 
	header( "Cache-Control: no-cache, must-revalidate" ); 
	header( "Pragma: no-cache" );
	header("Content-Type: text/html; charset=utf-8");
	//if the request does not provide the id of the last know message the id is set to 0
	if (!$jal_lastID) $jal_lastID = 0;
}

// retrieves all messages with an id greater than $jal_lastID
if ($jalGetChat == "yes") {
	jal_getData($jal_lastID);
}

// Where the shoutbox receives information
function jal_getData ($jal_lastID) {
global $jal_table_prefix;
if(isset($_SESSION['spam_msg'])) {
	$loop =$jal_lastID."---SPAMMER---".$_SESSION['spam_msg'];
	$who=($_SESSION['Show_Users']==0) ? "" : jal_get_useronline_extended();
	echo $who."\n".$loop;
	unset($_SESSION['spam_msg']);
}
else {
	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);
	@mysql_query("SET CHARACTER SET 'utf8'", $conn);
	@mysql_query("SET NAMES utf8", $conn);
	$sql = "SELECT * FROM ".$jal_table_prefix."liveshoutbox WHERE id > ".$jal_lastID." ORDER BY id DESC";
	$results = mysql_query($sql, $conn);
	$loop = "";

	while ($row = mysql_fetch_array($results)) {
		$id   = $row[0];
		$time = $row[1];
		$name = $row[2];
		$text = $row[3];
		$url  = $row[4];
		if(verifyName($name))
			$user=1;
		else
			$user=0;
		// append the new id's to the beginning of $loop --- is being used to separate the fields in the output
		$loop = $id."---".stripslashes($name)."---".stripslashes($text)."---".stripslashes($url)."---".$user."---" . $loop;
	}
	$who=($_SESSION['Show_Users']==0) ? "" : jal_get_useronline_extended();

	echo $who."\n".$loop;

	// if there's no new data, send one byte. Fixes a bug where safari gives up w/ no data
	if (empty($loop)) { echo "0"; }
	}
}

function jal_special_chars ($s) {
	$s = htmlspecialchars($s, ENT_COMPAT,'UTF-8');
	return str_replace("---","&minus;-&minus;",$s);
}

function check_ip_address($from, $checkip) {
global $spam_msg;

	$checkip=trim($checkip);
	if(strpos($checkip,"*") || strpos($checkip,"/")) {
		$checkip =str_replace("*", "([0-9]{1,3})", $checkip);
		if(strpos($checkip,"/")) {
			$ar=explode(".",$checkip);
			for($i=0; $i<@count($ar); $i++) {
				$ar2=explode("/",$ar[$i]);
				if(@count($ar2)==2) {
					$ip="(";
					for($j=intval($ar2[0]); $j<intval($ar2[1]);$j++) {
						$ip.=$j."|";					
					}
					$ip.=$ar2[1].")";
					$ar[$i]=eregi_replace("([0-9]{1,3})/([0-9]{1,3})", $ip, $ar[$i]);
				}
			}
			$checkip =$ar[0].".".$ar[1].".".$ar[2].".".$ar[3];
		}
		if (eregi($checkip, $from))	return false;
	}
	elseif($from==$checkip) return false;

	return true;
}

function CheckSpam($theText,$TheURL) {
global $spam_msg, $jal_table_prefix, $ip;

$count_http=substr_count($theText,"http");
if($count_http>1) {
	$spam_msg=$_SESSION['HTTPLimit'];
	return false;
}
$count_content_type=substr_count($theText,"content-type");
if($count_content_type>=1) {
	$spam_msg=$_SESSION['DLSpam'];
	return false;
}

$theText.=$TheURL;
$ip = $_SERVER['REMOTE_ADDR'];

$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME, $conn);
@mysql_query("SET CHARACTER SET 'utf8'", $conn);
@mysql_query("SET NAMES utf8", $conn);

$spam=array();
$SQL="SELECT option_value FROM ".$jal_table_prefix."options WHERE option_name = 'moderation_keys'";
$spam=mysql_query($SQL,$conn);
$sql_create_arr = mysql_fetch_array($spam);
$spam= $sql_create_arr[0];

$spam=explode("\r\n",strtolower($spam));
if($spam[0]!="") {
	for($i=0;$i<@count($spam);$i++) {
		$str=$spam[$i];
		if (strlen($str)>8 && intval($str)) {
			if(!check_ip_address($ip, $str)) {
				$spam_msg=$_SESSION['IPLogged'];
				return false;
				break;
			}
		}
		$pos=strpos($theText,$str);
		if(is_int($pos)) {
			$spam_msg=$_SESSION['DLSpam'];
			return false;
			break;
		}
	}
}
return true;
}

//////////////////////////////////////////////////////
// Functions Below are for submitting comments to the database
//////////////////////////////////////////////////////
// When user submits and javascript fails
if (isset($_POST['shout_no_js'])) {
	$myURL = isset($_POST['shoutboxU']) ? $_POST['shoutboxU'] : ""; 
	if ($_POST['shoutboxname'] != '' && $_POST['chatbarText'] != '')
		jal_addData($_POST['shoutboxname'], $_POST['chatbarText'], $myURL);
	else echo "You must have a name and a comment...";
}

//only if a name and a message have been provides the information is added to the db
if ($jal_user_name != '' && $jal_user_text != '' && $jalSendChat == "yes") {
	jal_addData($jal_user_name,$jal_user_text,$jal_user_url); //adds new data to the database
	echo "0";
}

function mySplit ($captures){
	// si url ou email, on passe...
	if(preg_match('#^(?:(?:http|ftp)s?://|[-_a-z0-9]+(?:\.[-_a-z0-9]+)*@[-a-z0-9]+(?:\.[-a-z0-9]+)*\.[a-z]{2,6})#i',$captures[0])) {
		$return = $captures[0];
	}
	else {
		$splited = preg_replace("/([^\s]{".split."})/iu","$1 ",$captures[0]);
		$return = trim($splited);
	}
	return $return;
}

function jal_addData($jal_user_name,$jal_user_text,$jal_user_url) {
global $spam_msg, $jal_table_prefix, $jal_user_val, $jal_user_calc, $jal_user_Control, $ip;

	$SearchText=strtolower(trim($jal_user_text));
	$SearchURL=strtolower(trim($jal_user_url));
	//replacement of non-breaking spaces...
	$SearchName=str_replace(" "," ",$jal_user_name);
	$SearchName=trim($SearchName);
	$SearchName=strtolower($SearchName);
	$myBolean="";

	//if the BadCalc variable is not set then it's a bot (direct access to wordspew)
	if(!isset($_SESSION['BadCalc'])) {
		AddSpam("I DON'T LIKE SPAM !!!");
		exit;
	}

	if($SearchURL == "http://") $SearchURL="";

	if($SearchName==$SearchText || isset($_POST['shoutboxurl'])) {
		AddSpam($_SESSION['DLSpam']);
		exit;
	}

	$hashtext = $_SESSION['hashtext'];
	$jal_user_calc=md5($jal_user_calc.$hashtext);
	if($jal_user_calc!=$jal_user_Control) {
		AddSpam($_SESSION['BadCalc']);
		exit;
	}

	if(!isset($_SESSION['Logged']) && verifyName($SearchName)) {
		AddSpam($_SESSION['LoggedMsg']);
		exit;
	}

	if(CheckSpam($SearchText.' '.$SearchName, $SearchURL)) {
		setcookie("jalUserName",$jal_user_name,time()+60*60*24*30*3,'/');
		//the message is cut of after 500 letters
		$jal_user_text = trim(substr($jal_user_text,0,500));

		// masque pour capturer toute chaîne de plus de $split car.
		$pattern = '#[^ ]{'.split.',}#u';
		// appel à une fonction callback de remplacement (*beaucoup* plus rapide que preg_replace() option e)
		$jal_user_text = preg_replace_callback($pattern, 'mySplit', $jal_user_text);

		$jal_user_text=jal_special_chars($jal_user_text);
		$jal_user_name = substr(trim($jal_user_name), 0,18);
		$jal_user_name=jal_special_chars($jal_user_name);
		$jal_user_url = ($jal_user_url == "http://") ? "" : jal_special_chars($jal_user_url);
		
		if (substr($jal_user_url,0,3)=="www") $jal_user_url ="http://".$jal_user_url;
		if (strpos($jal_user_url,"@")!=false) $jal_user_url ="mailto:".$jal_user_url;

		$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_select_db(DB_NAME, $conn);
		@mysql_query("SET CHARACTER SET 'utf8'", $conn);
		@mysql_query("SET NAMES utf8", $conn);
		if($jal_user_url!="") {
			setcookie("jalUrl",str_replace("mailto:","",$jal_user_url),time()+60*60*24*30*3,'/');
			if($_SESSION['useURL']=="") $myBolean="false";
		}

		if($myBolean=="") {
			if($_SESSION['useCaptcha']=="1") setcookie("jalCaptcha","Ok",time()+60*60*24*30*3,'/');
			mysql_query("INSERT INTO ".$jal_table_prefix."liveshoutbox (time,name,text,url,ipaddr) VALUES ('".time()."','".mysql_real_escape_string($jal_user_name)."','".mysql_real_escape_string($jal_user_text)."','".mysql_real_escape_string($jal_user_url)."', '".mysql_real_escape_string($ip)."')", $conn);
			jal_deleteOld(); //some database maintenance
			//take them right back where they left off
			header('location: '.$_SERVER['HTTP_REFERER']);
			}
		else {
			AddSpam($_SESSION['DLSpam']);
		}
	}
	else AddSpam($spam_msg);
}

function AddSpam($msg) {
global $jal_table_prefix, $jalSendChat;

	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);

	$SQL= mysql_query("SELECT option_value FROM ".$jal_table_prefix."options WHERE option_name='shoutbox_spam'");
	$nb= mysql_result($SQL, 0)+1;
	mysql_query("UPDATE ".$jal_table_prefix."options SET option_value='".$nb."' WHERE option_name='shoutbox_spam'",$conn);

	if($jalSendChat=="yes") {
		$_SESSION['spam_msg']= $msg;
		header('location: '.$_SERVER['HTTP_REFERER']);
	}
	else echo $msg;
}

//Maintains the database by deleting past comments
function jal_deleteOld() {
global $jal_table_prefix;
	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);
	$SQL=mysql_query("SELECT option_value FROM ".$jal_table_prefix."options WHERE option_name = 'shoutbox_nb_comment'");
	$jal_number_of_comments=mysql_result($SQL,0);
	$results = mysql_query("SELECT * FROM ".$jal_table_prefix."liveshoutbox ORDER BY id DESC LIMIT ".$jal_number_of_comments, $conn);	

	while ($row = mysql_fetch_array($results)) { $id = $row[0]; }

	if ($id) mysql_query("DELETE FROM ".$jal_table_prefix."liveshoutbox WHERE id < ".$id, $conn);
}
function sanitize_name($name) {
$bad = array(" ", " ", "'", ".");
$good= array("", "", "", "");
return str_replace($bad, $good, $name);
}
function verifyName($name) {
$ar=explode(",",$_SESSION['LoggedUsers']);
if(in_array(strtolower($name), $ar)) return true;
else return false;
}
// Prints the html structure for the shoutbox
function jal_get_shoutbox () {
global $wpdb, $jal_table_prefix, $user_level, $user_nickname, $user_url, $user_ID, $jal_admin_user_level, $user_identity;
get_currentuserinfo(); // Gets logged in user.
$theuser_nickname=$user_nickname;
$ActualVersion=round(get_bloginfo('version'));
if($ActualVersion>=2) $theuser_nickname=$user_identity;
if(get_option('shoutbox_spam')=="")	jal_install_shout();
$XHTML=get_option('shoutbox_XHTML');
$Captcha=get_option('shoutbox_Captcha');
$jal_number_of_comments=get_option('shoutbox_nb_comment');
$Actual_URL=get_bloginfo('wpurl');
$_SESSION['Show_Users']=get_option('shoutbox_online');
$_SESSION['BadCalc']=__("You should learn to count before use the shoutbox :)",wordspew);
$_SESSION['DLSpam']=__("I DON'T LIKE SPAM !!!",wordspew);
$_SESSION['HTTPLimit']=__("Sorry, but you can post only one url by message...",wordspew);
$_SESSION['IPLogged']=__("Your IP address have been banned from this blog, if you feel this is in error please contact the webmaster.",wordspew);
$_SESSION['hashtext']=get_option('shoutbox_hash');
$_SESSION['useURL']=get_option('shoutbox_use_url');
$_SESSION['useCaptcha']=get_option('shoutbox_Captcha');
?>
<div id="wordspew">
	<div id="chatoutput">
		<?php
		@mysql_query("SET CHARACTER SET 'utf8'");
		@mysql_query("SET NAMES utf8");
		$wpdb->hide_errors();
		$results = $wpdb->get_results("SELECT * FROM ".$jal_table_prefix."liveshoutbox ORDER BY id DESC LIMIT ".$jal_number_of_comments);
		$wpdb->show_errors();

		// Will only add the last message div if it is looping for the first time
		$jal_first_time = true;
		$registered_only = (get_option('shoutbox_registered_only') == "1") ? TRUE : FALSE;
		// Loops the messages into a list
		foreach( $results as $r ) {
			$target="";
			// Add links
			if (strpos($r->text, $Actual_URL)===false && $XHTML==0) $target=' target="_blank"';
			$theLink=__("link",wordspew); $theMail=__("email",wordspew);
			$r->text = preg_replace("`(http|ftp)+(s)?:(//)((\w|\.|\-|_)+)(/)?(\S+)?`i", "<a href=\"\\0\"$target>&laquo;$theLink&raquo;</a>", $r->text);
			$r->text = preg_replace("`([-_a-z0-9]+(\.[-_a-z0-9]+)*@[-a-z0-9]+(\.[-a-z0-9]+)*\.[a-z]{2,6})`i","<a href=\"mailto:\\1\">&laquo;$theMail&raquo;</a>", $r->text); 

			if ($jal_first_time == true) {
				$rand1=mt_rand(0,10);
				$rand2=mt_rand(0,10);
				$total=intval($rand1+$rand2);

				if (get_option('shoutbox_sound') == "1") {
					$img_sound=($_COOKIE['jalSound']==1 || $_COOKIE['jalSound']=="") ? "sound_1.gif" : "sound_0.gif";
					echo '<img src="'. $Actual_URL .'/wp-content/plugins/wordspew/'.$img_sound.'" alt="" 
					onclick="setSound();" id="JalSound" title="';
					_e("Click this to turn on/off sound",wordspew);
					echo '"/>
					';
				}
				if(get_option('shoutbox_Show_Spam')) {
					$nb = get_option('shoutbox_spam');
					printf(__('<div id="Show_Spam">%s spams blocked</div>',wordspew),$nb);
				}
				printf(__('<div id="lastMessage"><span>Last Message</span> <em id="responseTime">%s ago</em></div>',wordspew),jal_time_since($r->time));
				echo '<div id="usersOnline">'.jal_get_useronline_extended().'</div>';
				echo '<ul id="outputList">'; }

			if ($jal_first_time == true) $lastID = $r->id;
			$target="";
			if($r->url!="") if (strpos($r->url, $Actual_URL)===false && $XHTML==0) $target=' target="_blank"';
			$url = (empty($r->url) && $r->url = "http://") ? $r->name : '<a href="'.$r->url.'"'.$target.'>'.$r->name.'</a>';
			if($jal_first_time == true && !isset($_COOKIE['jalCaptcha']) && !$user_ID && !$registered_only && $_SESSION['useCaptcha'] == '1') 
				echo '<li><span>'.__("Info",wordspew).' : </span><b>'.__("Please, resolve the addition below before post any new comment...",wordspew).'</b></li>';
			if(verifyName($r->name)) {
			$class="jal_user ";
			}
			echo '<li><span title="'.jal_time_since( $r->time ).'" class="'.$class. sanitize_name($r->name).'">'.stripslashes($url).' : </span>'.convert_smilies(" ".stripslashes($r->text)).'</li>
			'; 
			$jal_first_time = false;
			$class="";
		}

		$use_url = (get_option('shoutbox_use_url') == "true") ? TRUE : FALSE;
		$use_textarea = (get_option('shoutbox_use_textarea') == "true") ? TRUE : FALSE;
		if (!defined("DB_CHARSET")) {
			@mysql_query("SET CHARACTER SET 'latin1'");
			@mysql_query("SET NAMES latin1");
			}
		?>
		</ul>
	</div>
	<div id="chatInput">
<?php 
	$hashtext = $_SESSION['hashtext'];

	if (!$registered_only || ($registered_only && $user_ID)) {
	$display_name=($_COOKIE['jalUserName']) ? $_COOKIE['jalUserName'] : __("Guest_",wordspew).rand(0,5000);
	$display_name=str_replace("\'", "'", $display_name);
	?>
	<form id="chatForm" method="post" action="<?php bloginfo('wpurl'); ?>/wp-content/plugins/wordspew/wordspew.php">
	<?php
	$current=current_user_can('level_'.$jal_admin_user_level);
	if ($user_level >= $jal_admin_user_level || $current==1) { // If user is allowed to use the admin page
		echo '<a href="'.get_bloginfo("wpurl").'/wp-admin/edit.php?page=wordspew" id="shoutboxAdmin">'. __("Admin",wordspew).'</a>';
	}
	if (!empty($theuser_nickname)) { /* If they are logged in, then print their nickname */ 
	$_SESSION['Logged']="ok"; ?>
	<input type="hidden" name="shoutboxControl" id="shoutboxControl" value="<?php echo md5($total.$hashtext); ?>"/>
	<input type="hidden" name="shoutboxOp" id="shoutboxOp" value="<?php echo $total; ?>"/>
	<label><?php _e('Name',wordspew); ?>: <em><?php echo $theuser_nickname ?></em></label>
	<input type="hidden" name="shoutboxname" id="shoutboxname" value="<?php echo $theuser_nickname; ?>"/>
	<input type="hidden" name="shoutboxU" id="shoutboxU" value="<?php if($use_url) { echo $user_url; } ?>"/>
	<?php } else { echo "\n"; /* Otherwise allow the user to pick their own name */ ?>

	<?php if ($Captcha==1) { ?>
	<input type="hidden" name="shoutboxControl" id="shoutboxControl" value="<?php echo md5($total.$hashtext); ?>"/>
	<div id="shoutbox_captcha">
	<label><?php _e('Captcha',wordspew); ?>:</label> <select name="shoutboxOp" id="shoutboxOp" 
	onchange="MasqueSelect()" onclick="MasqueSelect()">
	<option value="-3"><?php echo $rand1."+".$rand2."="; ?></option>
	<?php for ($i = 0; $i < 21; $i++) {
	echo '<option value="'.$i.'">'.$i.'</option>';
	}
	echo '</select></div>';
	}
	else { ?>
		<input type="hidden" name="shoutboxControl" id="shoutboxControl" value="<?php echo md5($total.$hashtext); ?>"/>
		<input type="hidden" name="shoutboxOp" id="shoutboxOp" value="<?php echo $total; ?>"/>
	<? } ?>

	<label for="shoutboxname"><?php _e('Name',wordspew); ?>:</label>
	<input type="text" name="shoutboxname" id="shoutboxname" value="<?php echo $display_name; ?>" maxlength="18"/>
	<label for="shoutboxU"<?php if (!$use_url) echo ' style="display: none"'; ?>><?php _e('URL/Email',wordspew); ?>:</label>
	<input type="text" name="shoutboxU" id="shoutboxU" value="<?php if ($_COOKIE['jalUrl'] && $use_url) echo $_COOKIE['jalUrl']; else echo 'http://'; ?>"<?php if (!$use_url) echo ' style="display: none"'; ?>/>
	<?php  } echo "\n"; ?>
	<label for="chatbarText"><?php _e('Message',wordspew) ?>:</label>
	<?php if ($use_textarea) { ?>
	<textarea rows="4" cols="16" name="chatbarText" id="chatbarText" onkeypress="return pressedEnter(this,event);"></textarea>
	<?php } else { ?>
	<input type="text" name="chatbarText" id="chatbarText"/>
	<?php } ?>
	<input type="hidden" id="jal_lastID" value="<?php echo $lastID + 1; ?>" name="jal_lastID"/>
	<input type="hidden" name="shout_no_js" value="true"/>
	<div id="SmileyList"></div>
	<input type="submit" id="submitchat" name="submit" value="<?php _e('Send',wordspew);?>"/>
	</form>
<?php }
else { ?>
	<form id="chatForm" action="">
	<p align="center"><?php _e('You must be a registered user to participate in this chat',wordspew); ?></p>
	<input type="hidden" name="shoutboxControl" id="shoutboxControl" value="<?php echo md5($total.$hashtext); ?>"/>
	<input type="hidden" name="shoutboxOp" id="shoutboxOp" value="<?php echo $total; ?>"/>
	<input type="hidden" id="shoutboxname"/>
	<input type="hidden" id="shoutboxU"/>
	<input type="hidden" id="chatbarText"/>
	<input type="hidden" id="jal_lastID" value="<?php echo $lastID+1; ?>"/>
	<input type="submit" id="submitchat" name="submit" style="display:none;"/>
	</form>
<?php } ?>
	</div>
</div>
<?php if (get_option('shoutbox_sound') == "1") echo('<span id="TheBox"></span>'); }

/* Widget */
if (function_exists("add_action")) {
	include_once ('widgetized.php');
	add_action("plugins_loaded","jal_on_plugins_loaded");
	}
/* End Widget */

function jal_admin_options() {
global $wpdb, $jal_table_prefix, $user_level, $jal_admin_user_level;

    // Security
    get_currentuserinfo();
	$current=current_user_can('level_'.$jal_admin_user_level);

    if ($user_level <  $jal_admin_user_level && $current!=1) die(__("Cheatin' uh ?"));

	// Convert from milliseconds
	$fade_length = $_GET['fade_length'] * 1000;
	$update_seconds = $_GET['update_seconds'] * 1000;

	// Update choices from admin panel
	update_option('shoutbox_fade_from', $_GET['fade_from']);
	update_option('shoutbox_fade_to', $_GET['fade_to']);
	update_option('shoutbox_update_seconds', $update_seconds);
	update_option('shoutbox_fade_length', $fade_length);
	update_option('shoutbox_text_color', $_GET['text_color']);
	update_option('shoutbox_name_color', $_GET['name_color']);

	$use_url = ($_GET['use_url']) ? "true" : "";
	$use_textarea = ($_GET['use_textarea']) ? "true" : "";
	$registered_only = ($_GET['registered_only']) ? "1" : "0";
	$use_sound = ($_GET['use_sound']) ? "1" : "0";
	$XHTML=($_GET['XHTML']) ? "1" : "0";
	$Online=($_GET['Show_Users']) ? "1" : "0";
	$Smiley=($_GET['Show_Smiley']) ? "1" : "0";
	$Show_Spam=($_GET['Show_Spam']) ? "1" : "0";
	$Captcha=($_GET['Captcha']) ? "1" : "0";
	$hash=($_GET['hash']!="") ? $_GET['hash'] : __("Your secret sentence",wordspew)."_".mt_rand(0,5000);
	$HideUsers=($_GET['Hide_Users']) ? "1" : "0";
	if(!is_numeric($_GET['nb_comment']))
		$nb_comment=35;
	else
		$nb_comment=intval($_GET['nb_comment']);

	update_option('shoutbox_use_url', $use_url);
	update_option('shoutbox_use_textarea', $use_textarea);
	update_option('shoutbox_registered_only', $registered_only);
	update_option('shoutbox_sound', $use_sound);
	update_option('shoutbox_XHTML', $XHTML);
	update_option('shoutbox_online', $Online);
	update_option('shoutbox_Smiley', $Smiley);
	update_option('shoutbox_Show_Spam', $Show_Spam);
	update_option('shoutbox_nb_comment', $nb_comment);
	update_option('shoutbox_Captcha', $Captcha);
	update_option('shoutbox_hash', $hash);
	update_option('shoutbox_HideUsers', $HideUsers);
}

function jal_shout_edit() {
global $wpdb, $jal_table_prefix, $user_level, $jal_admin_user_level;

    // Security
    get_currentuserinfo();
	$current=current_user_can('level_'.$jal_admin_user_level);
    if ($user_level <  $jal_admin_user_level && $current!=1) die(__("Cheatin' uh ?"));
	@mysql_query("SET CHARACTER SET 'utf8'");
	@mysql_query("SET NAMES utf8");
	$wpdb->query("UPDATE ".$jal_table_prefix."liveshoutbox SET text = '".$wpdb->escape($_GET['jal_text'])."',ipaddr='".trim($_GET['ip'])."' WHERE id = ".$wpdb->escape($_GET['jal_comment_id']));
}

function jal_shout_delete() {
global $wpdb, $jal_table_prefix, $user_level, $jal_admin_user_level;

    // Security
    get_currentuserinfo();
	$current=current_user_can('level_'.$jal_admin_user_level);
    if ($user_level <  $jal_admin_user_level && $current!=1) die(__("Cheatin' uh ?"));

	$results = count($wpdb->get_results("SELECT * FROM ".$jal_table_prefix."liveshoutbox"));

	if(1==$results) jal_shout_truncate();
	else {	
		if($_GET['jal_comment_id']) {
			$wpdb->query("DELETE FROM ".$jal_table_prefix."liveshoutbox WHERE id = ".$wpdb->escape($_GET['jal_comment_id']));
		}
	}
}

function jal_shout_truncate() {
global $wpdb, $jal_table_prefix, $user_level, $jal_admin_user_level;

	// Security
	get_currentuserinfo();
	$current=current_user_can('level_'.$jal_admin_user_level);
    if ($user_level <  $jal_admin_user_level && $current!=1) die(__("Cheatin' uh ?"));

	$wpdb->query("TRUNCATE TABLE ".$jal_table_prefix."liveshoutbox");

	$welcome_name = "Pierre";
	$welcome_text = __('Your shoutbox is blank. Add a message!',wordspew);

	$wpdb->query("INSERT INTO ".$jal_table_prefix."liveshoutbox (time,name,text) VALUES ('".time()."','".$welcome_name."','".$welcome_text."')");
}

function jal_shout_spam() {
global $user_level, $jal_admin_user_level, $ip;
$ip=trim($_GET['ip']);
$pos=0;
	get_currentuserinfo();
	$current=current_user_can('level_'.$jal_admin_user_level);
    if ($user_level <  $jal_admin_user_level && $current!=1) die(__("Cheatin' uh ?"));

	$spam=get_option('moderation_keys');
	$ar=explode("\r\n",$spam);
	if(!in_array($ip, $ar)) update_option('moderation_keys', $ip."\r\n".$spam);
	jal_shout_delete();
}

// If user has updated the admin panel
if (isset($_GET['jal_admin_options']))
    add_action('init', 'jal_admin_options');

// If someone has deleted an entry through the admin panel
if (isset($_GET['jal_delete']))
    add_action('init', 'jal_shout_delete');

// If someone has edited an entry through the admin panel
if (isset($_GET['jal_edit']))
    add_action('init', 'jal_shout_edit');

// If someone has clicked the "delete all" button
if (isset($_GET['jal_truncate']))
    add_action('init', 'jal_shout_truncate');

// If it's a spam
if (isset($_GET['jal_ban']))
	add_action('init', 'jal_shout_spam');

// Print to the <script> and <link> (for css) to the head of the document
// And adds the admin menu
if (function_exists('add_action')) {
	if(function_exists('load_plugin_textdomain')) load_plugin_textdomain(wordspew);
	add_action('wp_head', 'jal_add_to_head');
	add_action('admin_menu', 'shoutbox_admin_page');
	if (strstr($_SERVER['REQUEST_URI'], 'wordspew'))
	   add_action('admin_head', 'jal_add_to_admin_head');
} 

/* useronline code */
function jal_get_IP() {
	if (empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
		$ip_address = $_SERVER["REMOTE_ADDR"];
	} else {
		$ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
	}
	if(strpos($ip_address, ',') !== false) {
		$ip_address = explode(',', $ip_address);
		$ip_address = $ip_address[0];
	}
	return $ip_address;
}

function jal_get_useronline_engine($usertimeout = 60) {
	global $jal_table_prefix;
	$tableuseronline = $jal_table_prefix.'liveshoutbox_useronline';

	$conn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	mysql_select_db(DB_NAME, $conn);
	@mysql_query("SET CHARACTER SET 'utf8'", $conn);
	@mysql_query("SET NAMES utf8", $conn);

	// Search Bots
	$bots = array('Google' => 'googlebot', 'MSN' => 'msnbot', 'Alex' => 'ia_archiver', 'Lycos' => 'lycos', 'Ask Jeeves' => 'askjeeves', 'Altavista' => 'scooter', 'AllTheWeb' => 'fast-webcrawler', 'Inktomi' => 'slurp@inktomi', 'Turnitin.com' => 'turnitinbot');

	// Useronline Settings
	$timeoutseconds = $usertimeout;
	$timestamp = time();
	$timeout = $timestamp-$timeoutseconds;

	$sql = "SELECT option_value from {$jal_table_prefix}options where option_name = 'siteurl'";
	$result = mysql_query($sql,$conn);
	while($element = mysql_fetch_array($result)) $siteurl = $element["option_value"];

	// Check Members
	if(isset($_COOKIE['jalUserName']) && (strtolower(substr($_COOKIE['jalUserName'],0,4)) != strtolower(substr(trim($_SESSION['guest']),0,4)))) {
			$memberonline = mysql_real_escape_string(str_replace("\'", "'", $_COOKIE['jalUserName']));
			$where = "WHERE username='".$memberonline."'";
	} else { // guestify the user
		$memberonline = 'guest';
		$where = "WHERE ip='".jal_get_IP()."'";
	}
	// Check For Bot
	foreach ($bots as $name => $lookfor) {
		if (stristr($_SERVER['HTTP_USER_AGENT'], $lookfor) !== false) { 
			$memberonline = mysql_real_escape_string($name);
			$where = "WHERE ip='".jal_get_IP()."'";
		} 
	} 
	$make_page = "(unknown page title)";
	$visitinguri = $_SERVER['REQUEST_URI'];
	if (str_replace("/wordspew.php","",$_SERVER['REQUEST_URI']) != $_SERVER['REQUEST_URI'])
		$visitinguri = null;

	$s = ""; foreach ($_COOKIE as $key=>$val) { $s.="[$key]='$val' --- "; }
	$s = "Live chat: username detected: '" . $memberonline . "' --- cookie: " .$s;

	mysql_query("LOCK TABLES $tableuseronline WRITE", $conn);	

	if ($visitinguri != null)
		mysql_query("UPDATE $tableuseronline SET timestamp = '$timestamp', ip = '".jal_get_IP()."', location = '".mysql_real_escape_string($make_page)."', url = '".mysql_real_escape_string($visitinguri)."' $where", $conn);
	else
		mysql_query("UPDATE $tableuseronline SET timestamp = '$timestamp', ip = '".jal_get_IP()."' $where", $conn);

	// If No User Insert It
	if (mysql_affected_rows($conn) == 0) {
		if ($visitinguri != null)
			mysql_query("INSERT INTO $tableuseronline VALUES ('$timestamp', '$memberonline', '".jal_get_IP()."', '".mysql_real_escape_string($make_page)."', '".mysql_real_escape_string($visitinguri)."')",$conn);
		else 
			mysql_query("INSERT INTO $tableuseronline VALUES ('$timestamp', '$memberonline', '".jal_get_IP()."', NULL,NULL)",$conn);
	}

	mysql_query("DELETE FROM $tableuseronline WHERE timestamp < $timeout",$conn);
	mysql_query("UNLOCK TABLES", $conn);

	$result = mysql_query("SELECT username FROM $tableuseronline",$conn);

	$useronline = array();
	while($element = mysql_fetch_array($result)) $useronline[] = $element["username"];

	$detected_bots = array();
	$registered_users = array();
	$guests = 0;
	foreach ($useronline as $element) {
		if (array_key_exists($element,$bots)) $detected_bots[] = $element;
		elseif ($element == "guest") $guests = $guests + 1;
		else $registered_users[] = $element;
	}
	if (!defined("DB_CHARSET")) {
		@mysql_query("SET CHARACTER SET 'latin1'", $conn);
		@mysql_query("SET NAMES latin1", $conn);
	}
	return array("num_guests"=>$guests,"bots"=>$detected_bots,"users"=>$registered_users);
}

function jal_implode_human($glue,$lastglue,$array) {
	if (count($array) == 0) return ""; // only one element
	if (count($array) == 1) return implode("",$array); // only one element
	if (count($array) == 2) return implode($lastglue,$array); // only one element

	$last_element = array_pop($array);
	$finalstring = implode($glue,$array);
	$finalstring .= $lastglue . $last_element;

	return $finalstring;
}

function jal_get_useronline_extended($usertimeout = 60) {
if($_SESSION['Show_Users']==1) {
	if(!isset($_SESSION['guest'])) {
		$_SESSION['NoOne']=__('No one online.',wordspew);
		$_SESSION['guest']=" " . __('guest',wordspew);
		$_SESSION['guests']=" " . __('guests',wordspew);
		$_SESSION['glue']= " " . __('and',wordspew) . " ";
		$_SESSION['bot'] = " " . __('is crawling the site.',wordspew);
		$_SESSION['bots'] = " " . __('are crawling the site.',wordspew);
		$_SESSION['online'] = " " . __('is online.',wordspew);
		$_SESSION['onlines'] = " " . __('are online.',wordspew);
	}

	$array = jal_get_useronline_engine($usertimeout);
	$u = $array["users"];
	$g = $array["num_guests"];
	$b = $array["bots"];

	/* desired verbiage: */
	/* "Pierre, Framboise and 2 guests online.  Google, Inktomi are crawling the site." */
	/* "Pierre, Framboise and 1 guest online.  Google, Inktomi are crawling the site." */

	/* thus we get an array with nicknames and a string describing the number of guests */
	$tobeimploded = $u;
	if($g == 0) { /* do not do anything */ }
	else $tobeimploded[]= $g . pluralize($g,$_SESSION['guest'],$_SESSION['guests']);
	
	if ($g + count($u) + count($b) == 0) { return $_SESSION['NoOne']; } // no one's here
	$users_online = jal_implode_human(", ",$_SESSION['glue'],$tobeimploded);
	$users_online .= pluralize($g + count($u),$_SESSION['online'],$_SESSION['onlines']);;

	if (count($b)) {
		$bots_online = jal_implode_human(", ",$_SESSION['glue'],$b);
		$bots_online .=pluralize(count($b),$_SESSION['bot'],$_SESSION['bots']);
	}
	else {
		$bots_online = "";
	}
	return $users_online . "  " . $bots_online;
}
else return "";
}
?>